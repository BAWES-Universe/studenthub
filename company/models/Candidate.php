<?php
namespace company\models;

use Yii;
use yii\helpers\ArrayHelper;


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
            $fields['candidate_updated_at'],
            $fields['candidate_hourly_rate'],
            $fields['bank_id'],
            $fields['candidate_iban'],
            $fields['candidate_uid'],
            $fields['bank_account_name'],
            $fields['approved'],
            $fields['deleted'],
            $fields['candidate_status'],
            $fields['employee_id'],
            $fields['candidate_email_verification'],
            $fields['candidate_limit_email'],
            $fields['candidate_new_email'],
            $fields['bank_account_name'],
            $fields['bank_id'],
        );

        /**
         * hide if not employee of logged in employer
         */
        $storeIds = ArrayHelper::getColumn (Yii::$app->storeManager->getManagedStores(), 'store_id');

        if(!in_array ($this->store_id, $storeIds)) {
            unset(
                $fields['candidate_phone'],
                $fields['candidate_email'],
                $fields['candidate_civil_photo_front'],
                $fields['candidate_civil_photo_back']
            );
        }

        $fields['candidate_name'] = function($model){
            return strtolower($model->candidate_name);
        };
        
        // Clear bank info from array
        $fields['bank'] = function() {return [];};
        
        return $fields;
    }    
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'store',
            'company',
            'university',
            'country',
            'area',
            'nationality',
            'candidateSkills',
            'candidateExperiences',
        ];
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\common\models\University")
    {
        return parent::getUniversity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\company\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCard($modelClass = "\common\models\CandidateIdCard")
    {
        return parent::getCandidateIdCard($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCards($modelClass = "\common\models\CandidateIdCard")
    {
        return parent::getCandidateIdCards($modelClass);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateSkills($modelClass = "\common\models\CandidateSkill")
    {
        return parent::getCandidateSkills($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateExperiences($modelClass = "\common\models\CandidateExperience")
    {
        return parent::getCandidateExperiences($modelClass);
    }
}
