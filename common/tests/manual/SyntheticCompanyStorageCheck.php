<?php
/**
 * Synthetic upload, copy, and read check for company documents.
 *
 * Not part of PHPUnit or Codeception. It does not open a database and it
 * does not read or update a company row.
 *
 * Without --confirm-buckets it prints the plan and exits. With that flag it
 * uses the application presigner and Company promotion helpers:
 *
 *   php common/tests/manual/SyntheticCompanyStorageCheck.php --confirm-buckets
 *
 * Required environment, already used by the application:
 *   AWS_TEMP_UPLOAD_SIGNER_KEY
 *   AWS_TEMP_UPLOAD_SIGNER_SECRET
 *   AWS_S3_RAILWAY_ACCESS_KEY_ID
 *   AWS_S3_RAILWAY_SECRET_ACCESS_KEY
 *
 * The signer only creates the presigned PUT. Cleanup uses the permanent
 * storage principal, which is the railway-s3-access user. This script does
 * not grant DeleteObject to the signer.
 *
 * Results are SDK and presigned-request checks. They are not a browser CORS
 * check, an image-decode check, or a Staff or Admin login check.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$confirm = in_array('--confirm-buckets', $argv, true);
if (!$confirm) {
    fwrite(STDOUT, implode("\n", [
        'Synthetic company storage check is prepared and was not run.',
        'It does not open a database and does not update a company.',
        'Pass --confirm-buckets only in a process that already has the signer and railway-s3-access variables.',
        'It then uses TempUploadPresigner and Company::updateLicence / updateCompanyLogo:',
        '1. Presign a private licence and PUT a 1x1 PNG. The key is privatelicence-synthetic-licence-check-{time}-{16 hex}.png.',
        '2. Require anonymous HEAD and GET denial, then read the bytes with the permanent principal.',
        '3. Promote that object to private/company-commercial-licences/ and repeat the denial and authenticated read.',
        '4. Presign a public logo and PUT a 1x1 PNG. The key is synthetic-logo-check-{time}-{16 hex}.png.',
        '5. Promote it to company-logos/ and require an anonymous HEAD and GET of those bytes.',
        '6. Delete only those exact keys with the permanent principal.',
        'No company row and no staging database is used.',
        '',
    ]));
    exit(0);
}

if (defined('YII_ENV') && YII_ENV !== 'prod') {
    fwrite(STDERR, "Refusing to run because YII_ENV is not prod, so promotion would use a different prefix.\n");
    exit(2);
}
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';

$missing = [];
foreach ([
    'AWS_TEMP_UPLOAD_SIGNER_KEY',
    'AWS_TEMP_UPLOAD_SIGNER_SECRET',
    'AWS_S3_RAILWAY_ACCESS_KEY_ID',
    'AWS_S3_RAILWAY_SECRET_ACCESS_KEY',
] as $name) {
    $value = getenv($name);
    if (!is_string($value) || trim($value) === '') {
        $missing[] = $name;
    }
}
if ($missing) {
    fwrite(STDERR, 'Missing environment: ' . implode(', ', $missing) . "\n");
    exit(2);
}

new yii\console\Application([
    'id' => 'synthetic-company-storage',
    'basePath' => $root . '/common',
    'runtimePath' => sys_get_temp_dir() . '/synthetic-company-storage',
    'components' => [
        'resourceManager' => [
            'class' => common\components\S3ResourceManager::class,
            'authMethod' => common\components\S3ResourceManager::AUTH_VIA_KEY_AND_SECRET,
            'key' => getenv('AWS_S3_RAILWAY_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_S3_RAILWAY_SECRET_ACCESS_KEY'),
            'region' => common\components\TempUploadPresigner::REGION,
            'bucket' => 'studenthub-uploads',
        ],
        'temporaryBucketResourceManager' => [
            'class' => common\components\S3ResourceManager::class,
            'authMethod' => common\components\S3ResourceManager::AUTH_VIA_ANONYMOUS,
            'region' => common\components\TempUploadPresigner::REGION,
            'bucket' => common\components\TempUploadPresigner::BUCKET,
        ],
    ],
]);
if (Yii::$app->has('log', true)) {
    Yii::$app->log->targets = [];
}

if (common\models\Company::permanentDocumentPrefix('licence') !== 'private/company-commercial-licences/') {
    fwrite(STDERR, "Refusing to run because the licence prefix is not the production prefix.\n");
    exit(2);
}
if (common\models\Company::permanentDocumentPrefix('logo') !== 'company-logos/') {
    fwrite(STDERR, "Refusing to run because the logo prefix is not the production prefix.\n");
    exit(2);
}

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
if (!is_string($png) || strlen($png) < 8 || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
    fwrite(STDERR, "synthetic check failed category=invalid-png\n");
    exit(1);
}

$http = new GuzzleHttp\Client([
    'http_errors' => false,
    'allow_redirects' => false,
]);
$anonymousStored = new common\components\S3ResourceManager([
    'authMethod' => common\components\S3ResourceManager::AUTH_VIA_ANONYMOUS,
    'region' => common\components\TempUploadPresigner::REGION,
    'bucket' => 'studenthub-uploads',
]);
$presigner = new common\components\TempUploadPresigner();
$created = [];
$failed = false;

try {
    $licenceKey = presignAndPut(
        $presigner,
        $http,
        $png,
        'synthetic-licence-check.png',
        common\components\TempUploadPresigner::aclForPurpose('commercial-licence'),
        '/^privatelicence-synthetic-licence-check-[0-9]+-[a-f0-9]{16}\.png$/'
    );
    $created[] = ['bucket' => common\components\TempUploadPresigner::BUCKET, 'key' => $licenceKey];
    $created[] = [
        'bucket' => 'studenthub-uploads',
        'key' => common\models\Company::permanentDocumentPrefix('licence') . $licenceKey,
    ];

    expectAnonymousDenial(Yii::$app->temporaryBucketResourceManager, $http, $licenceKey, 'temporary licence');
    expectPermanentBytes(common\components\TempUploadPresigner::BUCKET, $licenceKey, $png, 'temporary licence');

    $licenceCompany = syntheticCompany();
    $licenceCompany->setOldAttributes(['commercial_licence' => null]);
    $licenceCompany->commercial_licence = $licenceKey;
    if (!$licenceCompany->updateLicence()) {
        fail('licence promotion failed');
    }
    $storedLicence = (string) $licenceCompany->commercial_licence;
    if ($storedLicence !== common\models\Company::permanentDocumentPrefix('licence') . $licenceKey) {
        fail('licence promotion key mismatch');
    }
    line('promoted licence key=' . $storedLicence);
    expectAnonymousDenial($anonymousStored, $http, $storedLicence, 'permanent licence');
    expectPermanentBytes('studenthub-uploads', $storedLicence, $png, 'permanent licence');

    $logoKey = presignAndPut(
        $presigner,
        $http,
        $png,
        'synthetic-logo-check.png',
        common\components\TempUploadPresigner::ACL,
        '/^synthetic-logo-check-[0-9]+-[a-f0-9]{16}\.png$/'
    );
    $created[] = ['bucket' => common\components\TempUploadPresigner::BUCKET, 'key' => $logoKey];
    $created[] = [
        'bucket' => 'studenthub-uploads',
        'key' => common\models\Company::permanentDocumentPrefix('logo') . $logoKey,
    ];

    expectAnonymousBytes(Yii::$app->temporaryBucketResourceManager, $http, $logoKey, $png, 'temporary logo');

    $logoCompany = syntheticCompany();
    $logoCompany->setOldAttributes(['company_logo' => null]);
    $logoCompany->company_logo = $logoKey;
    if (!$logoCompany->updateCompanyLogo()) {
        fail('logo promotion failed');
    }
    $storedLogo = (string) $logoCompany->company_logo;
    if ($storedLogo !== common\models\Company::permanentDocumentPrefix('logo') . $logoKey) {
        fail('logo promotion key mismatch');
    }
    line('promoted logo key=' . $storedLogo);
    expectAnonymousBytes($anonymousStored, $http, $storedLogo, $png, 'permanent logo');
    expectPermanentBytes('studenthub-uploads', $storedLogo, $png, 'permanent stored logo');
} catch (Throwable $e) {
    $failed = true;
    fwrite(STDERR, 'synthetic check failed category=' . failureCategory($e) . "\n");
} finally {
    $cleanupFailed = cleanupExactObjects($created);
    if ($cleanupFailed) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);

/**
 * ActiveRecord attribute assignment normally loads the table schema.
 * This subclass keeps the promotion helpers and refuses a database.
 *
 * @return common\models\Company
 */
