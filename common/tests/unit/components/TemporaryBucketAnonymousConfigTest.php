<?php

namespace common\tests\unit\components;

use common\components\S3FileExistValidator;
use common\components\S3ResourceManager;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/common/config/bootstrap.php';

/**
 * Offline checks for the production temporary bucket.
 *
 * Synthetic values stay in process memory. These tests must not call AWS.
 */
class TemporaryBucketAnonymousConfigTest extends \PHPUnit\Framework\TestCase
{
    const FAKE_KEY = 'AKIAPERMANENTCONFIG01';
    const FAKE_SECRET = 'permanent-config-test-secret-value-01';
    const DEFAULT_CHAIN_KEY = 'AKIADEFAULTCHAIN0001';
    const DEFAULT_CHAIN_SECRET = 'default-chain-sentinel-secret-value';
    const BUCKET = 'studenthub-public-anyone-can-upload-24hr-expiry';
    const OBJECT_KEY = 'folder/pic.jpg';

    /** @var array<string, array<string, mixed>>|null */
    private $envSnapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rememberEnv([
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'AWS_SESSION_TOKEN',
            'AWS_PROFILE',
            'AWS_EC2_METADATA_DISABLED',
        ]);
        $this->setEnv('AWS_ACCESS_KEY_ID', self::DEFAULT_CHAIN_KEY);
        $this->setEnv('AWS_SECRET_ACCESS_KEY', self::DEFAULT_CHAIN_SECRET);
        $this->setEnv('AWS_SESSION_TOKEN', 'session-token-must-not-be-used');
        $this->setEnv('AWS_EC2_METADATA_DISABLED', 'true');
        putenv('AWS_PROFILE');
        unset($_ENV['AWS_PROFILE'], $_SERVER['AWS_PROFILE']);
    }

    protected function tearDown(): void
    {
        $this->restoreEnv();
        parent::tearDown();
    }

    public function testProductionTemporaryComponentIsExplicitlyAnonymous()
    {
        $this->assertTemporaryConfigHasNoHardcodedKey();
        $config = $this->productionTemporaryConfig();

        $this->assertSame('common\components\S3ResourceManager', $config['class']);
        $this->assertSame(S3ResourceManager::AUTH_VIA_ANONYMOUS, $config['authMethod']);
        $this->assertSame('eu-west-2', $config['region']);
        $this->assertSame(self::BUCKET, $config['bucket']);
        $this->assertNull($config['key']);
        $this->assertNull($config['secret']);

        $manager = new S3ResourceManager($this->componentConfig($config));
        $client = $manager->getClient();
        $credentials = $client->getCredentials()->wait();

        $this->assertSame('', $credentials->getAccessKeyId());
        $this->assertSame('', $credentials->getSecretKey());
        $this->assertNull($credentials->getSecurityToken());
        $this->assertSame('anonymous', $client->getConfig('signature_version'));
        $this->assertNotSame(self::DEFAULT_CHAIN_KEY, $credentials->getAccessKeyId());
        $this->assertStringNotContainsString(self::DEFAULT_CHAIN_KEY, $manager->getUrl(self::OBJECT_KEY));
    }

    public function testPublicUrlMatchesKeyAndSecretMode()
    {
        $anonymous = $this->anonymousManager();
        $signed = new S3ResourceManager([
            'authMethod' => S3ResourceManager::AUTH_VIA_KEY_AND_SECRET,
            'key' => self::FAKE_KEY,
            'secret' => self::FAKE_SECRET,
            'region' => 'eu-west-2',
            'bucket' => self::BUCKET,
        ]);

        $anonymousUrl = $anonymous->getUrl(self::OBJECT_KEY);
        $signedUrl = $signed->getUrl(self::OBJECT_KEY);

        $this->assertSame($signedUrl, $anonymousUrl);
        $this->assertStringContainsString(self::BUCKET, $anonymousUrl);
        $this->assertStringContainsString('eu-west-2', $anonymousUrl);
        $this->assertStringContainsString('folder/pic.jpg', $anonymousUrl);
        $this->assertStringNotContainsString('X-Amz-', $anonymousUrl);
        $this->assertStringNotContainsString(self::FAKE_KEY, $anonymousUrl);
        $this->assertStringNotContainsString(self::DEFAULT_CHAIN_KEY, $anonymousUrl);
    }

    public function testFileExistsAndValidatorUsePublicHead()
    {
        $present = $this->anonymousManager();
        $present->status = 200;
        $present->headers = [
            'Content-Length' => ['128'],
            'Content-Type' => ['image/jpeg'],
        ];

        $this->assertTrue($present->fileExists(self::OBJECT_KEY));
        $this->assertSame($present->getUrl(self::OBJECT_KEY), $present->uris[0]);
        $this->assertFalse($present->hasErrorsOn('photo', self::OBJECT_KEY));

        $missing = $this->anonymousManager();
        $missing->status = 404;
        $this->assertFalse($missing->fileExists(self::OBJECT_KEY));
        $this->assertTrue($missing->hasErrorsOn('photo', self::OBJECT_KEY));

        $unreachable = $this->anonymousManager();
        $unreachable->status = 0;
        $this->assertFalse($unreachable->fileExists(self::OBJECT_KEY));

        $measured = $this->anonymousManager();
        $measured->status = 200;
        $measured->headers = [
            'Content-Length' => ['128'],
            'Content-Type' => ['image/jpeg'],
        ];
        $this->assertSame('128', $measured->getSize(self::OBJECT_KEY));
        $this->assertTrue($measured->hasErrorsOn('answer', self::OBJECT_KEY, 10));
        $this->assertFalse($measured->hasErrorsOn('answer', self::OBJECT_KEY, 200));
    }

    public function testIamRoleStillFollowsTheDefaultProvider()
    {
        $manager = new S3ResourceManager([
            'authMethod' => S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => 'eu-west-2',
            'bucket' => 'studenthub-uploads',
        ]);
        $credentials = $manager->getClient()->getCredentials()->wait();

        $this->assertSame(self::DEFAULT_CHAIN_KEY, $credentials->getAccessKeyId());
        $this->assertSame(self::DEFAULT_CHAIN_SECRET, $credentials->getSecretKey());
    }

    private function assertTemporaryConfigHasNoHardcodedKey()
    {
        $path = dirname(__DIR__, 4) . '/common/config/main.php';
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->fail('Could not read common/config/main.php');
        }
        $block = $this->componentLines($lines, "'temporaryBucketResourceManager'");
        foreach ($block as $lineNumber => $line) {
            if (strpos($line, 'ODY2X') !== false || preg_match('/AKIA[0-9A-Z]{16}/', $line)) {
                $this->fail('temporaryBucketResourceManager still has an access key on line ' . $lineNumber);
            }
        }
    }

    /**
     * @param string[] $lines
     * @param string $component
     * @return array<int, string>
     */
    private function componentLines(array $lines, $component)
    {
        $start = null;
        foreach ($lines as $i => $line) {
            if (strpos($line, $component) !== false) {
                $start = $i;
                break;
            }
        }
        if ($start === null) {
            $this->fail($component . ' block was not found');
        }

        $block = [];
        $count = count($lines);
        for ($i = $start; $i < $count; $i++) {
            $block[$i + 1] = $lines[$i];
            if ($i > $start && preg_match('/^        \],$/', $lines[$i])) {
                break;
            }
        }
        return $block;
    }

    /**
     * @return array<string, mixed>
     */
    private function productionTemporaryConfig()
    {
        $main = require dirname(__DIR__, 4) . '/common/config/main.php';
        $local = require dirname(__DIR__, 4) . '/environments/prod-railway/common/config/main-local.php';
        $merged = ArrayHelper::merge($main, $local);
        unset($main, $local);
        if (!isset($merged['components']['temporaryBucketResourceManager']) || !is_array($merged['components']['temporaryBucketResourceManager'])) {
            unset($merged);
            $this->fail('production temporaryBucketResourceManager config is missing');
        }
        $component = $merged['components']['temporaryBucketResourceManager'];
        unset($merged);
        if (array_key_exists('key', $component) || array_key_exists('secret', $component)) {
            unset($component);
            $this->fail('production temporaryBucketResourceManager still has credential fields');
        }

        return [
            'class' => isset($component['class']) ? $component['class'] : null,
            'authMethod' => isset($component['authMethod']) ? $component['authMethod'] : null,
            'region' => isset($component['region']) ? $component['region'] : null,
            'bucket' => isset($component['bucket']) ? $component['bucket'] : null,
            'key' => null,
            'secret' => null,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function componentConfig(array $config)
    {
        unset($config['class'], $config['key'], $config['secret']);
        return $config;
    }

    /**
     * @return TemporaryBucketHttpStub
     */
    private function anonymousManager()
    {
        return new TemporaryBucketHttpStub([
            'authMethod' => S3ResourceManager::AUTH_VIA_ANONYMOUS,
            'region' => 'eu-west-2',
            'bucket' => self::BUCKET,
        ]);
    }

    /**
     * @param string[] $names
     */
    private function rememberEnv(array $names)
    {
        $this->envSnapshot = [];
        foreach ($names as $name) {
            $getenv = getenv($name);
            $this->envSnapshot[$name] = [
                'getenv_exists' => $getenv !== false,
                'getenv' => $getenv === false ? null : $getenv,
                'env_exists' => array_key_exists($name, $_ENV),
                'env' => array_key_exists($name, $_ENV) ? $_ENV[$name] : null,
                'server_exists' => array_key_exists($name, $_SERVER),
                'server' => array_key_exists($name, $_SERVER) ? $_SERVER[$name] : null,
            ];
        }
    }

    private function restoreEnv()
    {
        if (!is_array($this->envSnapshot)) {
            return;
        }
        foreach ($this->envSnapshot as $name => $prior) {
            if ($prior['getenv_exists']) {
                putenv($name . '=' . $prior['getenv']);
            } else {
                putenv($name);
            }
            if ($prior['env_exists']) {
                $_ENV[$name] = $prior['env'];
            } else {
                unset($_ENV[$name]);
            }
            if ($prior['server_exists']) {
                $_SERVER[$name] = $prior['server'];
            } else {
                unset($_SERVER[$name]);
            }
        }
        $this->envSnapshot = null;
    }

    /**
     * @param string $name
     * @param string $value
     */
    private function setEnv($name, $value)
    {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Serves HEAD responses without opening a socket.
 */
class TemporaryBucketHttpStub extends S3ResourceManager
{
    /** @var int 0 throws, any other value is the HTTP status */
    public $status = 200;

    /** @var array<string, string[]> */
    public $headers = [];

    /** @var string[] */
    public $uris = [];

    /**
     * @param string $baseUri
     * @return object
     */
    protected function httpClient($baseUri)
    {
        $this->uris[] = $baseUri;
        $status = $this->status;
        $headers = $this->headers;

        return new class($status, $headers) {
            /** @var int */
            private $status;
            /** @var array<string, string[]> */
            private $headers;

            /**
             * @param int $status
             * @param array<string, string[]> $headers
             */
            public function __construct($status, $headers)
            {
                $this->status = $status;
                $this->headers = $headers;
            }

            /**
             * @param string $method
             * @param string $uri
             * @param array<string, mixed> $options
             * @return object
             */
            public function request($method, $uri = '', $options = [])
            {
                if ($this->status === 0) {
                    throw new \RuntimeException('connection failed');
                }
                $status = $this->status;
                $headers = $this->headers;

                return new class($status, $headers) {
                    /** @var int */
                    private $status;
                    /** @var array<string, string[]> */
                    private $headers;

                    /**
                     * @param int $status
                     * @param array<string, string[]> $headers
                     */
                    public function __construct($status, $headers)
                    {
                        $this->status = $status;
                        $this->headers = $headers;
                    }

                    public function getStatusCode()
                    {
                        return $this->status;
                    }

                    /**
                     * @return array<string, string[]>
                     */
                    public function getHeaders()
                    {
                        return $this->headers;
                    }
                };
            }
        };
    }

    /**
     * @param string $attribute
     * @param string $filename
     * @param int|null $maxSize
     */
    public function hasErrorsOn($attribute, $filename, $maxSize = null)
    {
        $model = new DynamicModel([$attribute => $filename]);
        $config = [
            'resourceManager' => $this,
            'filePath' => '',
        ];
        if ($maxSize !== null) {
            $config['maxSize'] = $maxSize;
        }
        $validator = new S3FileExistValidator($config);
        $validator->validateAttribute($model, $attribute);
        return $model->hasErrors($attribute);
    }
}
