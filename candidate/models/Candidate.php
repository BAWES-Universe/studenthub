<?php
namespace candidate\models;

use common\models\CandidateToken;
use Yii;
/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @return array
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getTransferCandidate($modelClass);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate()
    {
        $status =[Transfer::STATUS_TRANSFER_COMPLETE,Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS];
        return TransferCandidate::find()
            ->leftJoin('transfer','transfer.transfer_id=transfer_candidate.transfer_id')
            ->andWhere('{{%transfer}}.transfer_status IN('.implode(',', $status).')')
            ->filterCandidate(Yii::$app->user->getId())
            ->all();
    }
}
