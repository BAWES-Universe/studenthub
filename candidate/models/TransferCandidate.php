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
        unset(
            $fields['store_id'],
            $fields['company_id'],
            $fields['company_email'],
            $fields['company_hourly_rate'],
            $fields['bonus'],    
            $fields['bonus_commission'],    
            $fields['transfer_cost'],
            $fields['tc_updated_at'],
            $fields['total_amount'],
            $fields['company_total'],
            $fields['profit'],
            $fields['paid'],
            $fields['total_paid']
        );
        
        $fields['status'] = function($model){
            return ($model->paid) ? 'Paid' : 'Unpaid';
        };

        $fields['total'] = function($model) {
            return $model->candidate_total;
            //($model->candidate_hourly_rate * $model->hours) + $model->bonus - $model->bonus_commission;
        };

       /* $fields['candidate_bonus'] = function($model) {
            return $model->bonus - $model->bonus_commission;
        };*/

        $fields['tc_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_created_at, "long");
        };

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\candidate\models\Transfer")
    {
        return parent::getTransfer($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\candidate\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\common\models\Invoice")
    {
        return parent::getInvoice($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\candidate\models\Bank")
    {
        return parent::getBank($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFile($modelClass = "\common\models\TransferFile")
    {
        return parent::getTransferFile($modelClass);
    }
}
