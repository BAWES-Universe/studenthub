<?php

namespace candidate\models;

use Yii;


/**
 * Class TransferCandidate
 */
class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['tc_id'],
            $fields['store_id'],
            $fields['store_name'],
            $fields['company_id'],
            $fields['company_name'],
            $fields['company_email'],
            $fields['company_hourly_rate'],
            $fields['bonus'],    
            $fields['bonus_commission'],    
            $fields['transfer_cost'],
            $fields['tc_updated_at'],
            $fields['total_amount'],
            $fields['profit'],
            $fields['paid'],
            $fields['total_paid']
        );
        
        $fields['status'] = function($model){
            return ($model->paid) ? 'Paid' : 'Unpaid';
        };

        $fields['total'] = function($model) {
            return ($model->candidate_hourly_rate * $model->hours) + $model->bonus - $model->bonus_commission;
        };

        $fields['tc_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_created_at, "long");
        };

        return $fields;
    }

    public function getTransfer($modelClass = "\candidate\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }
}
