<?php

namespace common\components;

use Aws\S3\S3Client;

/**
 * Staff Phase 1 backend presigner for the public 24hr temp upload bucket.
 *
 * This class must never read the retired browser-SDK temp-bucket env vars
 * and must never fall back to any other IAM user.
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
     * Staff ceiling. This is the default when the server does not pass a ceiling.
     * It is not read from the HTTP body.
     */
    const MAX_FILE_SIZE = 5242880;

    /**
     * Admin ceiling. The Admin controller passes this when it constructs the
     * signer. A request field cannot select or raise it.
     */
    const ADMIN_MAX_FILE_SIZE = 18874368;

    /**
     * Employer ceiling. Same 18 MB byte count as Admin, selected only by the
     * company controller. A request field cannot select or raise it.
     */
    const COMPANY_MAX_FILE_SIZE = 18874368;

    /**
     * Candidate profile photo product limit. Matches the student photo picker
     * (10 * 1024 * 1024). It is not the Staff 5 MB or Employer 18 MB ceiling.
     * A request field cannot select or raise it.
     */
    const CANDIDATE_PROFILE_PHOTO_MAX_FILE_SIZE = 10485760;

    /**
     * Single PutObject transport ceiling. S3 documents that one PUT can upload
     * an object up to 5 GB; the API limit is 5 * 1024 * 1024 * 1024 bytes.
     * Candidate civil ID, resume/portfolio, and video have no smaller product
     * cap, so declared file_size is checked against this ceiling.
     *
     * This comparison is request validation only. The signed PUT does not
     * include a content-length-range, so it is not S3-enforced protection.
     * Objects above this size would need multipart upload, which this signer
     * does not create.
     */
    const SINGLE_PUT_MAX_FILE_SIZE = 5368709120;

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

    /**
     * Candidate video only. These types are not in CONTENT_TYPES_BY_EXTENSION,
     * so Staff, Admin, and Employer presigns cannot accept them.
     * mov stays mov and webm stays webm so non-MP4 objects still reach MediaConvert.
     * Empty browser file.type and application/octet-stream resolve from the extension.
     */
    const CANDIDATE_VIDEO_CONTENT_TYPES_BY_EXTENSION = [
        'mp4' => [
            'video/mp4' => true,
        ],
        'mov' => [
            'video/quicktime' => true,
            'video/mov' => true,
        ],
        'webm' => [
            'video/webm' => true,
        ],
    ];

    const CANDIDATE_VIDEO_CANONICAL_CONTENT_TYPE_BY_EXTENSION = [
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
    ];

    /** @var string */
    private $accessKey;

    /** @var string */
    private $secretKey;

    /** @var int */
    private $maximum;

    /** @var string */
    private $limitName = 'Staff';

    /** @var string|null */
    private $candidatePurpose;

    /** @var array<string, array<string, bool>> */
    private $contentTypesByExtension;

    /** @var array<string, string> */
    private $canonicalContentTypeByExtension;

    /**
     * @param string|null $accessKey Injected only by tests. Production uses env vars.
     * @param string|null $secretKey Injected only by tests. Production uses env vars.
     * @param int|null $maxFileSize Server-side ceiling. Null keeps the Staff 5 MB default.
     *        This is not an HTTP field. Callers pass a class constant or omit it.
     * @param string|null $limitName Null for Staff and Admin. The company controller passes Employer.
     *        The candidate controller passes Candidate and leaves the ceiling null;
     *        the purpose argument selects the Candidate policy.
     */
    public function __construct($accessKey = null, $secretKey = null, $maxFileSize = null, $limitName = null)
    {
        $this->accessKey = $accessKey !== null ? (string) $accessKey : self::readEnv(self::SIGNER_KEY_ENV);
        $this->secretKey = $secretKey !== null ? (string) $secretKey : self::readEnv(self::SIGNER_SECRET_ENV);
        $this->contentTypesByExtension = self::CONTENT_TYPES_BY_EXTENSION;
        $this->canonicalContentTypeByExtension = self::CANONICAL_CONTENT_TYPE_BY_EXTENSION;
        $this->maximum = $this->resolveMaximum($maxFileSize, $limitName);
    }

    /**
     * @param mixed $filename
     * @param mixed $contentType
     * @param mixed $fileSize Declared object size. Checked against the server ceiling.
     * @return array
     * @throws TempUploadValidationException
     * @throws TempUploadConfigurationException
     */
    public function presign($filename, $contentType, $fileSize)
    {
        if ($this->limitName === 'Candidate') {
            throw new TempUploadValidationException('Invalid upload purpose.');
        }

        return $this->issuePresign($filename, $contentType, $fileSize);
    }

    /**
     * Candidate-only presign. Purpose selects a fixed server policy.
     * profile_photo keeps the 10 MB product limit. civil_id, resume, and video
     * check declared file_size against SINGLE_PUT_MAX_FILE_SIZE only.
     * The HTTP body cannot choose a different ceiling.
     *
     * @param mixed $purpose profile_photo, civil_id, resume, or video.
     *        Portfolio uses resume. There is no separate portfolio policy.
     * @param mixed $filename
     * @param mixed $contentType
     * @param mixed $fileSize Declared object size. Not an S3 content-length-range.
     * @return array
     * @throws TempUploadValidationException
     * @throws TempUploadConfigurationException
     */
    public function presignForCandidatePurpose($purpose, $filename, $contentType, $fileSize)
    {
        if ($this->limitName !== 'Candidate') {
            throw new TempUploadValidationException('Invalid upload purpose.');
        }

        $this->applyCandidatePurpose($purpose);

        return $this->issuePresign($filename, $contentType, $fileSize);
    }

    /**
     * @param mixed $filename
     * @param mixed $contentType
     * @param mixed $fileSize
     * @return array
     * @throws TempUploadValidationException
     * @throws TempUploadConfigurationException
     */
    private function issuePresign($filename, $contentType, $fileSize)
    {
        if ($this->accessKey === '' || $this->secretKey === '') {
            throw new TempUploadConfigurationException('Temporary upload signer is not configured.');
        }

        // Reject a secret, or any other non-access-key, before the SDK can
        // place it in X-Amz-Credential. The client still receives the
        // existing sanitized 503 and never sees the configured values.
        $this->assertSignerAccessKeyId($this->accessKey, $this->secretKey);

        $declaredContentType = $this->normalizeDeclaredContentType($contentType);
        $validatedFileSize = $this->validateFileSize($fileSize, $this->maximum);
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
     * IAM access key IDs for this signer are AKIA plus 16 uppercase
     * alphanumeric characters. A secret copied into the key variable fails
     * this check, including when both variables hold the same value.
     *
     * @param string $accessKey
     * @param string $secretKey
     * @return void
     * @throws TempUploadConfigurationException
     */
    private function assertSignerAccessKeyId($accessKey, $secretKey)
    {
        if ($accessKey === $secretKey || !preg_match('/\AAKIA[A-Z0-9]{16}\z/', $accessKey)) {
            throw new TempUploadConfigurationException('Temporary upload signer is not configured.');
        }
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
        if ($extension === '' || !isset($this->canonicalContentTypeByExtension[$extension])) {
            throw new TempUploadValidationException('Unsupported file type.');
        }

        if ($contentType === '' || $contentType === 'application/octet-stream') {
            return;
        }

        if (!isset($this->contentTypesByExtension[$extension][$contentType])) {
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

        return $this->canonicalContentTypeByExtension[$extension];
    }

    /**
     * @param string $contentType
     * @return bool
     */
    private function isKnownDeclaredContentType($contentType)
    {
        foreach ($this->contentTypesByExtension as $types) {
            if (isset($types[$contentType])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restrict the active allow-list to extensions already accepted for Staff.
     * Candidate video does not use this helper.
     *
     * @param string[] $extensions
     * @return void
     */
    private function useSharedExtensions(array $extensions)
    {
        $types = [];
        $canonical = [];
        foreach ($extensions as $extension) {
            $types[$extension] = self::CONTENT_TYPES_BY_EXTENSION[$extension];
            $canonical[$extension] = self::CANONICAL_CONTENT_TYPE_BY_EXTENSION[$extension];
        }
        $this->contentTypesByExtension = $types;
        $this->canonicalContentTypeByExtension = $canonical;
    }

    /**
     * @param mixed $purpose
     * @return void
     * @throws TempUploadValidationException
     */
    private function applyCandidatePurpose($purpose)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'bmp', 'tif', 'tiff'];

        if ($purpose === 'profile_photo') {
            $this->candidatePurpose = 'profile_photo';
            $this->maximum = self::CANDIDATE_PROFILE_PHOTO_MAX_FILE_SIZE;
            $this->useSharedExtensions($imageExtensions);
            return;
        }

        if ($purpose === 'civil_id') {
            $this->candidatePurpose = 'civil_id';
            $this->maximum = self::SINGLE_PUT_MAX_FILE_SIZE;
            $this->useSharedExtensions($imageExtensions);
            return;
        }

        if ($purpose === 'resume') {
            $this->candidatePurpose = 'resume';
            $this->maximum = self::SINGLE_PUT_MAX_FILE_SIZE;
            $this->useSharedExtensions(['pdf']);
            return;
        }

        if ($purpose === 'video') {
            $this->candidatePurpose = 'video';
            $this->maximum = self::SINGLE_PUT_MAX_FILE_SIZE;
            $this->contentTypesByExtension = self::CANDIDATE_VIDEO_CONTENT_TYPES_BY_EXTENSION;
            $this->canonicalContentTypeByExtension = self::CANDIDATE_VIDEO_CANONICAL_CONTENT_TYPE_BY_EXTENSION;
            return;
        }

        throw new TempUploadValidationException('Invalid upload purpose.');
    }

    /**
     * Null keeps the Staff 5 MB default. Admin passes ADMIN_MAX_FILE_SIZE.
     * Employer passes COMPANY_MAX_FILE_SIZE with the Employer label because
     * that constant is the same byte count as the Admin ceiling. Candidate
     * passes the Candidate label and a null ceiling; the purpose selects
     * the Candidate policy later. Any other ceiling is rejected.
     * The HTTP body is never consulted here.
     *
     * @param int|null $maxFileSize
     * @param string|null $limitName
     * @return int
     * @throws TempUploadValidationException
     */
    private function resolveMaximum($maxFileSize, $limitName)
    {
        if ($limitName === 'Candidate') {
            if ($maxFileSize !== null) {
                throw new TempUploadValidationException('Invalid file size.');
            }
            $this->limitName = 'Candidate';
            $this->maximum = 0;

            return 0;
        }

        if ($limitName !== null && $limitName !== 'Employer') {
            throw new TempUploadValidationException('Invalid file size.');
        }

        if ($maxFileSize === null) {
            if ($limitName !== null) {
                throw new TempUploadValidationException('Invalid file size.');
            }
            $this->limitName = 'Staff';
            return self::MAX_FILE_SIZE;
        }

        if ($limitName === 'Employer') {
            if ($maxFileSize !== self::COMPANY_MAX_FILE_SIZE) {
                throw new TempUploadValidationException('Invalid file size.');
            }
            $this->limitName = 'Employer';
            return self::COMPANY_MAX_FILE_SIZE;
        }

        if ($maxFileSize === self::MAX_FILE_SIZE) {
            $this->limitName = 'Staff';
            return self::MAX_FILE_SIZE;
        }

        if ($maxFileSize === self::ADMIN_MAX_FILE_SIZE) {
            $this->limitName = 'Admin';
            return self::ADMIN_MAX_FILE_SIZE;
        }

        throw new TempUploadValidationException('Invalid file size.');
    }

    /**
     * @param mixed $fileSize
     * @param int $maximum
     * @return int
     * @throws TempUploadValidationException
     */
    private function validateFileSize($fileSize, $maximum)
    {
        if (is_int($fileSize)) {
            return $this->assertWholeByteSize($fileSize, $maximum);
        }

        if (is_string($fileSize)) {
            if (!preg_match('/^[1-9][0-9]*$/', $fileSize)) {
                throw new TempUploadValidationException('Invalid file size.');
            }

            $maximumText = (string) $maximum;
            if (strlen($fileSize) > strlen($maximumText) || (strlen($fileSize) === strlen($maximumText) && strcmp($fileSize, $maximumText) > 0)) {
                throw new TempUploadValidationException($this->maximumMessage($maximum));
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

            return $this->assertWholeByteSize($size, $maximum);
        }

        throw new TempUploadValidationException('Invalid file size.');
    }

    /**
     * @param int $size
     * @param int $maximum
     * @return int
     * @throws TempUploadValidationException
     */
    private function assertWholeByteSize($size, $maximum)
    {
        if ($size < 1) {
            throw new TempUploadValidationException('Invalid file size.');
        }

        if ($size > $maximum) {
            throw new TempUploadValidationException($this->maximumMessage($maximum));
        }

        return $size;
    }

    /**
     * @param int $maximum
     * @return string
     */
    private function maximumMessage($maximum)
    {
        if ($this->candidatePurpose === 'profile_photo') {
            return 'File size exceeds the 10 MB profile photo maximum.';
        }

        if ($this->limitName === 'Candidate') {
            return 'File size exceeds the 5 GiB single-PUT transport limit.';
        }

        if ($this->limitName === 'Employer') {
            return 'File size exceeds the 18 MB Employer maximum.';
        }

        if ($this->limitName === 'Admin') {
            return 'File size exceeds the 18 MB Admin maximum.';
        }

        return 'File size exceeds the 5 MB Staff maximum.';
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
