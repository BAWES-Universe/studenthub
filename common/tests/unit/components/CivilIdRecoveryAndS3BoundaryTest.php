<?php

namespace common\tests\unit\components;

use Codeception\Test\Unit;
use common\components\CivilIdRecoveryAuditor;
use common\components\S3SecurityBoundaryPolicy;

/**
 * Unit tests for non-destructive Civil ID S3 recovery auditing and IAM/EventBridge guardrails.
 */
final class CivilIdRecoveryAndS3BoundaryTest extends Unit
{
    /**
     * Build an in-memory mock storage manager backed by a key set and copy log.
     *
     * @param list<string> $initialKeys
     */
    private function createFakeStorageManager(array $initialKeys): object
    {
        return new class($initialKeys) {
            /** @var array<string, bool> */
            public array $keys = [];

            /** @var list<array{from: string, to: string, bucket: string}> */
            public array $copied = [];

            public function __construct(array $seed)
            {
                foreach ($seed as $key) {
                    $this->keys[$key] = true;
                }
            }

            public function storedObjectExists($key): bool
            {
                return isset($this->keys[(string) $key]);
            }

            public function copy($oldFile, $newFile, $sourceBucket = ''): bool
            {
                $this->copied[] = [
                    'from' => (string) $oldFile,
                    'to' => (string) $newFile,
                    'bucket' => (string) $sourceBucket,
                ];
                $this->keys[(string) $newFile] = true;

                return true;
            }
        };
    }

    public function testClassifiesPermanentLegacyTemporaryAndMissingCivilIdFiles(): void
    {
        $permanent = $this->createFakeStorageManager([
            'photos/front-ok.jpg',
            'candidate-civil-id/legacy-back.png',
        ]);
        $temporary = $this->createFakeStorageManager([
            'recent-temp.jpg',
        ]);
        $managers = ['permanent' => $permanent, 'temporary' => $temporary];

        $this->assertSame(
            CivilIdRecoveryAuditor::STATUS_EMPTY,
            CivilIdRecoveryAuditor::classifyCandidatePhoto('', $managers)
        );
        $this->assertSame(
            CivilIdRecoveryAuditor::STATUS_PERMANENT_PRESENT,
            CivilIdRecoveryAuditor::classifyCandidatePhoto('photos/front-ok.jpg', $managers)
        );
        $this->assertSame(
            CivilIdRecoveryAuditor::STATUS_RECOVERABLE_LEGACY,
            CivilIdRecoveryAuditor::classifyCandidatePhoto('legacy-back.png', $managers)
        );
        $this->assertSame(
            CivilIdRecoveryAuditor::STATUS_RECOVERABLE_TEMP,
            CivilIdRecoveryAuditor::classifyCandidatePhoto('recent-temp.jpg', $managers)
        );
        $this->assertSame(
            CivilIdRecoveryAuditor::STATUS_MISSING_NEEDS_REUPLOAD,
            CivilIdRecoveryAuditor::classifyCandidatePhoto('deleted-by-lifecycle.jpg', $managers)
        );
    }

    public function testPromotesLegacyAndTemporaryFilesIntoPermanentPhotosPrefix(): void
    {
        $permanent = $this->createFakeStorageManager([
            'candidate-civil-id/recover-me.jpg',
        ]);
        $temporary = $this->createFakeStorageManager([
            'from-temp-window.png',
        ]);
        $context = [
            'permanent' => $permanent,
            'temporary' => $temporary,
            'tempBucket' => S3SecurityBoundaryPolicy::TEMPORARY_BUCKET,
        ];

        $this->assertTrue(CivilIdRecoveryAuditor::promoteRecoverablePhoto('recover-me.jpg', $context));
        $this->assertTrue($permanent->storedObjectExists('photos/recover-me.jpg'));

        $this->assertTrue(CivilIdRecoveryAuditor::promoteRecoverablePhoto('from-temp-window.png', $context));
        $this->assertTrue($permanent->storedObjectExists('photos/from-temp-window.png'));
        $this->assertFalse(CivilIdRecoveryAuditor::promoteRecoverablePhoto('nowhere.jpg', $context));
    }

    public function testPermissionsBoundaryDeniesAllBucketAdminControlPlaneActions(): void
    {
        $policy = S3SecurityBoundaryPolicy::buildServiceUserPermissionsBoundary();
        $denyStatement = $policy['Statement'][0];

        $this->assertSame('Deny', $denyStatement['Effect']);
        $this->assertSame('*', $denyStatement['Resource']);
        $this->assertContains('s3:PutLifecycleConfiguration', $denyStatement['Action']);
        $this->assertContains('s3:PutBucketLifecycleConfiguration', $denyStatement['Action']);
        $this->assertContains('s3:PutBucketCors', $denyStatement['Action']);
        $this->assertContains('s3:PutBucketPolicy', $denyStatement['Action']);
        $this->assertContains('s3:PutPublicAccessBlock', $denyStatement['Action']);
    }

    public function testEventBridgePatternAndKeySuffixRedactionFollowAuditRules(): void
    {
        $pattern = S3SecurityBoundaryPolicy::buildBucketAdminEventBridgePattern();
        $events = $pattern['detail']['eventName'];
        $buckets = $pattern['detail']['requestParameters']['bucketName'];

        $this->assertContains('PutBucketLifecycleConfiguration', $events);
        $this->assertContains('DeleteBucketCors', $events);
        $this->assertSame(
            [S3SecurityBoundaryPolicy::PERMANENT_BUCKET, S3SecurityBoundaryPolicy::TEMPORARY_BUCKET],
            $buckets
        );
        $this->assertSame('...FZMN', S3SecurityBoundaryPolicy::redactKeyIdentifier('EXAMPLEKEYFZMN', 4));
        $this->assertSame('...4T67K', S3SecurityBoundaryPolicy::redactKeyIdentifier('EXAMPLEKEY4T67K', 5));
    }
}
