<?php

namespace common\components;

use Aws\S3\S3Client;

/**
 * Staff Phase 1 backend presigner for the public 24hr temp upload bucket.
 *
 * This class must never read the existing browser-SDK temp-bucket env vars
 * and must never fall back to any other IAM user. Those remain the existing
 * /aws/config path.
 *
 * Signer env vars (Railway will not have these during Phase 1):
 *   AWS_TEMP_UPLOAD_SIGNER_KEY
 *   AWS_TEMP_UPLOAD_SIGNER_SECRET
 *
 * Signing uses the public AWS SDK API only:
 *   S3Client::getCommand('PutObject') then S3Client::createPresignedRequest().
 * There is no custom SignatureV4 subclass.
 *
 * aws-sdk-php 3.339.9 blacklists Content-Type from SigV4, so Content-Type is
 * application-level validation, not a cryptographically signed header.
 * x-amz-acl=public-read remains part of the signed PutObject request.
 * The response still tells the future browser to send both headers.
 *
 * file_size is request validation / abuse reduction only. A normal presigned
 * PUT does not cryptographically enforce a content-length-range the way a
 * presigned POST policy can. Do not treat this check as an S3-enforced cap.
 * Declared content_type is not a byte-level file-content check.
 *
 * Enforced restrictions: authenticated Staff route, server-generated flat key,
 * this one bucket, 600 second expiry, and the signer IAM scope.
 */
class TempUploadPresigner
{
    const BUCKET = 'studenthub-public-anyone-can-upload-24hr-expiry';
    const REGION = 'eu-west-2';
    const EXPIRES_IN = 600;
    const ACL = 'public-read';

    /**
     * Current Staff AwsService maxUploadSize (5 MB).
     */
    const MAX_FILE_SIZE = 5242880;

    const SIGNER_KEY_ENV = 'AWS_TEMP_UPLOAD_SIGNER_KEY';
    const SIGNER_SECRET_ENV = 'AWS_TEMP_UPLOAD_SIGNER_SECRET';

