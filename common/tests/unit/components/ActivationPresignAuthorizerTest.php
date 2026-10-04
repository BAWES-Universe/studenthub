<?php

namespace common\tests\unit\components;

use common\components\ActivationPresignAuthorizer;
use common\components\ActivationPresignDeniedException;
use common\components\ActivationPresignInputException;
use common\components\ActivationPresignLimitedException;
use common\components\ActivationPresignUnavailableException;
use common\models\Company;
use PHPUnit\Framework\TestCase;

class ActivationPresignAuthorizerTest extends TestCase
{
    /** @var string */
    private $lockDir;

    protected function setUp(): void
    {
        $this->lockDir = sys_get_temp_dir() . '/ce37-activation-lock-' . uniqid('', true);
        mkdir($this->lockDir);
    }

    public function testValidActivationAllowsBothUploadsWithoutConsumingTheKey()
    {
        $contact = $this->contact('1234');
        $cache = new ActivationTestCache();
        $authorizer = $this->authorizer($cache, [$contact]);

        $logo = $authorizer->authorize('owner@example.test', '1234', 42, '203.0.113.8');
        $licence = $authorizer->authorize('owner@example.test', '1234', '42', '203.0.113.8');

        $this->assertSame(42, $logo->company_id);
        $this->assertSame(42, $licence->company_id);
        $this->assertSame('1234', $contact->contact_auth_key);
    }

    public function testDifferentlyCapitalizedEmailIsAcceptedWithoutAFailedGuess()
    {
        $contact = $this->contact('1234');
        $contact->contact_email = 'Owner@Example.TEST';
        $cache = new ActivationTestCache();
        $lookups = [];
        $authorizer = new ActivationPresignAuthorizer($cache, function ($email) use ($contact, &$lookups) {
            $lookups[] = $email;
            if (strtolower($contact->contact_email) === strtolower($email)) {
                return [$contact];
            }
            return [];
        }, $this->lockDir);

        $company = $authorizer->authorize('owner@example.test', '1234', 42, '203.0.113.8');

        $this->assertSame(42, $company->company_id);
        $this->assertSame(['owner@example.test'], $lookups);
        $this->assertSame('1234', $contact->contact_auth_key);
        $this->assertSame([], $this->failureCounts($cache));

        $this->assertDenied($authorizer, 'owner@example.test', '9999', 42);
    }

    public function testInvalidShapesDoNotQueryOrCountAsGuesses()
    {
        $calls = 0;
        $cache = new ActivationTestCache();
        $authorizer = new ActivationPresignAuthorizer($cache, function () use (&$calls) {
            $calls++;
            return [];
        });

        foreach ([
            [null, '1234', 42],
            ['', '1234', 42],
            [['owner@example.test'], '1234', 42],
            ['owner@example.test', ['1234'], 42],
            ['owner@example.test', '1234', ['42']],
            ['owner@example.test', '1234', 0],
            ['owner@example.test', '1234', '42abc'],
        ] as $input) {
            try {
                $authorizer->authorize($input[0], $input[1], $input[2], '203.0.113.8');
                $this->fail('Invalid activation input was accepted.');
            } catch (ActivationPresignInputException $e) {
                $this->assertSame('Invalid activation credentials.', $e->getMessage());
            }
        }

        $this->assertSame(0, $calls);
        $this->assertSame([], $cache->values);
    }

    public function testWrongKeyWrongCompanyAndActiveCompanyAreDenied()
    {
        $contact = $this->contact('1234');
        $cache = new ActivationTestCache();
        $authorizer = $this->authorizer($cache, [$contact]);

        $this->assertDenied($authorizer, 'owner@example.test', '9999', 42);
        $this->assertDenied($authorizer, 'other@example.test', '1234', 42);
        $this->assertDenied($authorizer, 'owner@example.test', '1234', 99);
        $this->assertSame('1234', $contact->contact_auth_key);

        $active = $this->contact('1234');
        $active->companies[0]->company_status_override = Company::STATUS_ACTIVE;
        $this->assertDenied($this->authorizer(new ActivationTestCache(), [$active]), 'owner@example.test', '1234', 42);

        $operating = $this->contact('1234');
        $operating->companies[0]->computed = Company::STATUS_ACTIVE;
        $this->assertDenied($this->authorizer(new ActivationTestCache(), [$operating]), 'owner@example.test', '1234', 42);
    }

