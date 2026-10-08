<?php

namespace candidate\tests;

use common\components\TempUploadPresigner;
use Codeception\Util\HttpCode;
use PHPUnit\Framework\Assert;

/**
 * Candidate temp-upload route through the Yii bearer stack.
 *
 * Signer values here are fake and process-local. The presign call signs
 * in process and does not upload an object.
 */
class TempUploadCest
{
    const FAKE_KEY = 'AKIATEMPUPLOADTEST01';
    const FAKE_SECRET = 'fake-temp-upload-secret-for-tests-only';
    const TOKEN = 'candidate-presign-test-token';
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

    /** @var array<string, array<string, mixed>>|null */
    private $envSnapshot;

    public function _before(FunctionalTester $I)
    {
        $this->rememberEnv(self::ENV_NAMES);
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function _after(FunctionalTester $I)
    {
        $this->restoreEnv();
    }

    public function tryMissingBearerReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->sendPOST('v1/temp-upload/url', $this->payload());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryInvalidBearerReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer not-a-candidate-token');
        $I->sendPOST('v1/temp-upload/url', $this->payload());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryValidCandidateTokenReturnsPresignContract(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->haveHttpHeader('Authorization', 'Bearer ' . self::TOKEN);
        $I->sendPOST('v1/temp-upload/url', $this->payload());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'method' => 'PUT',
            'bucket' => TempUploadPresigner::BUCKET,
            'expires_in' => 600,
            'headers' => [
                'Content-Type' => 'image/jpeg',
                'x-amz-acl' => 'public-read',
            ],
        ]);

        $response = json_decode($I->grabResponse(), true);
        Assert::assertIsArray($response);
        Assert::assertArrayNotHasKey('secret', $response);
        Assert::assertArrayNotHasKey('access_key', $response);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $I->grabResponse());
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_KEY, $I->grabResponse());
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_SECRET, $I->grabResponse());
        Assert::assertStringContainsString('X-Amz-Expires=600', $response['upload_url']);
        Assert::assertMatchesRegularExpression('/\.jpg$/', $response['key']);
        Assert::assertStringNotContainsString('content-length-range', strtolower($response['upload_url']));
        $I->seeHttpHeader('Cache-Control', 'no-store');
    }

    public function tryOptionsFromStudentOriginDoesNotRequireAuth(FunctionalTester $I)
    {
        $I->deleteHeader('Content-Type');
        $I->haveHttpHeader('Origin', 'https://student.studenthub.co');
        $I->haveHttpHeader('Access-Control-Request-Method', 'POST');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization,content-type');
        $I->sendOPTIONS('v1/temp-upload/url');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://student.studenthub.co');
        $I->seeHttpHeader('Access-Control-Allow-Methods');
        Assert::assertStringNotContainsString('upload_url', $I->grabResponse());
    }

    public function tryMissingSignerFailsClosedAndDoesNotUseCe37(FunctionalTester $I)
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_BUCKET_KEY', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_TEMP_BUCKET_SECRET', self::CE37_SENTINEL_SECRET);
        $I->haveHttpHeader('Authorization', 'Bearer ' . self::TOKEN);
        $I->sendPOST('v1/temp-upload/url', $this->payload());
        $I->seeResponseCodeIs(503);
        $body = $I->grabResponse();
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_KEY, $body);
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_SECRET, $body);
        Assert::assertStringNotContainsString(self::FAKE_KEY, $body);
        Assert::assertStringNotContainsString(self::FAKE_SECRET, $body);
        Assert::assertStringNotContainsString('upload_url', $body);
        Assert::assertStringNotContainsString('AKIA', $body);
        Assert::assertStringContainsString('unavailable', strtolower($body));
    }

    /**
     * @return array
     */
    private function payload()
    {
        return [
            'purpose' => 'profile_photo',
            'filename' => 'photo.jpg',
            'content_type' => 'image/jpeg',
            'file_size' => 2048,
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
