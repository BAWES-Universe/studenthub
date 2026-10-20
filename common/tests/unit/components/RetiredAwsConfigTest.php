<?php

namespace common\tests\unit\components;

use PHPUnit\Framework\TestCase;
use yii\base\BaseObject;
use yii\web\Application;

require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/common/config/bootstrap.php';

class RetiredAwsConfigTest extends TestCase
{
    const FAKE_KEY = 'RETIRED-CONFIG-KEY';
    const FAKE_SECRET = 'RETIRED-CONFIG-SECRET';

    /** @var array<int, string> */
    private $controllers = [
        \admin\modules\v1\controllers\AwsController::class,
        \candidate\modules\v1\controllers\AwsController::class,
        \company\modules\v1\controllers\AwsController::class,
        \inspector\modules\v1\controllers\AwsController::class,
        \manager\modules\v1\controllers\AwsController::class,
        \staff\modules\v1\controllers\AwsController::class,
        \status\modules\v1\controllers\AwsController::class,
    ];

    protected function tearDown(): void
    {
        $this->clearFakeCredentials();
        if (\Yii::$app !== null) {
            \Yii::$app = null;
        }
        parent::tearDown();
    }

    public function testConfigReturns410AndIgnoresFakeCredentials()
    {
        foreach ($this->controllers as $class) {
            $this->bootApp('GET');
            /** @var \yii\rest\Controller $controller */
            $controller = new $class('aws', \Yii::$app);
            $result = $controller->runAction('config');
            $body = json_encode($result);

            $this->assertSame(410, \Yii::$app->response->statusCode, $class);
            $this->assertSame('no-store', \Yii::$app->response->headers->get('Cache-Control'), $class);
            $this->assertStringNotContainsString(self::FAKE_KEY, $body, $class);
            $this->assertStringNotContainsString(self::FAKE_SECRET, $body, $class);
            $this->assertStringNotContainsString('AKIA', $body, $class);
            $this->assertArrayNotHasKey('key', $result, $class);
            $this->assertArrayNotHasKey('secret', $result, $class);
            $this->assertArrayNotHasKey('region', $result, $class);
            $this->assertArrayNotHasKey('bucket', $result, $class);
            $this->destroyApp();
        }
    }

    public function testPreflightStaysOpenAndDoesNotReturnCredentials()
    {
        foreach ($this->controllers as $class) {
            $this->bootApp('OPTIONS');
            /** @var \yii\rest\Controller $controller */
            $controller = new $class('aws', \Yii::$app);
            $result = $controller->runAction('options', ['id' => 'config']);
            $body = json_encode($result);

            $this->assertNotSame(410, \Yii::$app->response->statusCode, $class);
            $this->assertSame(
                'https://student.studenthub.co',
                \Yii::$app->response->headers->get('Access-Control-Allow-Origin'),
                $class
            );
            $this->assertNotEmpty(\Yii::$app->response->headers->get('Access-Control-Allow-Methods'), $class);
            $this->assertStringNotContainsString(self::FAKE_KEY, (string) $body, $class);
            $this->assertStringNotContainsString(self::FAKE_SECRET, (string) $body, $class);
            $this->destroyApp();
        }
    }

    public function testCredentialParametersAreGoneFromRuntimeConfig()
    {
        $params = file_get_contents(dirname(__DIR__, 4) . '/common/config/params.php');
        $this->assertStringNotContainsString('aws_temp_access_key_id', $params);
        $this->assertStringNotContainsString('aws_temp_secret_access_key', $params);
        $this->assertStringNotContainsString('AWS_TEMP_BUCKET_KEY', $params);
        $this->assertStringNotContainsString('AWS_TEMP_BUCKET_SECRET', $params);

        foreach ($this->controllers as $class) {
            $path = $this->controllerPath($class);
            $source = file_get_contents($path);
            $this->assertStringContainsString('statusCode = 410', $source, $path);
            $this->assertStringContainsString('corsFilter', $source, $path);
            $this->assertStringContainsString("unset(\$behaviors['authenticator'])", $source, $path);
            $this->assertStringContainsString("'class' => 'yii\\rest\\OptionsAction'", $source, $path);
            $this->assertStringNotContainsString('aws_temp_access_key_id', $source, $path);
            $this->assertStringNotContainsString('aws_temp_secret_access_key', $source, $path);
            $this->assertStringNotContainsString('temporaryBucketResourceManager', $source, $path);
        }
    }

    private function bootApp($method)
    {
        $this->destroyApp();
        $this->installFakeCredentials();
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_ORIGIN'] = 'https://student.studenthub.co';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['SCRIPT_NAME'] = '/index-test.php';

        new Application([
            'id' => 'retired-aws-config-test',
            'basePath' => dirname(__DIR__, 4) . '/common',
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'retired-aws-config-test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index-test.php',
                    'enableCsrfValidation' => false,
                    'enableCookieValidation' => false,
                ],
                'user' => [
                    'identityClass' => \yii\base\BaseObject::class,
                    'enableSession' => false,
                    'enableAutoLogin' => false,
                    'loginUrl' => null,
                ],
            ],
            'params' => [
                'allowedOrigins' => ['https://student.studenthub.co'],
                'aws_temp_access_key_id' => self::FAKE_KEY,
                'aws_temp_secret_access_key' => self::FAKE_SECRET,
            ],
        ]);

        \Yii::$app->set('temporaryBucketResourceManager', new class extends BaseObject {
            public function __get($name)
            {
                throw new \RuntimeException('temporary bucket config was read');
            }
        });
    }

    private function destroyApp()
    {
        if (\Yii::$app !== null) {
            \Yii::$app = null;
        }
    }

    private function installFakeCredentials()
    {
        putenv('AWS_TEMP_BUCKET_KEY=' . self::FAKE_KEY);
        putenv('AWS_TEMP_BUCKET_SECRET=' . self::FAKE_SECRET);
        $_ENV['AWS_TEMP_BUCKET_KEY'] = self::FAKE_KEY;
        $_ENV['AWS_TEMP_BUCKET_SECRET'] = self::FAKE_SECRET;
        $_SERVER['AWS_TEMP_BUCKET_KEY'] = self::FAKE_KEY;
        $_SERVER['AWS_TEMP_BUCKET_SECRET'] = self::FAKE_SECRET;
    }

    private function clearFakeCredentials()
    {
        putenv('AWS_TEMP_BUCKET_KEY');
        putenv('AWS_TEMP_BUCKET_SECRET');
        unset(
            $_ENV['AWS_TEMP_BUCKET_KEY'],
            $_ENV['AWS_TEMP_BUCKET_SECRET'],
            $_SERVER['AWS_TEMP_BUCKET_KEY'],
            $_SERVER['AWS_TEMP_BUCKET_SECRET']
        );
    }

    /**
     * @param class-string $class
     */
    private function controllerPath($class)
    {
        $relative = str_replace('\\', '/', $class) . '.php';
        return dirname(__DIR__, 4) . '/' . $relative;
    }
}
