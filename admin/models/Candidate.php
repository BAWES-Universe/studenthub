<?php
namespace admin\models;

/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_auth_key'],
        $fields['candidate_password_hash'],
        $fields['candidate_password_reset_token'],
        $fields['candidate_created_at'],
        $fields['candidate_updated_at']);
        return $fields;
    }

    /**
     * return total number of payable candidate
     * @return int
     */
    public static function getTotalPayableCandidate(){
        $candidates = 0;
        $transfers = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer) {
            $candidates += $transfer->getTransferCandidates()->where(['paid' => '0'])->count();
        }
        return $candidates;
    }
}