function syntheticCompany()
{
    return new class extends common\models\Company {
        public static function getDb()
        {
            throw new RuntimeException('database refused');
        }

        public function attributes()
        {
            return ['commercial_licence', 'company_logo', 'company_created_at', 'company_updated_at'];
        }
    };
}

/**
 * @param common\components\TempUploadPresigner $presigner
 * @param GuzzleHttp\Client $http
 * @param string $png
 * @param string $filename
 * @param string $acl
 * @param string $pattern
 * @return string
 */
function presignAndPut(common\components\TempUploadPresigner $presigner, GuzzleHttp\Client $http, $png, $filename, $acl, $pattern)
{
    $presign = $presigner->presign($filename, 'image/png', strlen($png), $acl);
    $key = isset($presign['key']) ? (string) $presign['key'] : '';
    $bucket = isset($presign['bucket']) ? (string) $presign['bucket'] : '';
    $uploadUrl = isset($presign['upload_url']) ? (string) $presign['upload_url'] : '';
    $headers = isset($presign['headers']) && is_array($presign['headers']) ? $presign['headers'] : [];
    unset($presign);

    if ($bucket !== common\components\TempUploadPresigner::BUCKET || !preg_match($pattern, $key) || strpos($key, '/') !== false) {
        fail('presign key was not accepted');
    }
    if (!isset($headers['x-amz-acl']) || $headers['x-amz-acl'] !== $acl) {
        fail('presign acl was not accepted');
    }
    if ($uploadUrl === '') {
        fail('presign upload url was empty');
    }

    $response = $http->request('PUT', $uploadUrl, [
        'headers' => [
            'Content-Type' => isset($headers['Content-Type']) ? $headers['Content-Type'] : 'image/png',
            'x-amz-acl' => $headers['x-amz-acl'],
        ],
        'body' => $png,
        'timeout' => 15,
        'connect_timeout' => 5,
    ]);
    $status = (int) $response->getStatusCode();
    line('presign put ' . $filename . ' status=' . $status . ' key=' . $key . ' acl=' . $acl);
    if ($status !== 200) {
        fail('presign put failed');
    }

    return $key;
}

