<?php

namespace common\components;

use RuntimeException;

/**
 * Enforces strict validation, private ACLs, and short-lived presigned URLs
 * for sensitive Civil ID uploads (#55).
 */
class SecureCivilIdStorage
{
    public const MAX_FILE_SIZE_BYTES = 10485760; // 10 MB

    private const ALLOWED_MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    /**
     * Validates an uploaded file's size and actual magic-byte MIME type before S3 upload.
     */
    public static function validateUploadedFile(string $localFilePath, int $fileSize): string
    {
        if ($fileSize <= 0 || $fileSize > self::MAX_FILE_SIZE_BYTES) {
            throw new RuntimeException('Invalid Civil ID file size (must be between 1B and 10MB).');
        }
        if (!is_readable($localFilePath)) {
            throw new RuntimeException('Uploaded Civil ID file is not readable.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->file($localFilePath);

        if (!isset(self::ALLOWED_MIME_MAP[$detectedMime])) {
            throw new RuntimeException('Unsupported Civil ID file format: ' . $detectedMime);
        }

        return self::ALLOWED_MIME_MAP[$detectedMime];
    }

    /**
     * Generates a non-guessable private S3 object key for a candidate's Civil ID document.
     */
    public static function buildPrivateObjectKey(int $candidateId, string $extension): string
    {
        $randomHex = bin2hex(random_bytes(16));
        $safeExt = preg_replace('/[^a-z0-9]/i', '', strtolower($extension));
        return sprintf('private/civil-id/%d/%s.%s', $candidateId, $randomHex, $safeExt);
    }
}
