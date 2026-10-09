<?php

namespace common\tests\unit\components;

use common\components\S3ResourceManager;
use yii\base\InvalidConfigException;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/common/config/bootstrap.php';

/**
 * Offline checks for production permanent-storage credentials.
 *
 * Synthetic values stay in process memory. These tests must not call AWS.
 */
class RailwayPermanentStorageConfigTest extends \PHPUnit\Framework\TestCase
{
    const FAKE_KEY = 'AKIARAILWAYCONFIGTEST';
    const FAKE_SECRET = 'railway-config-test-secret-value-0001';
    const DEFAULT_CHAIN_KEY = 'AKIADEFAULTCHAIN0001';
    const DEFAULT_CHAIN_SECRET = 'default-chain-sentinel-secret-value';

    const RAILWAY_KEY = 'AWS_S3_RAILWAY_ACCESS_KEY_ID';
    const RAILWAY_SECRET = 'AWS_S3_RAILWAY_SECRET_ACCESS_KEY';

    /** @var array<string, array<string, mixed>>|null */
    private $envSnapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rememberEnv([
            self::RAILWAY_KEY,
            self::RAILWAY_SECRET,
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'AWS_SESSION_TOKEN',
            'AWS_PROFILE',
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreEnv();
        parent::tearDown();
    }

    public function testConfiguredVariablesAreSelected()
    {
        $this->assertProductionConfigHasNoHardcodedPermanentKey();
        $this->clearDefaultChain();
        $this->setEnv(self::RAILWAY_KEY, '  ' . self::FAKE_KEY . '  ');
        $this->setEnv(self::RAILWAY_SECRET, "\n" . self::FAKE_SECRET . "\n");
        $this->setEnv('AWS_ACCESS_KEY_ID', self::DEFAULT_CHAIN_KEY);
        $this->setEnv('AWS_SECRET_ACCESS_KEY', self::DEFAULT_CHAIN_SECRET);
        $this->setEnv('AWS_SESSION_TOKEN', 'session-token-must-not-be-used');

        $config = $this->resourceManagerConfig();
        $this->assertSame('common\components\S3ResourceManager', $config['class']);
        $this->assertSame(S3ResourceManager::AUTH_VIA_KEY_AND_SECRET, $config['authMethod']);
        $this->assertSame('eu-west-2', $config['region']);
        $this->assertSame('studenthub-uploads', $config['bucket']);
        $this->assertSame(self::FAKE_KEY, $config['key']);
        $this->assertSame(self::FAKE_SECRET, $config['secret']);

        $manager = new S3ResourceManager($this->componentConfig($config));
        $credentials = $manager->getClient()->getCredentials()->wait();
        $this->assertSame(self::FAKE_KEY, $credentials->getAccessKeyId());
        $this->assertSame(self::FAKE_SECRET, $credentials->getSecretKey());
        $this->assertNull($credentials->getSecurityToken());
        $this->assertNotSame(self::DEFAULT_CHAIN_KEY, $credentials->getAccessKeyId());
    }

    public function testMissingOrBlankCredentialsFailClosed()
    {
        $this->assertProductionConfigHasNoHardcodedPermanentKey();
        $cases = [
            'missing' => [null, null],
            'empty' => ['', ''],
            'whitespace' => ['   ', "\t"],
            'secret missing' => [self::FAKE_KEY, ''],
            'key missing' => ['   ', self::FAKE_SECRET],
        ];

        foreach ($cases as $name => $pair) {
            $this->clearDefaultChain();
            $this->setEnv('AWS_ACCESS_KEY_ID', self::DEFAULT_CHAIN_KEY);
            $this->setEnv('AWS_SECRET_ACCESS_KEY', self::DEFAULT_CHAIN_SECRET);
            $this->setEnv('AWS_SESSION_TOKEN', 'session-token-must-not-be-used');
            $this->applyRailwayPair($pair[0], $pair[1]);

            $config = $this->resourceManagerConfig();
            $this->assertTrue(in_array($config['key'], [self::FAKE_KEY, null], true), $name);
            $this->assertTrue(in_array($config['secret'], [self::FAKE_SECRET, null], true), $name);
            $this->assertTrue($config['key'] === null || $config['secret'] === null, $name);

            try {
                new S3ResourceManager($this->componentConfig($config));
                $this->fail($name . ' credentials were accepted');
            } catch (InvalidConfigException $e) {
                $this->assertMessageHasNoCredentialMaterial($e->getMessage(), $name);
                $this->assertStringContainsString('cannot be empty', $e->getMessage(), $name);
            }
        }
    }

    public function testIamRoleConfigurationDoesNotRequireAKey()
    {
        $manager = new S3ResourceManager([
            'authMethod' => S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => 'eu-west-2',
            'bucket' => 'studenthub-uploads',
        ]);

        $this->assertSame(S3ResourceManager::AUTH_VIA_IAM_ROLE, $manager->authMethod);
        $this->assertSame('studenthub-uploads', $manager->bucket);
        $this->assertNull($manager->key);
        $this->assertNull($manager->secret);
    }

    private function assertProductionConfigHasNoHardcodedPermanentKey()
    {
        $root = dirname(__DIR__, 4) . '/environments/prod-railway';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') {
                continue;
            }
            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                $this->fail('Could not read ' . $file->getPathname());
            }
            foreach ($lines as $i => $line) {
                if (strpos($line, 'WCUM') !== false) {
                    $this->fail('WCUM remains at ' . $file->getPathname() . ' line ' . ($i + 1));
                }
            }
        }

        $path = $this->configPath();
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $block = $this->resourceManagerLines($lines);
        $sawKey = false;
        $sawSecret = false;
        foreach ($block as $lineNumber => $line) {
            if (preg_match('/AKIA[0-9A-Z]{16}/', $line)) {
                $this->fail('resourceManager still has an access key on line ' . $lineNumber);
            }
            if (strpos($line, "'key'") !== false) {
                $sawKey = strpos($line, '$railwayS3AccessKeyId') !== false;
                if (!$sawKey) {
                    $this->fail('resourceManager key is not the Railway variable on line ' . $lineNumber);
                }
            }
            if (strpos($line, "'secret'") !== false) {
                $sawSecret = strpos($line, '$railwayS3SecretAccessKey') !== false;
                if (!$sawSecret) {
                    $this->fail('resourceManager secret is not the Railway variable on line ' . $lineNumber);
                }
            }
        }
        if (!$sawKey || !$sawSecret) {
            $this->fail('resourceManager credential assignments were not found');
        }

        $source = implode("\n", $lines);
        if (strpos($source, self::RAILWAY_KEY) === false || strpos($source, self::RAILWAY_SECRET) === false) {
            $this->fail('production config does not read the Railway permanent-storage variables');
        }
    }

    /**
     * @param string[] $lines
     * @return array<int, string>
     */
    private function resourceManagerLines(array $lines)
    {
        $start = null;
        foreach ($lines as $i => $line) {
            if (strpos($line, "'resourceManager'") !== false) {
                $start = $i;
                break;
            }
        }
        if ($start === null) {
            $this->fail('resourceManager block was not found');
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
    private function resourceManagerConfig()
    {
        $config = include $this->configPath();
        if (!isset($config['components']['resourceManager']) || !is_array($config['components']['resourceManager'])) {
            unset($config);
            $this->fail('production resourceManager config is missing');
        }
        $manager = $config['components']['resourceManager'];
        unset($config);

        return [
            'class' => isset($manager['class']) ? $manager['class'] : null,
            'authMethod' => isset($manager['authMethod']) ? $manager['authMethod'] : null,
            'region' => isset($manager['region']) ? $manager['region'] : null,
            'bucket' => isset($manager['bucket']) ? $manager['bucket'] : null,
            'key' => array_key_exists('key', $manager) ? $manager['key'] : null,
            'secret' => array_key_exists('secret', $manager) ? $manager['secret'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function componentConfig(array $config)
    {
        unset($config['class']);
        return $config;
    }

    private function configPath()
    {
        return dirname(__DIR__, 4) . '/environments/prod-railway/common/config/main-local.php';
    }

    /**
     * @param string|null $key
     * @param string|null $secret
     */
    private function applyRailwayPair($key, $secret)
    {
        $this->applyOptionalEnv(self::RAILWAY_KEY, $key);
        $this->applyOptionalEnv(self::RAILWAY_SECRET, $secret);
    }

    /**
     * @param string $name
     * @param string|null $value null removes the variable
     */
    private function applyOptionalEnv($name, $value)
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
            return;
        }
        $this->setEnv($name, $value);
    }

    private function clearDefaultChain()
    {
        foreach (['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_SESSION_TOKEN', 'AWS_PROFILE'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    /**
     * @param string $message
     * @param string $name
     */
    private function assertMessageHasNoCredentialMaterial($message, $name)
    {
        $this->assertStringNotContainsString(self::FAKE_KEY, $message, $name);
        $this->assertStringNotContainsString(self::FAKE_SECRET, $message, $name);
        $this->assertStringNotContainsString(self::DEFAULT_CHAIN_KEY, $message, $name);
        $this->assertStringNotContainsString(self::DEFAULT_CHAIN_SECRET, $message, $name);
        $this->assertStringNotContainsString('AKIA', $message, $name);
        $this->assertStringNotContainsString('WCUM', $message, $name);
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
