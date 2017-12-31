<?php
namespace company\models;

use Yii;
use company\models\Company;

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

    public static function saveCandidateTransfer($candidate, $model, $value) {

        $total = 0;
        $company_total = 0;

        $hourly_rate = $candidate['candidate_hourly_rate'];

        $store = $candidate['store'];
        $company = $candidate['company'];
        $TCModel = new TransferCandidate;
        $TCModel->transfer_cost = Yii::$app->params['transfer_cost'];
        $TCModel->candidate_hourly_rate = $hourly_rate;
        $TCModel->attributes = $value;
        $TCModel->transfer_id = $model->transfer_id;
        $TCModel->store_id = $candidate['store_id'];
        $TCModel->store_name = $store['store_name'];
        $TCModel->company_id = $store['company_id'];
        $TCModel->company_name = $company['company_name'];
        $TCModel->company_email = $company['company_email'];

        $company_bonus_commission = $company['company_bonus_commission'];
        $company_hourly_rate = $company['company_hourly_rate'];
        
        //if value not set take from parent company 
        
        if(($company_bonus_commission + $company_hourly_rate == 0) && $company['parent_company_id'])
        {
            $parent = Company::findOne(['company_id' => $company['parent_company_id']]);
            
            if(!$parent)
            {
                return [
                    "operation" => "error",
                    "message" => "Parent not found."
                ];
            }
            
            $company_bonus_commission = $parent['company_bonus_commission'];
            $company_hourly_rate = $parent['company_hourly_rate'];
        }
        
        //if bonus commission or hourly rate not set 
        
        if($company_bonus_commission == 0 && $company_hourly_rate == 0) {
            return [
                "operation" => "error",
                "message" => "Company hourly rate not set, please contact us for assistance"
            ];
        }            
        
        //calculate and save bonus_commission 
        
        $TCModel->bonus_commission = $value['bonus'] * $company_bonus_commission / 100;
                
        $TCModel->company_hourly_rate = $company_hourly_rate;
        
        if ((int)$value['hours']>0 || $value['bonus'] > 0) {
            $total = $value['bonus'] - $TCModel->bonus_commission + ($value['hours'] * $hourly_rate) + Yii::$app->params['transfer_cost'];
            $company_total = $value['bonus'] + ($value['hours'] * $company_hourly_rate);
        }
        
        // in case if amount is less then 0 so that it should not show in payable candidate area
        if ($total  == 0) {
            $TCModel->paid = TransferCandidate::PAID;
        }

        if (!$TCModel->save()) {

            if(isset($TCModel->errors)){
                return [
                    "operation" => "error",
                    "message" => $TCModel->errors
                ];
            }

            return [
                "operation" => "error",
                "message" => "We've faced an issue saving your request, please contact us for assistance."
            ];
        }
        return [
            "operation" => "success",
            "total" => $total,
            "company_total" => $company_total
        ];
    }
}
