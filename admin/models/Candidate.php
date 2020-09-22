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
        $fields['candidate_password_reset_token']);
        return $fields;
    }

    /**
     * return total number of payable candidate
     * @return array
     */
    public static function getTotalPayableCandidate(){
        $totalCandidate = 0;
        $totalAmount = 0;
        
        $transfers = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer) {
            $candidates = $transfer->getUnPaidTransferCandidates()->asArray()->all();
            $totalCandidate += count($candidates);
            $totalAmount += Candidate::calculateRemainingPaymentTransferTotal($candidates);
        }
        
        return [
            'payable' => $totalCandidate,
            'amount' => $totalAmount,
        ];
    }

    /**
     * @param bool $condition
     * @return int|string
     */
    public static function candidateCountByCondition($condition = false) {
        $query = Candidate::find();

        switch ($condition) {
            case 'assigned':
                $query->filterAssigned();
                break;
            case 'approved':
                $query->byApprovalStatus(1);
                break;
        }

        return $query->notDeleted()->count();
    }

    /**
     * @param $candidates
     * @return int
     */
    public static function calculateRemainingPaymentTransferTotal($candidates) {
        $totalAmount = 0;
        if (count($candidates)>0) {
            foreach ($candidates as $candidateTransfer) {
                $totalAmount += $candidateTransfer['bonus'] - $candidateTransfer['bonus_commission'] + ($candidateTransfer['hours'] * $candidateTransfer['candidate_hourly_rate']);
            }
        }
        return $totalAmount;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }
}