/**
 * @param common\components\S3ResourceManager $manager
 * @param GuzzleHttp\Client $http
 * @param string $key
 * @param string $label
 * @return void
 */
function expectAnonymousDenial(common\components\S3ResourceManager $manager, GuzzleHttp\Client $http, $key, $label)
{
    foreach (['HEAD', 'GET'] as $method) {
        $status = anonymousStatus($manager, $http, $method, $key);
        line('anonymous ' . $label . ' ' . $method . ' status=' . $status);
        if ($status !== 403) {
            fail('anonymous ' . $label . ' ' . $method . ' was not denied');
        }
    }
}

/**
 * @param common\components\S3ResourceManager $manager
 * @param GuzzleHttp\Client $http
 * @param string $key
 * @param string $png
 * @param string $label
 * @return void
 */
function expectAnonymousBytes(common\components\S3ResourceManager $manager, GuzzleHttp\Client $http, $key, $png, $label)
{
    $head = anonymousStatus($manager, $http, 'HEAD', $key);
    line('anonymous ' . $label . ' HEAD status=' . $head);
    if ($head !== 200) {
        fail('anonymous ' . $label . ' HEAD was not public');
    }
    $response = anonymousResponse($manager, $http, 'GET', $key);
    $status = (int) $response->getStatusCode();
    $body = (string) $response->getBody();
    line('anonymous ' . $label . ' GET status=' . $status . ' bytes=' . strlen($body) . ' match=' . ($body === $png ? 'yes' : 'no'));
    if ($status !== 200 || $body !== $png) {
        fail('anonymous ' . $label . ' GET did not return the PNG');
    }
}

/**
 * @param string $bucket
 * @param string $key
 * @param string $png
 * @param string $label
 * @return void
 */
function expectPermanentBytes($bucket, $key, $png, $label)
{
    if ($bucket === 'studenthub-uploads') {
        $read = Yii::$app->resourceManager->readStoredObject($key);
        $body = $read['bytes'];
    } else {
        $result = Yii::$app->resourceManager->getClient()->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
        $body = (string) $result['Body'];
    }
    line('permanent ' . $label . ' read bytes=' . strlen($body) . ' match=' . ($body === $png ? 'yes' : 'no'));
    if ($body !== $png) {
        fail('permanent ' . $label . ' bytes did not match');
    }
}

