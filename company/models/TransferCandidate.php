<?php
namespace company\models;

use Yii;


class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

        // Hide Sensitive Data // $fields['transfer_cost'],
    	unset($fields['candidate_total'], $fields['total_amount'],
            $fields['candidate_hourly_rate'], $fields['deleted'], $fields['profit'],
            $fields['tc_created_at'], $fields['tc_updated_at']);

        // Display related Candidate
        $fields['candidate'] = function($model) {
    		return $model->candidate;
    	};

    	return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass= "\company\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass= "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass= "\company\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass= "\company\models\Transfer")
    {
        return parent::getTransfer($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\company\models\Invoice")
    {
        return parent::getInvoice($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\company\models\Bank")
    {
        return parent::getBank($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFile($modelClass = "\company\models\TransferFile")
    {
        return parent::getTransferFile($modelClass);
    }
}
