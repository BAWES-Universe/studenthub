<?php

namespace console\controllers;

use Yii;
use yii\helpers\Console;
use common\models\Candidate;


/**
 * All Resource actions related to this project
 */
class ResourceController extends \yii\console\Controller {

    /**
     * move s3 profile photos to cloudinary
     */
    public function actionS3ToCloudinary() {
        
        $query = Candidate::find()->where(['like', 'candidate_personal_photo', 'photos/']);

        $total = $query->count();
       
        Console::startProgress(0, $total);

        $n = 0;
         
        foreach ($query->batch(100) as $candidates) {
 
            foreach ($candidates as $candidate) {

                $candidate_personal_photo = $candidate->candidate_personal_photo;
                    
                //s3 bucket url 

                $url = Yii::$app->resourceManager->getUrl($candidate_personal_photo);

                if ($candidate->setProfileByUrl($url)) {
                    $candidate->save(false);
                }

                //remove S3 image from bucket

                Yii::$app->resourceManager->delete($candidate_personal_photo);

                $n++;
                
                Console::updateProgress($n, $total);
            }
        }
    }

    /**
     * add missing transfer file entries
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function populateTransferFileEntries() {

        $transfer_files = \common\models\TransferFile::find ()
            ->andWhere('transfer_file_id NOT IN (select DISTINCT(transfer_file_id) from transfer_file_entry)');

        foreach ($transfer_files->each (1) as $transfer_file) {

            $transaction = Yii::$app->db->beginTransaction ();

            if(!$transfer_file->populateEntries()) {
                $transaction->rollBack ();
                throw new \yii\web\BadRequestHttpException('Error populating entries for transfer file #' . $transfer_file->transfer_file_id);
            }

            $transaction->commit ();
        }

    }

    public $dryRun = 1;
    public $outputCsv;
    public $outputJson;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'dryRun',
            'outputCsv',
            'outputJson',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return [
            'd' => 'dryRun',
            'o' => 'outputCsv',
            'j' => 'outputJson',
        ];
    }

    /**
     * Phase 8: Missing Civil ID Data Audit & Recovery
     *
     * Audits all candidate records referencing Civil ID photos according to Issue #55:
     * - Probes permanent bucket canonical path: photos/<filename>
     * - If missing, probes legacy path: candidate-civil-id/<filename> and copies to photos/<filename>
     * - If missing, probes temporary bucket: <filename> (or photos/<filename>) and copies to permanent photos/<filename>
     * - If missing everywhere, records candidate as requiring re-upload (preserves DB fields without clearing)
     * - Generates audit report CSV and prints summary.
     *
     * @return int Exit code
     */
    public function actionAuditCivilIdRecovery()
    {
        $isDryRun = (bool)$this->dryRun;
        $modeDesc = $isDryRun ? 'DRY-RUN (Simulation — no S3 mutations)' : 'EXECUTE (Live remediation)';

        Console::output("Starting Phase 8 Civil ID S3 Audit & Recovery [{$modeDesc}]...");

        $sql = "SELECT candidate_id, 'front' AS side, candidate_civil_photo_front AS filename,
          CONCAT('photos/', candidate_civil_photo_front) AS expected_s3_key, candidate_updated_at
        FROM candidate
        WHERE candidate_civil_photo_front IS NOT NULL AND candidate_civil_photo_front <> ''
        UNION ALL
        SELECT candidate_id, 'back' AS side, candidate_civil_photo_back AS filename,
          CONCAT('photos/', candidate_civil_photo_back) AS expected_s3_key, candidate_updated_at
        FROM candidate
        WHERE candidate_civil_photo_back IS NOT NULL AND candidate_civil_photo_back <> ''
        ORDER BY candidate_updated_at DESC";

        $rows = Yii::$app->db->createCommand($sql)->queryAll();
        $total = count($rows);

        Console::output("Found {$total} candidate Civil ID photo references to evaluate.\n");

        if ($total === 0) {
            Console::output("No candidate Civil ID records found to audit.");
            return 0;
        }

        $permBucket = Yii::$app->resourceManager->bucket;
        $tempBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $stats = [
            'total'            => $total,
            'ok_canonical'     => 0,
            'recovered_legacy' => 0,
            'recovered_temp'   => 0,
            'missing_reupload' => 0,
            'errors'           => 0,
        ];
        $report = [];

        Console::startProgress(0, $total);
        $n = 0;

        foreach ($rows as $row) {
            $n++;
            $candidateId = (string)($row['candidate_id'] ?? '');
            $side        = (string)($row['side'] ?? '');
            $rawFilename = trim((string)($row['filename'] ?? ''));
            $updatedAt   = (string)($row['candidate_updated_at'] ?? '');

            $normKey = Candidate::normalizeCivilIdPermanentS3Key($rawFilename);

            if ($normKey === '') {
                $stats['errors']++;
                $report[] = [
                    'candidate_id'         => $candidateId,
                    'side'                 => $side,
                    'raw_filename'         => $rawFilename,
                    'clean_basename'       => '',
                    'expected_s3_key'      => '',
                    'candidate_updated_at' => $updatedAt,
                    'status'               => 'ERROR',
                    'action_taken'         => 'Empty or invalid filename',
                    'source_location'      => '',
                    'target_location'      => '',
                ];
                Console::updateProgress($n, $total);
                continue;
            }

            $basename = (strpos($normKey, 'photos/') === 0)
                ? substr($normKey, strlen('photos/'))
                : basename($normKey);

            $canonicalKey = 'photos/' . $basename;
            $legacyKey    = 'candidate-civil-id/' . $basename;

            $status         = null;
            $actionTaken    = '';
            $sourceLocation = '';
            $targetLocation = "s3://{$permBucket}/{$canonicalKey}";

            // Step 1: Probe canonical location in permanent bucket
            try {
                if (Yii::$app->resourceManager->fileExists($canonicalKey)) {
                    $status         = 'OK_CANONICAL';
                    $actionTaken    = 'Present at canonical path; no action required';
                    $sourceLocation = "s3://{$permBucket}/{$canonicalKey}";
                    $stats['ok_canonical']++;
                }
            } catch (\Throwable $e) {
                // S3 check error
            }

            // Step 2: Probe legacy prefix in permanent bucket
            if ($status === null) {
                try {
                    if (Yii::$app->resourceManager->fileExists($legacyKey)) {
                        $sourceLocation = "s3://{$permBucket}/{$legacyKey}";
                        if ($isDryRun) {
                            $status      = 'RECOVERED_LEGACY';
                            $actionTaken = "[DRY-RUN] Would copy from {$legacyKey} to {$canonicalKey}";
                        } else {
                            Yii::$app->resourceManager->copy($legacyKey, $canonicalKey, $permBucket);
                            $status      = 'RECOVERED_LEGACY';
                            $actionTaken = "Recovered: copied from legacy {$legacyKey} to {$canonicalKey}";
                        }
                        $stats['recovered_legacy']++;
                    }
                } catch (\Throwable $e) {
                    $status      = 'ERROR';
                    $actionTaken = "Error copying legacy object: " . $e->getMessage();
                    $stats['errors']++;
                }
            }

            // Step 3: Probe temporary upload bucket
            if ($status === null) {
                $tempProbes = [$basename, $canonicalKey];
                $foundTempKey = null;

                foreach ($tempProbes as $probeKey) {
                    try {
                        if (Yii::$app->temporaryBucketResourceManager->fileExists($probeKey)) {
                            $foundTempKey = $probeKey;
                            break;
                        }
                    } catch (\Throwable $e) {
                        // S3 temp probe error
                    }
                }

                if ($foundTempKey !== null) {
                    $sourceLocation = "s3://{$tempBucket}/{$foundTempKey}";
                    if ($isDryRun) {
                        $status      = 'RECOVERED_TEMP';
                        $actionTaken = "[DRY-RUN] Would copy from temp bucket {$tempBucket}/{$foundTempKey} to permanent {$permBucket}/{$canonicalKey}";
                    } else {
                        try {
                            Yii::$app->resourceManager->copy($foundTempKey, $canonicalKey, $tempBucket);
                            $status      = 'RECOVERED_TEMP';
                            $actionTaken = "Recovered: copied from temp bucket {$tempBucket}/{$foundTempKey} to permanent {$permBucket}/{$canonicalKey}";
                        } catch (\Throwable $e) {
                            $status      = 'ERROR';
                            $actionTaken = "Error copying temp object: " . $e->getMessage();
                            $stats['errors']++;
                        }
                    }
                    if ($status === 'RECOVERED_TEMP') {
                        $stats['recovered_temp']++;
                    }
                }
            }

            // Step 4: Missing across all locations -> flag for re-upload (DB fields preserved)
            if ($status === null) {
                $status      = 'MISSING_REUPLOAD_REQUIRED';
                $actionTaken = 'Missing across canonical, legacy, and temp storage; flagged for candidate re-upload (DB preserved)';
                $stats['missing_reupload']++;
            }

            $report[] = [
                'candidate_id'         => $candidateId,
                'side'                 => $side,
                'raw_filename'         => $rawFilename,
                'clean_basename'       => $basename,
                'expected_s3_key'      => $canonicalKey,
                'candidate_updated_at' => $updatedAt,
                'status'               => $status,
                'action_taken'         => $actionTaken,
                'source_location'      => $sourceLocation,
                'target_location'      => $targetLocation,
            ];

            Console::updateProgress($n, $total);
        }

        Console::endProgress();

        // Print Summary Table
        Console::output("\n" . str_repeat('=', 70));
        Console::output("CIVIL ID S3 AUDIT & RECOVERY SUMMARY [{$modeDesc}]");
        Console::output(str_repeat('=', 70));
        Console::output(sprintf("  Total Evaluated Records     : %6d", $stats['total']));
        Console::output(sprintf("  OK (Canonical S3 Exists)    : %6d (%5.1f%%)", $stats['ok_canonical'], $stats['total'] ? ($stats['ok_canonical'] / $stats['total'] * 100) : 0));
        Console::output(sprintf("  Recovered from Legacy Prefix: %6d (%5.1f%%)", $stats['recovered_legacy'], $stats['total'] ? ($stats['recovered_legacy'] / $stats['total'] * 100) : 0));
        Console::output(sprintf("  Recovered from Temp Bucket  : %6d (%5.1f%%)", $stats['recovered_temp'], $stats['total'] ? ($stats['recovered_temp'] / $stats['total'] * 100) : 0));
        Console::output(sprintf("  Missing (Re-upload Flagged) : %6d (%5.1f%%)", $stats['missing_reupload'], $stats['total'] ? ($stats['missing_reupload'] / $stats['total'] * 100) : 0));
        if ($stats['errors'] > 0) {
            Console::output(sprintf("  Errors Encountered          : %6d (%5.1f%%)", $stats['errors'], $stats['total'] ? ($stats['errors'] / $stats['total'] * 100) : 0));
        }
        Console::output(str_repeat('=', 70));
        if ($stats['missing_reupload'] > 0) {
            Console::output("NOTICE: {$stats['missing_reupload']} records require candidate re-upload.");
            Console::output("        Per Phase 8 rules, database fields remain preserved (NOT cleared).");
            Console::output(str_repeat('=', 70));
        }

        // Export CSV if configured or default to console/runtime/civil_id_audit_report.csv
        $csvPath = $this->outputCsv ?: Yii::getAlias('@console/runtime/civil_id_audit_report.csv');
        $csvDir = dirname($csvPath);
        if (!is_dir($csvDir)) {
            @mkdir($csvDir, 0777, true);
        }

        $fp = @fopen($csvPath, 'w');
        if ($fp) {
            fputcsv($fp, [
                'candidate_id',
                'side',
                'raw_filename',
                'clean_basename',
                'expected_s3_key',
                'candidate_updated_at',
                'status',
                'action_taken',
                'source_location',
                'target_location',
            ]);
            foreach ($report as $r) {
                fputcsv($fp, $r);
            }
            fclose($fp);
            Console::output("Full audit report written to: {$csvPath}");
        }

        // Export separate re-upload CSV
        $reuploadPath = dirname($csvPath) . '/candidates_requiring_reupload.csv';
        $fpReupload = @fopen($reuploadPath, 'w');
        if ($fpReupload) {
            fputcsv($fpReupload, [
                'candidate_id',
                'side',
                'raw_filename',
                'clean_basename',
                'expected_s3_key',
                'candidate_updated_at',
                'status',
                'action_taken',
            ]);
            foreach ($report as $r) {
                if ($r['status'] === 'MISSING_REUPLOAD_REQUIRED') {
                    fputcsv($fpReupload, [
                        $r['candidate_id'],
                        $r['side'],
                        $r['raw_filename'],
                        $r['clean_basename'],
                        $r['expected_s3_key'],
                        $r['candidate_updated_at'],
                        $r['status'],
                        $r['action_taken'],
                    ]);
                }
            }
            fclose($fpReupload);
            Console::output("Re-upload candidates list written to: {$reuploadPath}");
        }

        return 0;
    }
}