    public function testRepeatedGuessesAndExcessiveUrlsAreLimited()
    {
        $contact = $this->contact('1234');
        $cache = new ActivationTestCache();
        $authorizer = $this->authorizer($cache, [$contact]);

        for ($attempt = 0; $attempt < ActivationPresignAuthorizer::FAILURE_LIMIT; $attempt++) {
            $this->assertDenied($authorizer, 'owner@example.test', '0000', 42);
        }

        try {
            $authorizer->authorize('owner@example.test', '1234', 42, '203.0.113.8');
            $this->fail('Guessing was not limited.');
        } catch (ActivationPresignLimitedException $e) {
            $this->assertSame('Temporary upload is unavailable.', $e->getMessage());
        }
        $this->assertSame('1234', $contact->contact_auth_key);

        $fresh = new ActivationTestCache();
        $issuer = $this->authorizer($fresh, [$contact]);
        for ($issued = 0; $issued < ActivationPresignAuthorizer::ISSUE_LIMIT; $issued++) {
            $issuer->authorize('owner@example.test', '1234', 42, '203.0.113.9');
        }

        try {
            $issuer->authorize('owner@example.test', '1234', 42, '203.0.113.9');
            $this->fail('URL issuance was not limited.');
        } catch (ActivationPresignLimitedException $e) {
            $this->assertSame('Temporary upload is unavailable.', $e->getMessage());
        }
        $this->assertSame('1234', $contact->contact_auth_key);
    }

    public function testChangingAddressDoesNotResetTheEmailBudget()
    {
        $contact = $this->contact('1234');
        $cache = new ActivationTestCache();
        $authorizer = $this->authorizer($cache, [$contact]);

        for ($attempt = 0; $attempt < ActivationPresignAuthorizer::FAILURE_LIMIT; $attempt++) {
            $this->assertDenied($authorizer, 'Owner@Example.TEST', '0000', 42, '203.0.113.8');
        }

        try {
            $authorizer->authorize('owner@example.test', '1234', 42, '198.51.100.77');
            $this->fail('A new address reset the email budget.');
        } catch (ActivationPresignLimitedException $e) {
            $this->assertSame('Temporary upload is unavailable.', $e->getMessage());
        }
        $this->assertSame('1234', $contact->contact_auth_key);
    }

