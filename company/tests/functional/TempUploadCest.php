<?php

namespace company\tests;

use common\components\ActivationPresignAuthorizer;
use common\components\TempUploadPresigner;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactFixture;
use common\fixtures\ContactTokenFixture;
use common\models\Company;
use common\models\ContactToken;
use company\models\Contact;
use Codeception\Util\HttpCode;
use PHPUnit\Framework\Assert;
use Yii;

/**
 * Employer temp-upload endpoint.
 *
 * Signer credentials used here are fake and process-local only.
 * Company 1 in the shared fixture is operating, so this test zeroes
 * that disposable row before treating it as a pending activation.
 */
class TempUploadCest
{
    const FAKE_KEY = 'AKIATEMPUPLOADTEST01';
    const FAKE_SECRET = 'fake-temp-upload-secret-for-tests-only';
    const CE37_SENTINEL_KEY = 'CE37-SENTINEL-MUST-NOT-BE-USED';
    const CE37_SENTINEL_SECRET = 'CE37-SECRET-SENTINEL-MUST-NOT-BE-USED';
    const CONTACT_EMAIL = 'goodwin.berenice@maggio.org';
    const CONTACT_KEY = 'INWm4HzH0Mq5Ml_DqcIKMhNbmo0FKhbb';
    const COMPANY_ID = 1;
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
            'contact' => ContactFixture::class,
            'company' => CompanyFixture::class,
            'companyContact' => CompanyContactFixture::class,
            'contactToken' => ContactTokenFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->rememberEnv(self::ENV_NAMES);
        Company::updateAll([
            'total_candidate' => 0,
            'no_of_active_requests' => 0,
            'is_request_updates_in_30_days' => 0,
            'company_status_override' => null,
        ], ['company_id' => self::COMPANY_ID]);
        $this->token = ContactToken::findOne(['contact_uuid' => '20666f33-b761-35c0-8520-b8a1902f3190'])->token_value;
        $I->haveServerParameter('REMOTE_ADDR', '203.0.113.50');
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function _after(FunctionalTester $I)
    {
        $this->restoreEnv();
    }

    public function tryUnauthenticatedLoggedInPresignReturns401(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $I->sendPOST('v1/temp-upload/url', $this->filePayload('logo.png', 'image/png'));
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function tryValidPendingActivationPresignsLogoAndLicenceWithoutConsumingTheKey(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $this->resetBudget();

        $I->sendPOST('http://localhost/index-test.php/v1/temp-upload/activate', $this->activationPayload('logo.png', 'image/png'));
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->assertPresign($I, 'image/png');

        $I->sendPOST('http://localhost/index-test.php/v1/temp-upload/activate', $this->activationPayload('licence.png', 'image/png'));
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->assertPresign($I, 'image/png');

        $contact = Contact::findOne(['contact_email' => self::CONTACT_EMAIL]);
        Assert::assertSame(self::CONTACT_KEY, $contact->contact_auth_key);
    }

    public function tryInvalidActivationIsRejectedAndThenLimited(FunctionalTester $I)
    {
        $this->setSignerEnv();
        $this->resetBudget();
        $payload = $this->activationPayload('logo.png', 'image/png');
        $payload['contact_auth_key'] = '0000';

        for ($attempt = 0; $attempt < ActivationPresignAuthorizer::FAILURE_LIMIT; $attempt++) {
            $I->sendPOST('http://localhost/index-test.php/v1/temp-upload/activate', $payload);
            $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        }

        $I->sendPOST('http://localhost/index-test.php/v1/temp-upload/activate', $payload);
        $I->seeResponseCodeIs(HttpCode::TOO_MANY_REQUESTS);

        $contact = Contact::findOne(['contact_email' => self::CONTACT_EMAIL]);
        Assert::assertSame(self::CONTACT_KEY, $contact->contact_auth_key);
    }

    public function tryOptionsFromEmployerOrigin(FunctionalTester $I)
    {
        $I->haveHttpHeader('Origin', 'https://employer.studenthub.co');
        $I->haveHttpHeader('Access-Control-Request-Method', 'POST');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization,content-type');
        $I->sendOPTIONS('v1/temp-upload/activate');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://employer.studenthub.co');
        $I->seeHttpHeader('Access-Control-Allow-Methods');
    }

    public function tryMissingSignerFailsClosedAndDoesNotUseCe37(FunctionalTester $I)
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_BUCKET_KEY', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_TEMP_BUCKET_SECRET', self::CE37_SENTINEL_SECRET);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/temp-upload/url', $this->filePayload('logo.png', 'image/png'));
        $I->seeResponseCodeIs(503);
        $body = $I->grabResponse();
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_KEY, $body);
        Assert::assertStringNotContainsString(self::CE37_SENTINEL_SECRET, $body);
        Assert::assertStringNotContainsString('SYNTHETIC_TEMP_BUCKET_KEY', $body);
        Assert::assertStringNotContainsString('upload_url', $body);
        Assert::assertStringContainsString('unavailable', strtolower($body));
    }

    /**
     * @param FunctionalTester $I
     * @param string $contentType
     * @return void
     */
    private function assertPresign(FunctionalTester $I, $contentType)
    {
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'method' => 'PUT',
            'bucket' => TempUploadPresigner::BUCKET,
            'expires_in' => 600,
            'headers' => [
                'Content-Type' => $contentType,
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

    /**
     * @param string $filename
     * @param string $contentType
     * @return array
     */
    private function filePayload($filename, $contentType)
    {
        return [
            'filename' => $filename,
            'content_type' => $contentType,
            'file_size' => 2048,
        ];
    }

    /**
     * @param string $filename
     * @param string $contentType
     * @return array
     */
    private function activationPayload($filename, $contentType)
    {
        return $this->filePayload($filename, $contentType) + [
            'contact_email' => self::CONTACT_EMAIL,
            'contact_auth_key' => self::CONTACT_KEY,
            'company_id' => self::COMPANY_ID,
        ];
    }

    /**
     * @return void
     */
    private function resetBudget()
    {
        if (Yii::$app->has('cache')) {
            Yii::$app->cache->flush();
        }
    }

    /**
     * @return void
     */
    private function setSignerEnv()
    {
        $this->clearEnvSources(self::ENV_NAMES);
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
