<?php

namespace common\components;

/**
 * Generates least-privilege IAM permissions boundaries, EventBridge S3 bucket-admin
 * alert rules, and redacted key-suffix audit records for StudentHub S3 buckets.
 */
final class S3SecurityBoundaryPolicy
{
    public const PERMANENT_BUCKET = 'studenthub-uploads';
    public const TEMPORARY_BUCKET = 'studenthub-public-anyone-can-upload-24hr-expiry';

    private const DENIED_BUCKET_ADMIN_ACTIONS = [
        's3:PutLifecycleConfiguration',
        's3:PutBucketLifecycleConfiguration',
        's3:DeleteBucketLifecycle',
        's3:PutBucketCors',
        's3:DeleteBucketCors',
        's3:PutBucketPolicy',
        's3:DeleteBucketPolicy',
        's3:PutReplicationConfiguration',
        's3:PutBucketReplicationConfiguration',
        's3:DeleteBucketReplication',
        's3:PutBucketLogging',
        's3:PutBucketPublicAccessBlock',
        's3:PutPublicAccessBlock',
        's3:DeletePublicAccessBlock',
        's3:PutBucketAcl',
        's3:PutBucketWebsite',
        's3:DeleteBucketWebsite',
    ];

    private const CLOUDTRAIL_ALERT_EVENT_NAMES = [
        'PutBucketLifecycleConfiguration',
        'DeleteBucketLifecycle',
        'PutBucketCors',
        'DeleteBucketCors',
        'PutBucketPolicy',
        'DeleteBucketPolicy',
        'PutBucketReplicationConfiguration',
        'PutBucketLogging',
        'PutPublicAccessBlock',
        'DeletePublicAccessBlock',
    ];

    /**
     * Build the IAM permissions boundary denying all S3 bucket-admin control-plane actions
     * while allowing scoped object operations on designated buckets.
     *
     * @param list<string> $allowedBuckets
     * @return array<string, mixed>
     */
    public static function buildServiceUserPermissionsBoundary(array $allowedBuckets = []): array
    {
        $buckets = $allowedBuckets ?: [self::PERMANENT_BUCKET, self::TEMPORARY_BUCKET];
        $bucketArns = [];
        $objectArns = [];

        foreach ($buckets as $bucket) {
            $clean = trim((string) $bucket);
            if ($clean === '') {
                continue;
            }
            $bucketArns[] = 'arn:aws:s3:::' . $clean;
            $objectArns[] = 'arn:aws:s3:::' . $clean . '/*';
        }

        return [
            'Version' => '2012-10-17',
            'Statement' => [
                [
                    'Sid' => 'DenyAllBucketAdminControlPlaneActions',
                    'Effect' => 'Deny',
                    'Action' => self::DENIED_BUCKET_ADMIN_ACTIONS,
                    'Resource' => '*',
                ],
                [
                    'Sid' => 'AllowScopedBucketObjectOperationsWithinBoundary',
                    'Effect' => 'Allow',
                    'Action' => [
                        's3:GetObject',
                        's3:PutObject',
                        's3:PutObjectAcl',
                        's3:CopyObject',
                        's3:DeleteObject',
                        's3:AbortMultipartUpload',
                        's3:ListMultipartUploadParts',
                    ],
                    'Resource' => $objectArns,
                ],
                [
                    'Sid' => 'AllowScopedBucketListingWithinBoundary',
                    'Effect' => 'Allow',
                    'Action' => ['s3:ListBucket'],
                    'Resource' => $bucketArns,
                ],
            ],
        ];
    }

    /**
     * Build an AWS EventBridge rule pattern matching S3 bucket-admin mutations in CloudTrail.
     *
     * @param list<string> $monitoredBuckets
     * @return array<string, mixed>
     */
    public static function buildBucketAdminEventBridgePattern(array $monitoredBuckets = []): array
    {
        $buckets = $monitoredBuckets ?: [self::PERMANENT_BUCKET, self::TEMPORARY_BUCKET];

        return [
            'source' => ['aws.s3'],
            'detail-type' => ['AWS API Call via CloudTrail'],
            'detail' => [
                'eventSource' => ['s3.amazonaws.com'],
                'eventName' => self::CLOUDTRAIL_ALERT_EVENT_NAMES,
                'requestParameters' => [
                    'bucketName' => array_values($buckets),
                ],
            ],
        ];
    }

    /**
     * Redact an IAM access key identifier so only its trailing suffix is emitted in audit logs.
     */
    public static function redactKeyIdentifier($accessKeyId, int $suffixLength = 4): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $accessKeyId));
        if ($clean === '') {
            return 'REDACTED';
        }

        $keep = max(4, min(5, $suffixLength));
        if (strlen($clean) <= $keep) {
            return '...' . $clean;
        }

        return '...' . substr($clean, -$keep);
    }
}