    public function testConcurrentAttemptsCannotBypassTheEmailBudget()
    {
        $cacheDir = sys_get_temp_dir() . '/ce37-activation-cache-' . uniqid('', true);
        mkdir($cacheDir);
        $worker = dirname(__FILE__) . '/activation-budget-worker.php';
        $processes = [];
        $pipes = [];

        for ($index = 0; $index < 8; $index++) {
            $descriptor = [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker) . ' ' . escapeshellarg($cacheDir);
            $process = proc_open($command, $descriptor, $workerPipes);
            if (!is_resource($process)) {
                $this->fail('Could not start an activation budget worker.');
            }
            $processes[] = $process;
            $pipes[] = $workerPipes;
        }

        $denied = 0;
        $limited = 0;
        foreach ($processes as $index => $process) {
            $output = stream_get_contents($pipes[$index][1]);
            $error = stream_get_contents($pipes[$index][2]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            $status = proc_close($process);
            $this->assertSame(0, $status, $error);
            if (strpos($output, 'DENIED') !== false) {
                $denied++;
            }
            if (strpos($output, 'LIMITED') !== false) {
                $limited++;
            }
        }

        $this->assertSame(ActivationPresignAuthorizer::FAILURE_LIMIT, $denied);
        $this->assertSame(8 - ActivationPresignAuthorizer::FAILURE_LIMIT, $limited);
    }

    public function testFileCacheGarbageCollectionCannotRemoveTheLock()
    {
        $root = sys_get_temp_dir() . '/ce37-activation-gc-' . uniqid('', true);
        $cacheDir = $root . '/cache';
        mkdir($cacheDir, 0775, true);
        require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';

        $app = new \yii\console\Application([
            'id' => 'ce37-activation-gc',
            'basePath' => dirname(__DIR__, 3),
            'components' => [
                'cache' => [
                    'class' => 'yii\caching\FileCache',
                    'cachePath' => $cacheDir,
                ],
            ],
        ]);

        try {
            $authorizer = new ActivationPresignAuthorizer($app->cache, static function () {
                return [];
            });

            try {
                $authorizer->authorize('owner@example.test', '0000', 42, '203.0.113.8');
                $this->fail('A wrong key was accepted.');
            } catch (ActivationPresignDeniedException $e) {
                $this->assertSame('The requested page does not exist.', $e->getMessage());
            }

            $lock = $root . DIRECTORY_SEPARATOR . ActivationPresignAuthorizer::LOCK_DIRECTORY_NAME
                . DIRECTORY_SEPARATOR . ActivationPresignAuthorizer::LOCK_FILE_NAME;
            $cacheReal = realpath($cacheDir);
            $lockReal = realpath($lock);
            $this->assertNotFalse($cacheReal);
            $this->assertNotFalse($lockReal);
            $this->assertSame(
                realpath($root . DIRECTORY_SEPARATOR . ActivationPresignAuthorizer::LOCK_DIRECTORY_NAME),
                dirname($lockReal)
            );
            $this->assertStringStartsNotWith($cacheReal . DIRECTORY_SEPARATOR, $lockReal);
            $inode = fileinode($lock);

            $this->assertTrue($app->cache->set('ce37-gc-probe', 1, 3600));
            $app->cache->gc(true, false);

            $this->assertFileExists($lock);
            $this->assertSame($inode, fileinode($lock));
            $this->assertFalse($app->cache->get('ce37-gc-probe'));
            $this->assertFileDoesNotExist($cacheDir . DIRECTORY_SEPARATOR . ActivationPresignAuthorizer::LOCK_FILE_NAME);

            try {
                $authorizer->authorize('owner@example.test', '0000', 42, '203.0.113.8');
                $this->fail('A wrong key was accepted after garbage collection.');
            } catch (ActivationPresignDeniedException $e) {
                $this->assertSame('The requested page does not exist.', $e->getMessage());
            }
            $this->assertSame($inode, fileinode($lock));
        } finally {
            \Yii::$app = null;
        }
    }

    public function testMissingCacheFailsClosed()
    {
        $this->expectException(ActivationPresignUnavailableException::class);
        $authorizer = new ActivationPresignAuthorizer(null, function () {
            return [];
        });
        $authorizer->authorize('owner@example.test', '1234', 42, '203.0.113.8');
    }

    private function assertDenied(ActivationPresignAuthorizer $authorizer, $email, $key, $companyId, $ip = '203.0.113.8')
    {
        try {
            $authorizer->authorize($email, $key, $companyId, $ip);
            $this->fail('Invalid activation combination was accepted.');
        } catch (ActivationPresignDeniedException $e) {
            $this->assertSame('The requested page does not exist.', $e->getMessage());
        }
    }

    private function failureCounts(ActivationTestCache $cache)
    {
        $counts = [];
        foreach ($cache->values as $key => $value) {
            if (strpos($key, 'ce37:co:act:email:') === 0 || strpos($key, 'ce37:co:act:ip:') === 0) {
                $counts[$key] = $value;
            }
        }

        return $counts;
    }

    private function authorizer(ActivationTestCache $cache, array $contacts)
    {
        return new ActivationPresignAuthorizer($cache, function ($email) use ($contacts) {
            $matches = [];
            foreach ($contacts as $contact) {
                if ($contact->contact_email === $email) {
                    $matches[] = $contact;
                }
            }
            return $matches;
        }, $this->lockDir);
    }

    private function contact($key)
    {
        $company = new ActivationTestCompany();
        $company->company_id = 42;
        $contact = new \stdClass();
        $contact->contact_uuid = 'contact-42';
        $contact->contact_email = 'owner@example.test';
        $contact->contact_auth_key = $key;
        $contact->companies = [$company];
        return $contact;
    }
}

class ActivationTestCompany
{
    public $company_id;
    public $company_status_override = null;
    public $computed = 0;

    public function getCompany_status()
    {
        if ($this->company_status_override) {
            return $this->company_status_override;
        }

        return $this->computed;
    }
}

class ActivationTestCache
{
    public $values = [];

    public function get($key)
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : false;
    }

    public function set($key, $value, $ttl)
    {
        $this->values[$key] = $value;
        return true;
    }
}
