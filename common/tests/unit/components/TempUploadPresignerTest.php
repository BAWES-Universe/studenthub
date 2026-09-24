<?php

namespace common\tests\unit\components;

use common\components\TempUploadConfigurationException;
use common\components\TempUploadPresigner;
use common\components\TempUploadValidationException;

/**
 * Offline tests for the Staff temp-upload presigner.
 *
 * Fake signer credentials live only in process memory for this class.
 * These tests must never call AWS.
 */
class TempUploadPresignerTest extends \PHPUnit\Framework\TestCase
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

    /** @var array<string, array<string, mixed>>|null */
    private $envSnapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rememberEnv(self::ENV_NAMES);
    }

    protected function tearDown(): void
    {
        $this->restoreEnv();
        parent::tearDown();
    }

    /**
     * @return TempUploadPresigner
     */
    private function presigner()
    {
        return new TempUploadPresigner(self::FAKE_KEY, self::FAKE_SECRET);
    }

    public function testAuthenticatedValidRequestContract()
    {
        $result = $this->presigner()->presign('document.pdf', 'application/pdf', 123456);

        $this->assertSame('PUT', $result['method']);
        $this->assertSame(TempUploadPresigner::BUCKET, $result['bucket']);
        $this->assertSame(600, $result['expires_in']);
        $this->assertSame('application/pdf', $result['headers']['Content-Type']);
        $this->assertSame('public-read', $result['headers']['x-amz-acl']);
        $this->assertStringContainsString(TempUploadPresigner::BUCKET, $result['upload_url']);
        $this->assertStringContainsString('eu-west-2', $result['upload_url']);
        $this->assertStringContainsString('X-Amz-Expires=600', $result['upload_url']);
        $this->assertStringNotContainsString('x-amz-checksum', strtolower($result['upload_url']));
        $decodedUrl = urldecode($result['upload_url']);
        $this->assertStringContainsString('x-amz-acl=public-read', strtolower($decodedUrl));
        $this->assertMatchesRegularExpression('/X-Amz-SignedHeaders=host(?:%3B|;)x-amz-acl/i', $result['upload_url']);
        $this->assertDoesNotMatchRegularExpression('/X-Amz-SignedHeaders=[^&]*content-type/i', $result['upload_url']);
        $this->assertStringContainsString($result['key'], $result['upload_url']);
        $this->assertStringContainsString($result['key'], $result['public_url']);
        $this->assertStringStartsWith('https://', $result['upload_url']);
        $this->assertArrayNotHasKey('key_supplied_by_client', $result);
        $this->assertMatchesRegularExpression(
            '/X-Amz-Credential=' . preg_quote(self::FAKE_KEY, '/') . '%2F/',
            $result['upload_url']
        );
        $this->assertStringNotContainsString(self::FAKE_SECRET, $result['upload_url']);
        $this->assertStringNotContainsString(self::FAKE_SECRET, $result['public_url']);
    }

    public function testSecretDuplicatedIntoBothSignerFieldsFailsBeforeSigning()
    {
        $secret = 'synthetic-secret-shaped-value-not-an-access-key-0001';
        $presigner = new TempUploadPresigner($secret, $secret);

        try {
            $presigner->presign('photo.png', 'image/png', 128);
            $this->fail('A secret duplicated into the access key must not be signed.');
        } catch (TempUploadConfigurationException $e) {
            $this->assertSame('Temporary upload signer is not configured.', $e->getMessage());
            $this->assertStringNotContainsString($secret, $e->getMessage());
        }
    }

    public function testIdenticalAccessKeyAndSecretFailsBeforeSigning()
    {
        $presigner = new TempUploadPresigner(self::FAKE_KEY, self::FAKE_KEY);

        try {
            $presigner->presign('photo.png', 'image/png', 128);
            $this->fail('The access key must not be reused as the secret.');
        } catch (TempUploadConfigurationException $e) {
            $this->assertSame('Temporary upload signer is not configured.', $e->getMessage());
            $this->assertStringNotContainsString(self::FAKE_KEY, $e->getMessage());
        }
    }

    public function testSecretShapedAccessKeyFailsBeforeSigning()
    {
        $secretShapedKey = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';
        $presigner = new TempUploadPresigner($secretShapedKey, self::FAKE_SECRET);

        try {
            $presigner->presign('photo.png', 'image/png', 128);
            $this->fail('A secret-shaped access key must not be signed.');
        } catch (TempUploadConfigurationException $e) {
            $this->assertSame('Temporary upload signer is not configured.', $e->getMessage());
            $this->assertStringNotContainsString($secretShapedKey, $e->getMessage());
            $this->assertStringNotContainsString(self::FAKE_SECRET, $e->getMessage());
        }
    }

    public function testMissingSignerFailsClosedAndDoesNotUseCe37Params()
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_BUCKET_KEY', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_TEMP_BUCKET_SECRET', self::CE37_SENTINEL_SECRET);
        $this->setEnvSource('AWS_ACCESS_KEY_ID', self::CE37_SENTINEL_KEY);
        $this->setEnvSource('AWS_SECRET_ACCESS_KEY', self::CE37_SENTINEL_SECRET);

        $this->expectException(TempUploadConfigurationException::class);
        $this->expectExceptionMessage('Temporary upload signer is not configured.');
        (new TempUploadPresigner())->presign('document.pdf', 'application/pdf', 123456);
    }

    public function testPartialSignerKeyOnlyFailsClosed()
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_UPLOAD_SIGNER_KEY', self::FAKE_KEY);

        $this->expectException(TempUploadConfigurationException::class);
        $this->expectExceptionMessage('Temporary upload signer is not configured.');
        (new TempUploadPresigner())->presign('document.pdf', 'application/pdf', 123456);
    }

    public function testPartialSignerSecretOnlyFailsClosed()
    {
        $this->clearEnvSources(self::ENV_NAMES);
        $this->setEnvSource('AWS_TEMP_UPLOAD_SIGNER_SECRET', self::FAKE_SECRET);

        $this->expectException(TempUploadConfigurationException::class);
        $this->expectExceptionMessage('Temporary upload signer is not configured.');
        (new TempUploadPresigner())->presign('document.pdf', 'application/pdf', 123456);
    }

    public function testEmptyFilenameRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('', 'application/pdf', 123456);
    }

    public function testBlankFilenameRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('   ', 'application/pdf', 123456);
    }

    public function testPathTraversalFilenameRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('../etc/passwd.pdf', 'application/pdf', 123456);
    }

    public function testSlashFilenameRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('folder/document.pdf', 'application/pdf', 123456);
    }

    public function testBackslashFilenameRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('..\\windows\\document.pdf', 'application/pdf', 123456);
    }

    public function testFileSizeOneByteAccepted()
    {
        $result = $this->presigner()->presign('document.pdf', 'application/pdf', 1);
        $this->assertSame(600, $result['expires_in']);
    }

    public function testFileSizeOverStaffMaximumRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('document.pdf', 'application/pdf', 5242881);
    }

    public function testFractionalFileSizeRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->expectExceptionMessage('Invalid file size.');
        $this->presigner()->presign('document.pdf', 'application/pdf', 5242880.9);
    }

    public function testNegativeAndNonFiniteFileSizeRejected()
    {
        foreach ([-1, -1.5, INF, NAN] as $size) {
            try {
                $this->presigner()->presign('document.pdf', 'application/pdf', $size);
                $this->fail('Expected invalid file size to be rejected.');
            } catch (TempUploadValidationException $e) {
                $this->assertSame('Invalid file size.', $e->getMessage());
            }
        }
    }

    public function testInvalidFileSizeTypesRejected()
    {
        foreach ([true, false, null, '', '01', '12.5', 'abc', '99999999999999999999', [], new \stdClass()] as $size) {
            try {
                $this->presigner()->presign('document.pdf', 'application/pdf', $size);
                $this->fail('Expected invalid file size to be rejected.');
            } catch (TempUploadValidationException $e) {
                $this->assertStringNotContainsString(self::FAKE_SECRET, $e->getMessage());
            }
        }
    }

    public function testIntegerValuedFloatWithinLimitAccepted()
    {
        $result = $this->presigner()->presign('document.pdf', 'application/pdf', 1024.0);
        $this->assertStringEndsWith('.pdf', $result['key']);
    }

    public function testFileSizeAtStaffMaximumAccepted()
    {
        $result = $this->presigner()->presign('document.pdf', 'application/pdf', 5242880);
        $this->assertSame(600, $result['expires_in']);
        $this->assertNotSame('document.pdf', $result['key']);
        $this->assertStringEndsWith('.pdf', $result['key']);
    }

    public function testFileSizeBelowMaximumAccepted()
    {
        $result = $this->presigner()->presign('rates.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1);
        $this->assertStringEndsWith('.xlsx', $result['key']);
    }

    public function testUnsupportedContentTypeRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('payload.html', 'text/html', 123456);
    }

    public function testJpegFilenameWithHtmlContentTypeRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('document.jpg', 'text/html', 123456);
    }

    public function testJpegFilenameWithPdfContentTypeRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('document.jpg', 'application/pdf', 123456);
    }

    public function testSvgImageContentTypeRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('logo.svg', 'image/svg+xml', 123456);
    }

    public function testEmptyBrowserTypeUsesCanonicalPdfType()
    {
        $result = $this->presigner()->presign('document.pdf', '', 123456);
        $this->assertSame('application/pdf', $result['headers']['Content-Type']);
        $this->assertStringEndsWith('.pdf', $result['key']);
    }

    public function testOctetStreamOfficeFileUsesCanonicalType()
    {
        $result = $this->presigner()->presign(
            'rates.xlsx',
            'application/octet-stream',
            123456
        );
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $result['headers']['Content-Type']
        );
    }

    public function testPdfAliasUsesCanonicalType()
    {
        $result = $this->presigner()->presign('document.pdf', 'application/x-pdf', 123456);
        $this->assertSame('application/pdf', $result['headers']['Content-Type']);
    }

    public function testZipOnlyMatchesOfficeOpenXml()
    {
        $docx = $this->presigner()->presign('leave.docx', 'application/zip', 123456);
        $this->assertStringEndsWith('.docx', $docx['key']);

        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('photo.jpg', 'application/zip', 123456);
    }

    public function testHeicFromImagePickerAccepted()
    {
        $result = $this->presigner()->presign('photo.heic', 'image/heic', 123456);
        $this->assertSame('image/heic', $result['headers']['Content-Type']);
        $this->assertStringEndsWith('.heic', $result['key']);
    }

    public function testUnconstrainedCvExtensionRemainsRejected()
    {
        $this->expectException(TempUploadValidationException::class);
        $this->presigner()->presign('resume.pages', '', 123456);
    }

    public function testGeneratedKeyIsServerControlled()
    {
        $result = $this->presigner()->presign('My Resume.pdf', 'application/pdf', 123456);

        $this->assertNotSame('My Resume.pdf', $result['key']);
        $this->assertDoesNotMatchRegularExpression('#[\\\\/]#', $result['key']);
        $this->assertDoesNotMatchRegularExpression('/\\.\\./', $result['key']);
        $this->assertMatchesRegularExpression('/^My-Resume-\d+-[a-f0-9]{16}\.pdf$/', $result['key']);
    }

    public function testCallerCannotSupplyObjectKey()
    {
        $result = $this->presigner()->presign('photo.jpg', 'image/jpeg', 2048);
        $this->assertStringStartsWith('photo-', $result['key']);
        $this->assertStringEndsWith('.jpg', $result['key']);
        $this->assertSame($result['key'], basename($result['key']));
    }

    public function testResponseContainsNoSecretLikeFields()
    {
        $result = $this->presigner()->presign('ticket.png', 'image/png', 4096);
        $this->assertNoSensitiveKeys($result);
        $encoded = json_encode($result);
        $this->assertStringNotContainsString(self::FAKE_SECRET, $encoded);
        $this->assertStringNotContainsString('AWS_TEMP_UPLOAD_SIGNER_SECRET', $encoded);
        $this->assertStringNotContainsString('AWS_TEMP_BUCKET_SECRET', $encoded);
        $this->assertStringNotContainsString('X-Amz-Security-Token', $encoded);
    }

    public function testUrlTargetsOnlyTempBucketAndEuWest2()
    {
        $result = $this->presigner()->presign('brand.png', 'image/png', 1000);
        $this->assertStringContainsString('studenthub-public-anyone-can-upload-24hr-expiry', $result['upload_url']);
        $this->assertStringContainsString('eu-west-2', $result['upload_url']);
        $this->assertStringNotContainsString('studenthub-uploads', $result['upload_url']);
        $this->assertStringNotContainsString('plugn', strtolower($result['upload_url']));
        $this->assertSame(1, substr_count($result['upload_url'], 'studenthub-public-anyone-can-upload-24hr-expiry'));
    }

    public function testRequiredSignedHeaders()
    {
        $result = $this->presigner()->presign('leave.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 2000);
        $this->assertArrayHasKey('Content-Type', $result['headers']);
        $this->assertArrayHasKey('x-amz-acl', $result['headers']);
        $this->assertSame('public-read', $result['headers']['x-amz-acl']);
        $decodedUrl = urldecode($result['upload_url']);
        $this->assertStringContainsString('x-amz-acl=public-read', strtolower($decodedUrl));
        $this->assertMatchesRegularExpression('/X-Amz-SignedHeaders=host(?:%3B|;)x-amz-acl/i', $result['upload_url']);
    }

    public function testStaffPhotoWebpAccepted()
    {
        $result = $this->presigner()->presign('photo.webp', 'image/webp', 3000);
        $this->assertSame('image/webp', $result['headers']['Content-Type']);
    }

    public function testSourceDoesNotReferenceLegacyCredentialStreams()
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/components/TempUploadPresigner.php');
        $this->assertStringNotContainsString('AWS_TEMP_BUCKET_KEY', $source);
        $this->assertStringNotContainsString('AWS_TEMP_BUCKET_SECRET', $source);
        $this->assertStringNotContainsString('CE37', $source);
        $this->assertStringNotContainsString('ODY2X', $source);
        $this->assertStringNotContainsString('WCUM', $source);
        $this->assertStringContainsString('AWS_TEMP_UPLOAD_SIGNER_KEY', $source);
        $this->assertStringContainsString('AWS_TEMP_UPLOAD_SIGNER_SECRET', $source);
        $this->assertStringContainsString('content-length-range', $source);
        $this->assertStringContainsString("getCommand('PutObject'", $source);
        $this->assertStringContainsString('createPresignedRequest', $source);
        $this->assertStringNotContainsString('S3SignatureV4', $source);
        $this->assertStringNotContainsString('getHeaderBlacklist', $source);
    }

    public function testExistingAwsControllerSourceUnchanged()
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/staff/modules/v1/controllers/AwsController.php');
        $this->assertStringContainsString('function actionConfig', $source);
        $this->assertStringContainsString("Yii::\$app->params['aws_temp_access_key_id']", $source);
        $this->assertStringContainsString("Yii::\$app->params['aws_temp_secret_access_key']", $source);
        $this->assertStringNotContainsString('HttpBearerAuth', $source);
        $this->assertStringNotContainsString('TempUpload', $source);
        $this->assertStringNotContainsString('AWS_TEMP_UPLOAD_SIGNER', $source);
    }

    /**
     * @param mixed $data
     * @return void
     */
    private function assertNoSensitiveKeys($data)
    {
        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);
            $this->assertStringNotContainsString('secret', $normalized);
            $this->assertStringNotContainsString('access_key', $normalized);
            $this->assertStringNotContainsString('accesskeyid', $normalized);
            $this->assertStringNotContainsString('sessiontoken', $normalized);
            $this->assertStringNotContainsString('session_token', $normalized);
            if (is_array($value)) {
                $this->assertNoSensitiveKeys($value);
            }
        }
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
