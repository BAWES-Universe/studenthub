<?php

namespace admin\models;

/**
 * Class TransferCandidate
 * @package admin\models
 */
class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

    	$fields['candidate'] = function($model) {
    		return $model->candidate;
    	};

    	unset($fields['deleted']);

    	return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\admin\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\admin\models\Transfer")
    {
        return parent::getTransfer($modelClass);;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\admin\models\Invoice")
    {
        return parent::getInvoice($modelClass);
    }
}
