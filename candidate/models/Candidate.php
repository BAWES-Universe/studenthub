<?php
namespace candidate\models;

use Yii;


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
        $fields['candidate_updated_at']);

        $fields['bank_account_needed'] = function($model) {
            #https://www.pivotaltracker.com/story/show/177527467
            if ($model->bank) {
                return 0;
            }

            $unpaidTransfer = $model->getTransferCandidate()->andWhere(['paid'=>0,'deleted'=>0])->exists();
            if ($model->store_id || $unpaidTransfer) {
                return 1;
            }   else {
                return 0;
            }
        };

        $fields['working_hour_count'] = function($model) {
            return $model->getCandidateWorkingHour()->count();
        };
        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\candidate\models\Invitation")
    {
        return parent::getInvitations($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getTransferCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\candidate\models\University")
    {
        return parent::getUniversity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\candidate\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\common\models\Bank")
    {
        return parent::getBank($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
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
        return parent::getCandidateIdCard($modelClass);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\CandidateToken")
    {
        return parent::getAccessToken($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return parent::getWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateSkills($modelClass = "\candidate\models\CandidateSkill")
    {
        return parent::getCandidateSkills($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateExperiences($modelClass = "\candidate\models\CandidateExperience")
    {
        return parent::getCandidateExperiences($modelClass);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $token = CandidateToken::find()
            ->andWhere(['token_value' => $token])
            ->with('candidate')
            ->one();

        if($token) {
            return $token->candidate;
        }
    }

    public function getNationality($modelClass = "\candidate\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\candidate\models\Area")
    {
        return parent::getArea ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\candidate\models\Transfer")
    {
        return parent::getTransfers ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\candidate\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }
}
