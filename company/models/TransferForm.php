<?php
namespace company\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "Transfer".
 * It extends from \company\models\Transfer but with custom functionality for this application module
 */
class TransferForm extends \company\models\Transfer {

	public $candidates = [];

	/**
     * @inheritdoc
     */
    public function rules()
    {
    	$rules = parent::rules();
    	
    	$rules[] = ['candidates', 'validateCandidates'];

        return $rules;
    }

    /**
     * Static function to validate candidate array to initiate transfer
     * @param $attribute
     * @param $attribute
     * @param $validator
     * @return null
     */
    public function validateCandidates($attribute, $params, $validator)
    {
        $errors = [];
        $total = 0;
        $company_total = 0;
        if(!is_array($this->candidates)) {
            $this->candidates = [];
        }

        // check if empty field
        foreach ($this->candidates as $key => $value)
        {
            $bonus = (isset($value['bonus'])) ? $value['bonus'] : 0;
            $hours = (isset($value['hours'])) ? $value['hours'] : 0;
            
            if($hours < 0)
            {
                $this->addError($attribute, 'Hours can not be negative');
            }

            if($bonus < 0)
            {
                $this->addError($attribute, 'Bonus can not be negative');
            }

            if(empty($value['candidate_id']))
            {
                $this->addError($attribute, 'Candidate field require.');
            }

            $company_total += $bonus + ($hours * Yii::$app->params['candidate_max_hourly_rate']);
        }

        // Case where transfer total is zero/empty
        if ($company_total == 0) {
            $this->addError($attribute, "Transfer total is zero. Please input the actual hours worked.");
        }

        // Get list of all subcompanies belonging to this company.
        $companies = Company::findAll(['parent_company_id' => $this->company_id]);
        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');
        $company_ids[] = $this->company_id;

        // Use subcompany list to Get list of all stores belonging to the parent company
        $stores = Store::find()
            ->where(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

        // Find all candidates that work in stores belonging to company but not included in candidate list
        // that is being validated. Show error if any missing
        $candidate_ids = ArrayHelper::map($this->candidates, 'candidate_id', 'candidate_id');
        $missing = Candidate::find()
            ->where(['in', 'store_id', $store_ids])
            ->andWhere(['NOT IN', 'candidate_id', $candidate_ids])
            ->count();

        if($missing > 0)
        {
            $this->addError($attribute, 'Missing ' . $missing . ' candidate(s).');
        }
    }
}