<?php

namespace console\controllers;

use common\components\CivilIdRecoveryAuditor;
use common\components\S3SecurityBoundaryPolicy;
use common\models\Candidate;
use Yii;
use yii\console\Controller;

/**
 * Non-destructive audit and guardrail export command for candidate Civil ID S3 objects.
 *
 * Classifies front and back Civil ID references across permanent, legacy, and temporary
 * buckets without mass-clearing candidate database columns.
 */
final class CivilIdAuditController extends Controller
{
    /**
     * Export summary counts of candidate Civil ID front/back object locations.
     */
    public function actionAuditSummary(): int
    {
        $frontCounts = $this->emptyCounters();
        $backCounts = $this->emptyCounters();
        $managers = $this->resolveStorageManagers();

        $query = Candidate::find()
            ->select(['candidate_id', 'candidate_civil_photo_front', 'candidate_civil_photo_back'])
            ->where([
                'or',
                ['and', ['not', ['candidate_civil_photo_front' => null]], ['<>', 'candidate_civil_photo_front', '']],
                ['and', ['not', ['candidate_civil_photo_back' => null]], ['<>', 'candidate_civil_photo_back', '']],
            ]);

        foreach ($query->asArray()->each(200) as $row) {
            $frontStatus = CivilIdRecoveryAuditor::classifyCandidatePhoto($row['candidate_civil_photo_front'] ?? '', $managers);
            $backStatus = CivilIdRecoveryAuditor::classifyCandidatePhoto($row['candidate_civil_photo_back'] ?? '', $managers);
            $frontCounts[$frontStatus] = ($frontCounts[$frontStatus] ?? 0) + 1;
            $backCounts[$backStatus] = ($backCounts[$backStatus] ?? 0) + 1;
        }

        $this->stdout("Dry-run Civil ID S3 audit completed. No candidate rows were modified.\n");
        $this->stdout('Civil ID front: ' . json_encode($frontCounts) . "\n");
        $this->stdout('Civil ID back: ' . json_encode($backCounts) . "\n");

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Emit the JSON IAM service-user permissions boundary and EventBridge alert rule.
     */
    public function actionExportGuardrails(): int
    {
        $payload = [
            'permissions_boundary' => S3SecurityBoundaryPolicy::buildServiceUserPermissionsBoundary(),
            'eventbridge_rule_pattern' => S3SecurityBoundaryPolicy::buildBucketAdminEventBridgePattern(),
        ];

        $this->stdout(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Initialize zeroed classification counters for Civil ID audit reporting.
     *
     * @return array<string, int>
     */
    private function emptyCounters(): array
    {
        return [
            CivilIdRecoveryAuditor::STATUS_EMPTY => 0,
            CivilIdRecoveryAuditor::STATUS_PERMANENT_PRESENT => 0,
            CivilIdRecoveryAuditor::STATUS_RECOVERABLE_LEGACY => 0,
            CivilIdRecoveryAuditor::STATUS_RECOVERABLE_TEMP => 0,
            CivilIdRecoveryAuditor::STATUS_MISSING_NEEDS_REUPLOAD => 0,
            CivilIdRecoveryAuditor::STATUS_STORAGE_UNAVAILABLE => 0,
        ];
    }

    /**
     * Retrieve configured permanent and temporary S3 resource managers from the Yii container.
     *
     * @return array{permanent: object|null, temporary: object|null}
     */
    private function resolveStorageManagers(): array
    {
        $app = Yii::$app;

        return [
            'permanent' => ($app && $app->has('resourceManager')) ? $app->get('resourceManager') : null,
            'temporary' => ($app && $app->has('temporaryBucketResourceManager')) ? $app->get('temporaryBucketResourceManager') : null,
        ];
    }
}
