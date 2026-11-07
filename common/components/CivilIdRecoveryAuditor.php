<?php

namespace common\components;

/**
 * Classifies and promotes candidate Civil ID objects across permanent, legacy,
 * and temporary S3 prefixes without destructively clearing database fields.
 */
final class CivilIdRecoveryAuditor
{
    public const STATUS_EMPTY = 'empty';
    public const STATUS_PERMANENT_PRESENT = 'permanent_present';
    public const STATUS_RECOVERABLE_LEGACY = 'recoverable_from_legacy';
    public const STATUS_RECOVERABLE_TEMP = 'recoverable_from_temp';
    public const STATUS_MISSING_NEEDS_REUPLOAD = 'missing_needs_reupload';
    public const STATUS_STORAGE_UNAVAILABLE = 'storage_error_or_unconfigured';

    public const PERMANENT_PREFIX = 'photos/';
    public const LEGACY_PREFIX = 'candidate-civil-id/';

    /**
     * Normalize a stored Civil ID database value to its bare object basename.
     */
    public static function extractBasename($storedValue): string
    {
        $trimmed = trim((string) $storedValue);
        if ($trimmed === '') {
            return '';
        }

        return basename(str_replace('\\', '/', $trimmed));
    }

    /**
     * Build the canonical permanent S3 key under photos/<filename>.
     */
    public static function permanentKey($storedValue): string
    {
        $base = self::extractBasename($storedValue);
        if ($base === '') {
            return '';
        }

        return self::PERMANENT_PREFIX . $base;
    }

    /**
     * Build the legacy permanent S3 key under candidate-civil-id/<filename>.
     */
    public static function legacyKey($storedValue): string
    {
        $base = self::extractBasename($storedValue);
        if ($base === '') {
            return '';
        }

        return self::LEGACY_PREFIX . $base;
    }

    /**
     * Probe storage managers to classify where a candidate Civil ID file resides.
     *
     * Checks the canonical permanent prefix first, then the legacy permanent prefix,
     * and finally the 24-hour temporary upload bucket before flagging for re-upload.
     * Distinguishes unconfigured managers or transient S3 errors from missing objects.
     *
     * @param mixed $storedValue
     * @param array{permanent?: object, temporary?: object} $managers
     */
    public static function classifyCandidatePhoto($storedValue, array $managers): string
    {
        $base = self::extractBasename($storedValue);
        if ($base === '') {
            return self::STATUS_EMPTY;
        }

        $permanent = $managers['permanent'] ?? null;
        if (!is_object($permanent)) {
            return self::STATUS_STORAGE_UNAVAILABLE;
        }

        try {
            return self::probeCandidateLocations($base, $managers);
        } catch (\Throwable $e) {
            return self::STATUS_STORAGE_UNAVAILABLE;
        }
    }

    /**
     * Promote a recoverable Civil ID object from legacy or temp storage into photos/<filename>
     * with an explicit private ACL.
     *
     * @param mixed $storedValue
     * @param array{permanent?: object, temporary?: object, tempBucket?: string} $context
     */
    public static function promoteRecoverablePhoto($storedValue, array $context): bool
    {
        $status = self::classifyCandidatePhoto($storedValue, $context);
        $permanent = $context['permanent'] ?? null;
        if (!is_object($permanent)) {
            return false;
        }

        $targetKey = self::permanentKey($storedValue);
        if ($status === self::STATUS_RECOVERABLE_LEGACY) {
            return self::copyAndVerify($permanent, [
                'sourceKey' => self::legacyKey($storedValue),
                'targetKey' => $targetKey,
                'sourceBucket' => '',
            ]);
        }

        if ($status === self::STATUS_RECOVERABLE_TEMP) {
            $tempBucket = trim((string) ($context['tempBucket'] ?? ''));
            if ($tempBucket === '') {
                return false;
            }

            return self::copyAndVerify($permanent, [
                'sourceKey' => self::extractBasename($storedValue),
                'targetKey' => $targetKey,
                'sourceBucket' => $tempBucket,
            ]);
        }

        return $status === self::STATUS_PERMANENT_PRESENT;
    }

    /**
     * Check permanent, legacy, and temporary prefixes in priority order.
     */
    private static function probeCandidateLocations(string $base, array $managers): string
    {
        $permanent = $managers['permanent'];
        $temporary = $managers['temporary'] ?? null;
        if (self::objectExists($permanent, self::PERMANENT_PREFIX . $base)) {
            return self::STATUS_PERMANENT_PRESENT;
        }
        if (self::objectExists($permanent, self::LEGACY_PREFIX . $base)) {
            return self::STATUS_RECOVERABLE_LEGACY;
        }
        if (is_object($temporary) && self::objectExists($temporary, $base)) {
            return self::STATUS_RECOVERABLE_TEMP;
        }

        return self::STATUS_MISSING_NEEDS_REUPLOAD;
    }

    /**
     * Copy an object into the permanent bucket with private ACL and confirm destination presence.
     *
     * @param object $permanent
     * @param array{sourceKey: string, targetKey: string, sourceBucket: string} $transfer
     */
    private static function copyAndVerify($permanent, array $transfer): bool
    {
        if (!method_exists($permanent, 'copy')) {
            return false;
        }

        try {
            $permanent->copy(
                $transfer['sourceKey'],
                $transfer['targetKey'],
                $transfer['sourceBucket'],
                ['ACL' => 'private']
            );
            return self::objectExists($permanent, $transfer['targetKey']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check object existence using authenticated headObject when supported by the manager.
     * Rethrows transient S3/network exceptions so they are not misclassified as missing files.
     */
    private static function objectExists($manager, $key): bool
    {
        if (!is_object($manager) || $key === '') {
            return false;
        }

        try {
            if (method_exists($manager, 'storedObjectExists')) {
                return (bool) $manager->storedObjectExists($key);
            }
            if (method_exists($manager, 'fileExists')) {
                return (bool) $manager->fileExists($key);
            }
        } catch (\Throwable $e) {
            if (self::isNotFoundException($e)) {
                return false;
            }
            throw $e;
        }

        return false;
    }

    /**
     * Determine whether an exception represents a confirmed S3 404/NoSuchKey absence.
     */
    private static function isNotFoundException(\Throwable $e): bool
    {
        if ((int) $e->getCode() === 404) {
            return true;
        }

        $msg = strtolower($e->getMessage());
        return strpos($msg, 'nosuchkey') !== false
            || strpos($msg, 'notfound') !== false
            || strpos($msg, '404') !== false;
    }
}
