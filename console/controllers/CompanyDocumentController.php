<?php

namespace console\controllers;

use common\models\Company;
use Yii;
use yii\console\Controller;

/**
 * Read-only classification of stored company documents.
 * This command never updates company rows.
 */
class CompanyDocumentController extends Controller
{
    /**
     * Print how many licences and logos are permanent, legacy, or not recoverable.
     * A legacy value is reported as recoverable only when the permanent bucket
     * already has an object under the current environment prefix. The stored
     * value is left unchanged.
     *
     * @return int
     */
    public function actionRecoveryReport()
    {
        $licence = $this->emptyCounts();
        $logo = $this->emptyCounts();

        $query = Company::find()->select(['company_id', 'commercial_licence', 'company_logo']);

        foreach ($query->asArray()->each(200) as $row) {
            $this->countDocument($licence, $row['commercial_licence'], 'licence');
            $this->countDocument($logo, $row['company_logo'], 'logo');
        }

        $this->stdout("Dry run only. No company records were updated.\n");
        $this->stdout("Commercial licences: " . json_encode($licence) . "\n");
        $this->stdout("Company logos: " . json_encode($logo) . "\n");

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts()
    {
        return [
            'empty' => 0,
            'permanent' => 0,
            'legacy' => 0,
            'legacy_s3_object_present' => 0,
            'temporary' => 0,
            'unknown' => 0,
        ];
    }

    /**
     * @param array<string, int> $counts
     * @param mixed $stored
     * @param string $kind
     * @return void
     */
    private function countDocument(&$counts, $stored, $kind)
    {
        $class = Company::classifyStoredDocument($stored, $kind);
        if (!isset($counts[$class])) {
            $class = 'unknown';
        }
        $counts[$class]++;

        if ($class !== 'legacy') {
            return;
        }

        $candidate = Company::permanentDocumentPrefix($kind) . basename((string) $stored);

        try {
            if (Yii::$app->resourceManager->fileExists($candidate)) {
                $counts['legacy_s3_object_present']++;
            }
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), 'company');
        }
    }
}
