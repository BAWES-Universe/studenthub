<?php
namespace company\models;

class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

        // Hide Sensitive Data
    	unset($fields['total_amount'], $fields['transfer_cost'],
            $fields['candidate_hourly_rate'], $fields['deleted'], $fields['profit'],
            $fields['tc_created_at'], $fields['tc_updated_at']);

        // Display related Candidate
        $fields['candidate'] = function($model) {
    		return $model->candidate;
    	};

    	return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasOne(Candidate::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['transfer_id' => 'transfer_id']);
    }
}
