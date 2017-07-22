<?php

namespace candidate\models;

use Yii;

/**
 * Class TransferCandidate
 * @package candidate\models
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
            return ($model->candidate_hourly_rate * $model->hours) + $model->bonus;
        };
        
        $fields['company_name'] = function($model) {
            if (isset($model->transfer->company->company_name)) {
                return $model->transfer->company->company_name;
            } else {
                return '';
            }
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
