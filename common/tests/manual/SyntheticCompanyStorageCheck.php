<?php
/**
 * Synthetic upload, copy, and read check for company documents.
 *
 * This file is not part of PHPUnit or Codeception. It does not open a
 * database and it does not read or update a company row.
 *
 * Without --confirm-buckets it prints the plan and exits. It was not run
 * against AWS in this batch. Run it only after the bucket permissions are
 * confirmed, and do not point it at an unverified staging database:
 *
 *   php common/tests/manual/SyntheticCompanyStorageCheck.php --confirm-buckets
 *
 * It reads the existing signer and permanent-storage environment variables.
 * It writes only under synthetic-company-doc-check/{id}/ and deletes those
 * keys before it exits.
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
        'Pass --confirm-buckets only after the bucket permissions are confirmed.',
        'It would then use only synthetic-company-doc-check/{id}/:',
        '1. Put a private licence in the temporary bucket with the signer.',
        '2. Send an anonymous HEAD and require an explicit denial.',
        '3. HEAD and copy that object with the permanent principal and a private ACL.',
        '4. Read the copied object with the permanent principal.',
        '5. Put a public logo and require an anonymous 200.',
        '6. Delete those keys.',
        'No company row and no staging database is used.',
        '',
    ]));
    exit(0);
}

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

const TEMP_BUCKET = 'studenthub-public-anyone-can-upload-24hr-expiry';
const STORED_BUCKET = 'studenthub-uploads';
const REGION = 'eu-west-2';
const PREFIX_ROOT = 'synthetic-company-doc-check/';

$id = bin2hex(random_bytes(8));
$prefix = PREFIX_ROOT . $id . '/';
$tempLicence = $prefix . 'licence.png';
$storedLicence = $prefix . 'licence.png';
$storedLogo = $prefix . 'logo.png';
$created = [];

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

$signer = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => REGION,
    'credentials' => [
        'key' => getenv('AWS_TEMP_UPLOAD_SIGNER_KEY'),
        'secret' => getenv('AWS_TEMP_UPLOAD_SIGNER_SECRET'),
    ],
]);
$permanent = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => REGION,
    'credentials' => [
        'key' => getenv('AWS_S3_RAILWAY_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_S3_RAILWAY_SECRET_ACCESS_KEY'),
    ],
]);
$anonymous = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => REGION,
    'credentials' => false,
]);
$http = new GuzzleHttp\Client([
    'timeout' => 4,
    'connect_timeout' => 2,
    'http_errors' => false,
    'allow_redirects' => false,
]);

$failed = false;

try {
    assertSafeKey($tempLicence);
    assertSafeKey($storedLicence);
    assertSafeKey($storedLogo);

    $signer->putObject([
        'Bucket' => TEMP_BUCKET,
        'Key' => $tempLicence,
        'Body' => 'synthetic-licence',
        'ACL' => 'private',
        'ContentType' => 'image/png',
    ]);
    $created[] = [TEMP_BUCKET, $tempLicence, $signer];
    line('temporary private put status=ok');

    $tempStatus = anonymousStatus($anonymous, $http, TEMP_BUCKET, $tempLicence);
    line('anonymous temporary licence status=' . $tempStatus);
    if ($tempStatus === 200) {
        fail('temporary licence was publicly readable');
    }
    if ($tempStatus !== 403 && $tempStatus !== 404) {
        fail('temporary licence privacy was not an explicit denial');
    }

    $permanent->headObject([
        'Bucket' => TEMP_BUCKET,
        'Key' => $tempLicence,
    ]);
    line('permanent temporary head status=ok');

    $permanent->copyObject([
        'Bucket' => STORED_BUCKET,
        'Key' => $storedLicence,
        'CopySource' => urlencode(TEMP_BUCKET . '/' . $tempLicence),
        'ACL' => 'private',
    ]);
    $created[] = [STORED_BUCKET, $storedLicence, $permanent];
    $copied = $permanent->getObject([
        'Bucket' => STORED_BUCKET,
        'Key' => $storedLicence,
    ]);
    $copiedBody = (string) $copied['Body'];
    if ($copiedBody !== 'synthetic-licence') {
        fail('copied licence body did not match');
    }
    line('permanent licence read status=ok');

    $permanent->putObject([
        'Bucket' => STORED_BUCKET,
        'Key' => $storedLogo,
        'Body' => 'synthetic-logo',
        'ACL' => 'public-read',
        'ContentType' => 'image/png',
    ]);
    $created[] = [STORED_BUCKET, $storedLogo, $permanent];
    $logoStatus = anonymousStatus($anonymous, $http, STORED_BUCKET, $storedLogo);
    line('anonymous logo status=' . $logoStatus);
    if ($logoStatus !== 200) {
        fail('logo was not publicly readable');
    }
} catch (Throwable $e) {
    $failed = true;
    fwrite(STDERR, 'synthetic check failed category=' . failureCategory($e) . "\n");
} finally {
    foreach (array_reverse($created) as $item) {
        list($bucket, $key, $client) = $item;
        try {
            assertSafeKey($key);
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
            line('deleted ' . $key);
        } catch (Throwable $e) {
            $failed = true;
            fwrite(STDERR, 'delete failed category=' . failureCategory($e) . ' key=' . $key . "\n");
        }
    }
}

exit($failed ? 1 : 0);

/**
 * @param string $key
 */
function assertSafeKey($key)
{
    if (!is_string($key) || strpos($key, PREFIX_ROOT) !== 0 || strpos($key, '..') !== false) {
        throw new RuntimeException('unsafe key');
    }
}

/**
 * @param Aws\S3\S3Client $anonymous
 * @param GuzzleHttp\Client $http
 * @param string $bucket
 * @param string $key
 * @return int
 */
function anonymousStatus(Aws\S3\S3Client $anonymous, GuzzleHttp\Client $http, $bucket, $key)
{
    $url = $anonymous->getObjectUrl($bucket, $key);
    $response = $http->request('HEAD', $url);

    return (int) $response->getStatusCode();
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

    return (new ReflectionClass($e))->getShortName();
}

/**
 * @param string $message
 */
function line($message)
{
    fwrite(STDOUT, $message . "\n");
}

/**
 * @param string $message
 */
function fail($message)
{
    throw new RuntimeException($message);
}
