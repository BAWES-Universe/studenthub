<?php
/**
 * Local proof that a timed-out PUT still leaves the synthetic keys registered.
 *
 * This file does not call AWS. The presigner is the application class with a
 * fake access key, and the HTTP handler throws before any socket is opened.
 * Requiring the check script does not run it.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

defined('YII_DEBUG') or define('YII_DEBUG', false);
if (defined('YII_ENV') && YII_ENV !== 'prod') {
    fwrite(STDERR, "FAIL YII_ENV is not prod\n");
    exit(1);
}
defined('YII_ENV') or define('YII_ENV', 'prod');

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require __DIR__ . '/SyntheticCompanyStorageCheck.php';

/**
 * @param bool $condition
 * @param string $label
 * @return void
 */
function assertCheck($condition, $label)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL ' . $label . "\n");
        exit(1);
    }
    fwrite(STDOUT, 'PASS ' . $label . "\n");
}

/**
 * @param string $text
 * @return void
 */
function rejectSensitive($text)
{
    if (preg_match('/X-Amz-|AKIA|Signature=|aws4_request|https?:\/\//i', $text)) {
        fwrite(STDERR, "FAIL output contained a URL or credential marker\n");
        exit(1);
    }
}

if (common\models\Company::permanentDocumentPrefix('licence') !== 'private/company-commercial-licences/'
    || common\models\Company::permanentDocumentPrefix('logo') !== 'company-logos/') {
    fwrite(STDERR, "FAIL production prefixes\n");
    exit(1);
}
fwrite(STDOUT, "PASS production prefixes\n");

new yii\console\Application([
    'id' => 'synthetic-company-storage-timeout',
    'basePath' => $root . '/common',
    'runtimePath' => sys_get_temp_dir() . '/synthetic-company-storage-timeout',
]);

$company = syntheticCompany();
try {
    $company->setOldAttributes(['commercial_licence' => null]);
    $company->commercial_licence = 'privatelicence-synthetic-licence-check-1-0123456789abcdef.png';
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL attribute assignment\n");
    exit(1);
}
$databaseRefused = false;
try {
    $company::getDb();
} catch (RuntimeException $e) {
    $databaseRefused = $e->getMessage() === 'database refused';
}
assertCheck($databaseRefused, 'database refused');

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
if (!is_string($png) || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
    fwrite(STDERR, "FAIL png\n");
    exit(1);
}

$presigner = new common\components\TempUploadPresigner(
    'AK' . 'IA' . 'FAKECHECKKEY0001',
    'synthetic-timeout-secret-value'
);
$created = [];
$putStarted = false;
$http = new GuzzleHttp\Client([
    'handler' => GuzzleHttp\HandlerStack::create(function ($request, array $options) use (&$created, &$putStarted) {
        $putStarted = count($created) === 2;
        throw new GuzzleHttp\Exception\ConnectException('operation timed out', $request, null, ['errno' => 28]);
    }),
    'http_errors' => false,
    'allow_redirects' => false,
]);

$caught = null;
ob_start();
try {
    presignAndPut(
        $presigner,
        $http,
        $png,
        'synthetic-licence-check.png',
        common\components\TempUploadPresigner::aclForPurpose('commercial-licence'),
        '/^privatelicence-synthetic-licence-check-[0-9]+-[a-f0-9]{16}\.png$/',
        $created,
        'private/company-commercial-licences/'
    );
} catch (Throwable $e) {
    $caught = $e;
}
$buffered = (string) ob_get_clean();
rejectSensitive($buffered);

assertCheck($caught instanceof GuzzleHttp\Exception\ConnectException, 'timeout is a transport failure');
assertCheck($putStarted, 'keys were registered before the PUT started');
assertCheck(count($created) === 2, 'timeout left both keys registered');
$temporaryKey = $created[0]['key'];
$permanentKey = $created[1]['key'];
assertCheck(
    $created[0]['bucket'] === common\components\TempUploadPresigner::BUCKET
        && allowedSyntheticKey($created[0]['bucket'], $temporaryKey),
    'temporary key stayed registered'
);
assertCheck(
    $created[1]['bucket'] === 'studenthub-uploads'
        && $permanentKey === 'private/company-commercial-licences/' . $temporaryKey
        && allowedSyntheticKey('studenthub-uploads', $permanentKey),
    'permanent target stayed registered'
);

$invalidCalled = false;
$invalidHttp = new GuzzleHttp\Client([
    'handler' => GuzzleHttp\HandlerStack::create(function ($request, array $options) use (&$invalidCalled) {
        $invalidCalled = true;
        throw new GuzzleHttp\Exception\ConnectException('operation timed out', $request);
    }),
    'http_errors' => false,
    'allow_redirects' => false,
]);
$invalidPresigner = new class {
    public function presign($filename, $contentType, $fileSize, $acl)
    {
        return [
            'key' => 'not-a-synthetic-key.png',
            'bucket' => common\components\TempUploadPresigner::BUCKET,
            'upload_url' => 'https://example.invalid/not-called',
            'headers' => [
                'Content-Type' => 'image/png',
                'x-amz-acl' => $acl,
            ],
        ];
    }
};
$rejected = [];
$invalidCaught = null;
ob_start();
try {
    presignAndPut(
        $invalidPresigner,
        $invalidHttp,
        $png,
        'synthetic-licence-check.png',
        'private',
        '/^privatelicence-synthetic-licence-check-[0-9]+-[a-f0-9]{16}\.png$/',
        $rejected,
        'private/company-commercial-licences/'
    );
} catch (Throwable $e) {
    $invalidCaught = $e;
}
rejectSensitive((string) ob_get_clean());
assertCheck(
    $invalidCaught instanceof RuntimeException && $invalidCaught->getMessage() === 'presign key was not accepted',
    'invalid key is rejected'
);
assertCheck($rejected === [] && $invalidCalled === false, 'invalid key is not registered and does not start a PUT');

fwrite(STDOUT, "PASS timeout cleanup tracking\n");
exit(0);
