<?php

namespace staff\models;

use Yii;


/**
 * Class TransferCandidate
 * @package staff\models
 */
class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

        $fields['status'] = function($model){
            return ($model->paid) ? 'Paid' : 'Unpaid';
        };

        $fields['total'] = function($model) {
            return ($model->candidate_hourly_rate * $model->hours) + $model->bonus - $model->bonus_commission;
        };

        $fields['tc_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_created_at, "long");
        };

        unset($fields['profit'],$fields['transfer_cost']);
    	return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\staff\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}
