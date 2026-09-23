<?php

namespace staff\tests;

use common\components\TempUploadPresigner;
use common\fixtures\StaffTokenFixture;
use common\models\StaffToken;
use Codeception\Util\HttpCode;
use PHPUnit\Framework\Assert;

/**
 * Staff Phase 1 temp-upload endpoint tests.
 *
 * Signer credentials used here are fake and process-local only.
 * file_size is request validation / abuse reduction. A presigned PUT does
 * not cryptographically enforce content-length-range.
 */
class TempUploadCest
{
    const FAKE_KEY = 'TESTTEMPUPLOADKEYID0001';
    const FAKE_SECRET = 'fake-temp-upload-secret-for-tests-only';
    const CE37_SENTINEL_KEY = 'CE37-SENTINEL-MUST-NOT-BE-USED';
    const CE37_SENTINEL_SECRET = 'CE37-SECRET-SENTINEL-MUST-NOT-BE-USED';
    const SYNTHETIC_CONFIG_KEY = 'SYNTHETIC_AWS_CONFIG_KEY';
    const SYNTHETIC_CONFIG_SECRET = 'SYNTHETIC_AWS_CONFIG_SECRET';
    const ENV_NAMES = [
        'AWS_TEMP_UPLOAD_SIGNER_KEY',
        'AWS_TEMP_UPLOAD_SIGNER_SECRET',
        'AWS_TEMP_BUCKET_KEY',
        'AWS_TEMP_BUCKET_SECRET',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
    ];

    public $token;

    /** @var array<string, array<string, mixed>>|null */
    private $envSnapshot;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->rememberEnv(self::ENV_NAMES);
        $this->token = StaffToken::find()->one()->token_value;
        $I->haveHttpHeader('Currency', 'KWD');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function _after(FunctionalTester $I)
    {
        $this->restoreEnv();
    }