    /**
     * Extension must match one of these declared content types.
     * A known extension paired with a different known type is rejected.
     *
     * Empty string and application/octet-stream are handled separately: they
     * are accepted only for an extension in CANONICAL_CONTENT_TYPE_BY_EXTENSION,
     * and the response Content-Type becomes that canonical type. This covers
     * browsers and native pickers that leave file.type blank or report
     * application/octet-stream. It does not inspect file bytes.
     *
     * Staff inputs covered:
     * - .jpg,.jpeg,.png tickets, account photo, company file, brand
     * - image/* on app-image-upload, including gif, webp, heic, bmp, and tiff
     * - .pdf,.doc,.docx leave, fulltimer, expense, and the upload-cv comment
     * - .xlsx,.xls transfer form, rates, and import
     * - native uploadNativePath, which forwards the OS file.type
     *
     * SVG and HTML stay rejected. upload-cv has no accept attribute, so any
     * other extension remains an owner migration decision.
     */
    const CONTENT_TYPES_BY_EXTENSION = [
        'jpg' => [
            'image/jpeg' => true,
            'image/jpg' => true,
            'image/pjpeg' => true,
        ],
        'jpeg' => [
            'image/jpeg' => true,
            'image/jpg' => true,
            'image/pjpeg' => true,
        ],
        'png' => [
            'image/png' => true,
            'image/x-png' => true,
        ],
        'gif' => [
            'image/gif' => true,
        ],
        'webp' => [
            'image/webp' => true,
        ],
        'heic' => [
            'image/heic' => true,
            'image/heif' => true,
        ],
        'heif' => [
            'image/heif' => true,
            'image/heic' => true,
        ],
        'bmp' => [
            'image/bmp' => true,
            'image/x-ms-bmp' => true,
        ],
        'tif' => [
            'image/tiff' => true,
            'image/tif' => true,
        ],
        'tiff' => [
            'image/tiff' => true,
            'image/tif' => true,
        ],
        'pdf' => [
            'application/pdf' => true,
            'application/x-pdf' => true,
        ],
        'doc' => [
            'application/msword' => true,
            'application/vnd.ms-word' => true,
            'application/vnd.ms-office' => true,
        ],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
            'application/zip' => true,
        ],
        'xls' => [
            'application/vnd.ms-excel' => true,
            'application/excel' => true,
            'application/x-excel' => true,
            'application/x-msexcel' => true,
            'application/vnd.ms-office' => true,
        ],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true,
            'application/zip' => true,
        ],
    ];

    /**
     * Content-Type returned to the future browser. Aliases such as
     * application/x-pdf or application/zip are collapsed to this value.
     */
    const CANONICAL_CONTENT_TYPE_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'bmp' => 'image/bmp',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /** @var string */
    private $accessKey;

    /** @var string */
    private $secretKey;

    /**
     * @param string|null $accessKey Injected only by tests. Production uses env vars.
     * @param string|null $secretKey Injected only by tests. Production uses env vars.
     */
    public function __construct($accessKey = null, $secretKey = null)
    {
        $this->accessKey = $accessKey !== null ? (string) $accessKey : self::readEnv(self::SIGNER_KEY_ENV);
        $this->secretKey = $secretKey !== null ? (string) $secretKey : self::readEnv(self::SIGNER_SECRET_ENV);
    }

    /**
     * @param mixed $filename
     * @param mixed $contentType
     * @param mixed $fileSize
     * @return array
     * @throws TempUploadValidationException
     * @throws TempUploadConfigurationException
     */
    public function presign($filename, $contentType, $fileSize)
    {
        if ($this->accessKey === '' || $this->secretKey === '') {
            throw new TempUploadConfigurationException('Temporary upload signer is not configured.');
        }

        $declaredContentType = $this->normalizeDeclaredContentType($contentType);
        $validatedFileSize = $this->validateFileSize($fileSize);
        $objectKey = $this->generateObjectKey($filename, $declaredContentType);
        $validatedContentType = $this->canonicalContentTypeForKey($objectKey);

        // file_size is not signed into the PUT. It is request validation only.
        unset($validatedFileSize);

        try {
            $client = $this->createClient();
            $command = $client->getCommand('PutObject', [
                'Bucket' => self::BUCKET,
                'Key' => $objectKey,
                'ACL' => self::ACL,
                'ContentType' => $validatedContentType,
            ]);
            $request = $client->createPresignedRequest($command, '+' . self::EXPIRES_IN . ' seconds');
            $uploadUrl = (string) $request->getUri();
            $publicUrl = $client->getObjectUrl(self::BUCKET, $objectKey);
        } catch (\Throwable) {
            throw new TempUploadConfigurationException('Temporary upload presign failed.');
        }

        return [
            'method' => 'PUT',
            'upload_url' => $uploadUrl,
            'key' => $objectKey,
            'bucket' => self::BUCKET,
            'public_url' => $publicUrl,
            'headers' => [
                'Content-Type' => $validatedContentType,
                'x-amz-acl' => self::ACL,
            ],
            'expires_in' => self::EXPIRES_IN,
        ];
    }

    /**
     * @param mixed $filename
     * @param string $contentType
     * @return string
     * @throws TempUploadValidationException
     */
    public function generateObjectKey($filename, $contentType)
    {
        if (!is_string($filename)) {
            throw new TempUploadValidationException('Invalid filename.');
        }

        $filename = trim($filename);
        if ($filename === '') {
            throw new TempUploadValidationException('Invalid filename.');
        }

        if (preg_match('/[\\/\\\\]|[\x00-\x1F\x7F]/', $filename) || strpos($filename, '..') !== false) {
            throw new TempUploadValidationException('Invalid filename.');
        }

        $basename = basename(str_replace('\\', '/', $filename));
        if ($basename === '' || $basename !== $filename) {
            throw new TempUploadValidationException('Invalid filename.');
        }

        $extension = $this->extractExtension($basename);
        $this->assertExtensionMatchesContentType($extension, $contentType);

        $stem = $this->stemFromBasename($basename);
        if ($stem === '') {
            $stem = 'file';
        }

        if (strlen($stem) > 80) {
            $stem = substr($stem, 0, 80);
        }

        return $stem . '-' . time() . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * @return S3Client
     */
    private function createClient()
    {
        return new S3Client([
            'version' => 'latest',
            'region' => self::REGION,
            'credentials' => [
                'key' => $this->accessKey,
                'secret' => $this->secretKey,
            ],
            'use_aws_shared_config_files' => false,
            // SDK 3.337+ defaults to extra checksum headers a browser PUT will not send.
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
        ]);
    }

    /**
     * Normalize a client-declared type. Empty string is kept so a blank
     * browser file.type can be resolved from the filename extension.
     * This does not read or verify file bytes.
     *
     * @param mixed $contentType
     * @return string
     * @throws TempUploadValidationException
     */
    private function normalizeDeclaredContentType($contentType)
    {
        if (!is_string($contentType)) {
            throw new TempUploadValidationException('Unsupported content type.');
        }

        $contentType = strtolower(trim($contentType));
        $semicolon = strpos($contentType, ';');
        if ($semicolon !== false) {
            $contentType = trim(substr($contentType, 0, $semicolon));
        }

        if ($contentType === '' || $contentType === 'application/octet-stream') {
            return $contentType;
        }

        if (!$this->isKnownDeclaredContentType($contentType)) {
            throw new TempUploadValidationException('Unsupported content type.');
        }

        return $contentType;
    }

    /**
     * @param string $extension
     * @param string $contentType
     * @return void
     * @throws TempUploadValidationException
     */
    private function assertExtensionMatchesContentType($extension, $contentType)
    {
        if ($extension === '' || !isset(self::CANONICAL_CONTENT_TYPE_BY_EXTENSION[$extension])) {
            throw new TempUploadValidationException('Unsupported file type.');
        }

        if ($contentType === '' || $contentType === 'application/octet-stream') {
            return;
        }

        if (!isset(self::CONTENT_TYPES_BY_EXTENSION[$extension][$contentType])) {
            throw new TempUploadValidationException('Filename extension does not match content type.');
        }
    }

    /**
     * @param string $objectKey
     * @return string
     */
    private function canonicalContentTypeForKey($objectKey)
    {
        $extension = $this->extractExtension($objectKey);

        return self::CANONICAL_CONTENT_TYPE_BY_EXTENSION[$extension];
    }

    /**
     * @param string $contentType
     * @return bool
     */
    private function isKnownDeclaredContentType($contentType)
    {
        foreach (self::CONTENT_TYPES_BY_EXTENSION as $types) {
            if (isset($types[$contentType])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $fileSize
     * @return int
     * @throws TempUploadValidationException
     */
    private function validateFileSize($fileSize)
    {
        if (is_int($fileSize)) {
            return $this->assertWholeByteSize($fileSize);
        }

        if (is_string($fileSize)) {
            if (!preg_match('/^[1-9][0-9]*$/', $fileSize)) {
                throw new TempUploadValidationException('Invalid file size.');
            }

            $maximum = (string) self::MAX_FILE_SIZE;
            if (strlen($fileSize) > strlen($maximum) || (strlen($fileSize) === strlen($maximum) && strcmp($fileSize, $maximum) > 0)) {
                throw new TempUploadValidationException('File size exceeds the 5 MB Staff maximum.');
            }

            return (int) $fileSize;
        }

        if (is_float($fileSize)) {
            if (!is_finite($fileSize) || floor($fileSize) !== $fileSize) {
                throw new TempUploadValidationException('Invalid file size.');
            }

            $size = (int) $fileSize;
            if ((float) $size !== $fileSize) {
                throw new TempUploadValidationException('Invalid file size.');
            }

            return $this->assertWholeByteSize($size);
        }

        throw new TempUploadValidationException('Invalid file size.');
    }

    /**
     * @param int $size
     * @return int
     * @throws TempUploadValidationException
     */
    private function assertWholeByteSize($size)
    {
        if ($size < 1) {
            throw new TempUploadValidationException('Invalid file size.');
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new TempUploadValidationException('File size exceeds the 5 MB Staff maximum.');
        }

        return $size;
    }

    /**
     * @param string $basename
     * @return string
     */
    private function extractExtension($basename)
    {
        $pos = strrpos($basename, '.');
        if ($basename === '' || $pos === false || $pos < 1) {
            return '';
        }

        return strtolower(substr($basename, $pos + 1));
    }

    /**
     * Match current Staff AwsService.normalizeFileName / stem behavior.
     *
     * @param string $basename
     * @return string
     */
    private function stemFromBasename($basename)
    {
        $pos = strrpos($basename, '.');
        $stem = ($pos === false || $pos < 1) ? $basename : substr($basename, 0, $pos);
        $stem = str_replace([' ', '%20'], '-', $stem);
        $stem = preg_replace('/([^a-z0-9]+)/i', '-', $stem);
        $stem = trim($stem, '-');

        return $stem;
    }

    /**
     * @param string $name
     * @return string
     */
    private static function readEnv($name)
    {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$name]) && is_string($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }
        if (isset($_SERVER[$name]) && is_string($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }

        return '';
    }
}

class TempUploadValidationException extends \InvalidArgumentException
{
}

class TempUploadConfigurationException extends \RuntimeException
{
}