/**
 * @param common\components\S3ResourceManager $manager
 * @param GuzzleHttp\Client $http
 * @param string $method
 * @param string $key
 * @return int
 */
function anonymousStatus(common\components\S3ResourceManager $manager, GuzzleHttp\Client $http, $method, $key)
{
    return (int) anonymousResponse($manager, $http, $method, $key)->getStatusCode();
}

/**
 * @param common\components\S3ResourceManager $manager
 * @param GuzzleHttp\Client $http
 * @param string $method
 * @param string $key
 * @return \Psr\Http\Message\ResponseInterface
 */
function anonymousResponse(common\components\S3ResourceManager $manager, GuzzleHttp\Client $http, $method, $key)
{
    return $http->request($method, $manager->getUrl($key), [
        'timeout' => 4,
        'connect_timeout' => 2,
    ]);
}

/**
 * @param array<int, array{bucket: string, key: string}> $created
 * @return bool
 */
function cleanupExactObjects(array $created)
{
    $client = Yii::$app->resourceManager->getClient();
    $failed = false;
    $seen = [];
    foreach ($created as $item) {
        $bucket = $item['bucket'];
        $key = $item['key'];
        $id = $bucket . "\n" . $key;
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        if (!allowedSyntheticKey($bucket, $key)) {
            $failed = true;
            fwrite(STDERR, "cleanup refused key\n");
            continue;
        }
        try {
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
        } catch (Throwable $e) {
            $failed = true;
            fwrite(STDERR, 'cleanup delete failed category=' . failureCategory($e) . ' key=' . $key . "\n");
            continue;
        }
        $status = permanentHeadStatus($client, $bucket, $key);
        if ($status === 404) {
            line('deleted key=' . $key . ' bucket=' . $bucket);
            continue;
        }
        $failed = true;
        fwrite(STDERR, 'cleanup remaining status=' . $status . ' key=' . $key . ' bucket=' . $bucket . "\n");
    }
    if (!$created) {
        line('cleanup created=0');
    }

    return $failed;
}

/**
 * @param \Aws\S3\S3Client $client
 * @param string $bucket
 * @param string $key
 * @return int
 */
function permanentHeadStatus($client, $bucket, $key)
{
    try {
        $client->headObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return 200;
    } catch (Throwable $e) {
        if ($e instanceof Aws\Exception\AwsException && (int) $e->getStatusCode() === 404) {
            return 404;
        }

        return 0;
    }
}

/**
 * @param string $bucket
 * @param string $key
 * @return bool
 */
function allowedSyntheticKey($bucket, $key)
{
    if (!is_string($key) || strpos($key, '..') !== false) {
        return false;
    }
    $licence = '/^privatelicence-synthetic-licence-check-[0-9]+-[a-f0-9]{16}\.png$/';
    $logo = '/^synthetic-logo-check-[0-9]+-[a-f0-9]{16}\.png$/';
    if ($bucket === common\components\TempUploadPresigner::BUCKET) {
        return preg_match($licence, $key) === 1 || preg_match($logo, $key) === 1;
    }
    if ($bucket === 'studenthub-uploads') {
        $name = basename($key);
        $licenceKey = 'private/company-commercial-licences/' . $name;
        $logoKey = 'company-logos/' . $name;

        return ($key === $licenceKey && preg_match($licence, $name) === 1)
            || ($key === $logoKey && preg_match($logo, $name) === 1);
    }

    return false;
}

/**
 * @param Throwable $e
 * @return string
 */
function failureCategory(Throwable $e)
{
    if ($e instanceof Aws\Exception\AwsException) {
        $code = $e->getAwsErrorCode();
        if (is_string($code) && preg_match('/^[A-Za-z0-9]+$/', $code)) {
            return $code;
        }

        return 'AwsException';
    }
    $message = $e->getMessage();
    if ($message === 'database refused' || $message === 'presign key was not accepted' || $message === 'presign acl was not accepted') {
        return $message;
    }

    return (new ReflectionClass($e))->getShortName();
}

/**
 * @param string $message
 * @return void
 */
function line($message)
{
    if (preg_match('/X-Amz-|AKIA|Signature=|aws4_request|https?:\/\//i', $message)) {
        $message = 'redacted output suppressed';
    }
    fwrite(STDOUT, $message . "\n");
}

/**
 * @param string $message
 * @return void
 */
function fail($message)
{
    throw new RuntimeException($message);
}