    public function tryAuthenticatedValidRequestReturnsContract(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'method' => 'PUT',
            'bucket' => TempUploadPresigner::BUCKET,
            'expires_in' => 600,
            'headers' => [
                'Content-Type' => 'application/pdf',
                'x-amz-acl' => 'public-read',
            ],
        ]);

        $response = json_decode($I->grabResponse(), true);
        Assert::assertIsArray($response);
        Assert::assertArrayHasKey('upload_url', $response);
        Assert::assertArrayHasKey('key', $response);
        Assert::assertArrayHasKey('public_url', $response);
        Assert::assertStringContainsString('studenthub-public-anyone-can-upload-24hr-expiry', $response['upload_url']);
        Assert::assertStringContainsString('eu-west-2', $response['upload_url']);
        Assert::assertStringContainsString('X-Amz-Expires=600', $response['upload_url']);
        Assert::assertMatchesRegularExpression('/^[A-Za-z0-9-]+-\d+-[a-f0-9]{16}\.pdf$/', $response['key']);
        $this->assertNoSensitiveKeys($response);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $I->grabResponse());
        $I->seeHttpHeader('Cache-Control', 'no-store');
    }

    public function tryUnauthenticatedPostReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->sendPOST('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function tryInvalidBearerTokenReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer synthetic-invalid-token');
        $I->sendPOST('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $I->grabResponse());
    }

    public function tryUnsupportedMethodsAreNotRouted(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->sendPUT('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->sendPATCH('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->sendDELETE('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function tryOptionsCorsPreflightRemainsValid(FunctionalTester $I)
    {
        $I->haveHttpHeader('Origin', 'https://staff.studenthub.co');
        $I->haveHttpHeader('Access-Control-Request-Method', 'POST');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization,content-type');
        $I->sendOPTIONS('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin');
        $I->seeHttpHeader('Access-Control-Allow-Methods');
    }

    public function tryMissingSignerFailsClosedAndDoesNotUseCe37(FunctionalTester $I)
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_BUCKET_KEY', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_TEMP_BUCKET_SECRET', self::CE37_SENTINEL_SECRET);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(503);
        $I->seeResponseIsJson();
        $I->seeHttpHeader('Cache-Control', 'no-store');
        $body = $I->grabResponse();
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_KEY, $body);
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_SECRET, $body);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $body);
        Assert::assertStringNotContainsString('AWS_TEMP_BUCKET_SECRET', $body);
        Assert::assertStringNotContainsString('AWS_TEMP_UPLOAD_SIGNER_SECRET', $body);
        Assert::assertStringNotContainsString('upload_url', $body);
        Assert::assertStringContainsString('unavailable', strtolower($body));
    }

    public function tryPartialSignerConfigurationFailsClosed(FunctionalTester $I)
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_UPLOAD_SIGNER_KEY', self::FAKE_KEY);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->validPayload());
        $I->seeResponseCodeIs(503);
        Assert::assertStringNotContainsString(self::FAKE_KEY, $I->grabResponse());
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryEmptyFilenameRejected(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => '',
            'content_type' => 'application/pdf',
            'file_size' => 123456,
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function tryPathTraversalFilenameRejected(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => '../etc/passwd.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 123456,
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->dontSeeResponseContainsJson(['key' => '../etc/passwd.pdf']);
    }

    public function tryFileSizeOverStaffMaximumRejected(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 5242881,
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function tryFileSizeAtStaffMaximumAccepted(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 5242880,
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['expires_in' => 600]);
    }

    public function tryUnsupportedContentTypeRejected(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => 'payload.html',
            'content_type' => 'text/html',
            'file_size' => 123456,
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function tryGeneratedKeyIgnoresCallerSuppliedKey(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', [
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 123456,
            'key' => 'attacker/../secret.pdf',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabResponse(), true);
        Assert::assertNotSame('attacker/../secret.pdf', $response['key']);
        Assert::assertSame($response['key'], basename($response['key']));
        Assert::assertStringStartsWith('document-', $response['key']);
    }

    public function tryExistingAwsConfigRemainsUnauthenticated(FunctionalTester $I)
    {
        \Yii::$app->params['aws_temp_access_key_id'] = self::SYNTHETIC_CONFIG_KEY;
        \Yii::$app->params['aws_temp_secret_access_key'] = self::SYNTHETIC_CONFIG_SECRET;
        $I->sendGET('v1/aws/config');
        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabResponse(), true);
        Assert::assertIsArray($response);
        Assert::assertArrayHasKey('region', $response);
        Assert::assertArrayHasKey('bucket', $response);
        Assert::assertSame(self::SYNTHETIC_CONFIG_KEY, $response['key']);
        Assert::assertSame(self::SYNTHETIC_CONFIG_SECRET, $response['secret']);
        Assert::assertArrayNotHasKey('Cache-Control', $response);
    }

    /**
     * @return array
     */
    private function validPayload()
    {
        return [
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'file_size' => 123456,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    private function assertNoSensitiveKeys(array $data)
    {
        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);
            Assert::assertStringNotContainsString('secret', $normalized);
            Assert::assertStringNotContainsString('access_key', $normalized);
            Assert::assertStringNotContainsString('accesskeyid', $normalized);
            Assert::assertStringNotContainsString('sessiontoken', $normalized);
            if (is_array($value)) {
                $this->assertNoSensitiveKeys($value);
            }
        }
    }

    private function setSignerEnv()
    {
        $this->setEnvSource('AWS_TEMP_UPLOAD_SIGNER_KEY', self::FAKE_KEY);
        $this->setEnvSource('AWS_TEMP_UPLOAD_SIGNER_SECRET', self::FAKE_SECRET);
    }

    /**
     * @param string[] $names
     * @return void
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

    /**
     * @return void
     */
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
     * @param string[] $names
     * @return void
     */
    private function clearEnvSources(array $names)
    {
        foreach ($names as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    private function setEnvSource($name, $value)
    {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
