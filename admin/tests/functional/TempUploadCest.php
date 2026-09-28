<?php

namespace admin\tests;

use common\components\TempUploadPresigner;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use Codeception\Util\HttpCode;
use PHPUnit\Framework\Assert;

/**
 * Admin temp-upload endpoint.
 *
 * Signer credentials used here are fake and process-local only.
 */
class TempUploadCest
{
    const FAKE_KEY = 'AKIATEMPUPLOADTEST01';
    const FAKE_SECRET = 'fake-temp-upload-secret-for-tests-only';
    const CE37_SENTINEL_KEY = 'CE37-SENTINEL-MUST-NOT-BE-USED';
    const CE37_SENTINEL_SECRET = 'CE37-SECRET-SENTINEL-MUST-NOT-BE-USED';
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
            'adminToken' => AdminTokenFixture::class,
            'admin' => AdminFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->rememberEnv(self::ENV_NAMES);
        $this->token = AdminToken::find()->one()->token_value;
        $I->haveHttpHeader('Currency', 'KWD');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function _after(FunctionalTester $I)
    {
        $this->restoreEnv();
    }

    public function tryUnauthenticatedPostReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->sendPOST('v1/temp-upload/url', $this->payload(123456));
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function tryAuthenticatedRequestAboveStaffLimitReturnsContract(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->payload(6000000));
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
        Assert::assertArrayNotHasKey('secret', $response);
        Assert::assertArrayNotHasKey('access_key', $response);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $I->grabResponse());
        Assert::assertStringContainsString('X-Amz-Expires=600', $response['upload_url']);
        $I->seeHttpHeader('Cache-Control', 'no-store');
    }

    public function tryFileAboveAdminMaximumRejected(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->payload(18874369));
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        Assert::assertStringContainsString('18 MB Admin maximum', $I->grabResponse());
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryRequestFieldCannotRaiseTheCeiling(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $payload = $this->payload(18874369);
        $payload['max_file_size'] = 999999999;
        $payload['maximum'] = TempUploadPresigner::ADMIN_MAX_FILE_SIZE;
        $I->sendPOST('v1/temp-upload/url', $payload);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        Assert::assertStringContainsString('18 MB Admin maximum', $I->grabResponse());
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryUnsupportedMethodIsNotTheRegisteredRoute(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function tryOptionsCorsAllowsProductionAdminOrigin(FunctionalTester $I)
    {
        $I->haveHttpHeader('Origin', 'https://admin.studenthub.co');
        $I->haveHttpHeader('Access-Control-Request-Method', 'POST');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization,content-type');
        $I->sendOPTIONS('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://admin.studenthub.co');
        $I->seeHttpHeader('Access-Control-Allow-Methods');
    }

    public function tryMissingSignerFailsClosedAndDoesNotUseCe37(FunctionalTester $I)
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_BUCKET_KEY', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_TEMP_BUCKET_SECRET', self::CE37_SENTINEL_SECRET);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->payload(123456));
        $I->seeResponseCodeIs(503);
        $body = $I->grabResponse();
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_KEY, $body);
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_SECRET, $body);
        Assert::assertStringNotContainsString('upload_url', $body);
        Assert::assertStringContainsString('unavailable', strtolower($body));
    }

    /**
     * @param int $fileSize
     * @return array
     */
    private function payload($fileSize)
    {
        return [
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'file_size' => $fileSize,
        ];
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
