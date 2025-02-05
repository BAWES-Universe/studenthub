<?php
namespace company\models;

use Yii;
use yii\db\Expression;
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
            //$fields['store_id'],
            $fields['store'],
            $fields['candidate_phone'],
            $fields['candidate_email'],

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
                $fields['candidate_civil_photo_back'],
                $fields['candidate_resume'],
                $fields['candidate_latitude'],
                $fields['candidate_longitude'],
                $fields['ip_address'],
            );
        } else {
            $fields['is_our_employee'] = function($model) {
                return true;
            };
        }

        $fields['candidate_name'] = function($model){
            return $model->candidate_name? strtolower($model->candidate_name): null;
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
        /**
         * hide if not employee of logged in employer
         */
        $storeIds = ArrayHelper::getColumn (Yii::$app->storeManager->getManagedStores(), 'store_id');

        if(!in_array ($this->store_id, $storeIds)) {
            return [
                'university',
                'country',
                'area',
                'nationality',
                'candidateSkills',
                'candidateExperiences',
                'invitations',
                'invitedCount',
                'isInvitedForCompany'
            ];
        }

        return [
            "totalCandidateWorkingDate",
            "latestCandidateWorkingDate",
            "candidateWorkingDates",
            'candidateWorkingHour',
            'storeAssignmentRequest',
            'store',
            'company',
            'university',
            'country',
            'area',
            'nationality',
            'candidateSkills',
            'candidateExperiences',
            'invitations',
            'invitedCount',
            'isInvitedForCompany',
            'currentWorkHistory'
        ];
    }

    /**
     * @param string $modelClass
     * @return bool|int|string|null
     */
    public function getInvitedCount($modelClass = "\company\models\Invitation")
    {
        return (int) $this->getInvitations($modelClass)
            ->filterInvited()
            ->count();
    }

    /**
     * @return mixed
     */
    public function getIsInvitedForCompany() {
        return Yii::$app->companyManager->getCompany()->getInvitations()->andWhere(['candidate_id'=>$this->candidate_id])->exists();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\company\models\Invitation")
    {
        return parent::getInvitations($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\company\models\University")
    {
        return parent::getUniversity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\company\models\Country")
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\company\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\company\models\Bank")
    {
        return parent::getBank ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\company\models\Area")
    {
        return parent::getArea ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\company\models\Transfer")
    {
        return parent::getTransfers ($modelClass);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\CandidateToken")
    {
        return parent::getAccessTokens ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkHistory($modelClass = "\company\models\CandidateWorkHistory")
    {
        return parent::getWorkHistory ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return parent::getCurrentWorkHistory($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\company\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }

    /**
     * @param $modelClass
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getLatestCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingDate")
    {
        return self::getCandidateWorkingDates ($modelClass)
            ->one();
    }

    /**
     * @param $modelClass
     * @return bool|int|string|null
     */
    public function getTotalCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingDate")
    {
        return (int) self::getCandidateWorkingDates ($modelClass)
            ->count();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingDates($modelClass = "\common\models\CandidateWorkingDate")
    {
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");
        $session_status = Yii::$app->request->get("session_status");

        /*$company = Yii::$app->companyManager->getCompany();

        if (isset($company->subCompanies) && count($company->subCompanies)>0) {
            $query = $company
                ->getSubCompanyStores();
//                ->getSubCompanies();
        } else {
            $query = $company
                ->getStores();
        }*/

        $stores = Yii::$app->storeManager->getManagedStores();

        $storeIds = ArrayHelper::getColumn($stores, "store_id");

        $query = parent::getCandidateWorkingDates ($modelClass)
            ->andWhere(["IN", "candidate_working_date.store_id", $storeIds]);

        if (in_array($session_status, [0, 1, 2]) || $start_date || $end_date) {

            if ($session_status == \common\models\CandidateWorkingHour::STATUS_APPROVED) {
                $query->andWhere(new Expression('candidate_working_date.total_approved > 0'));
            } else if ($session_status == \common\models\CandidateWorkingHour::STATUS_REJECTED) {
                $query->andWhere(new Expression('candidate_working_date.total_rejected > 0'));
            } else if ($session_status == \common\models\CandidateWorkingHour::STATUS_PENDING) {
                $query->andWhere(new Expression('candidate_working_date.total_pending > 0'));
            }

            if ($start_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) >= DATE("'. $start_date .'")'));
            }

            if ($end_date) {
                $query->andWhere(new Expression('DATE(candidate_working_date.date) <= DATE("'. $end_date .'")'));
            }
        }

        //todo: query cache?

        return $query;
            //->orderBy("candidate_working_date.created_at DESC");
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingHour($modelClass = "\common\models\CandidateWorkingHour")
    {
        $company = Yii::$app->companyManager->getCompany();

        if (isset($company->subCompanies) && count($company->subCompanies)>0) {
            $query = $company
                ->getSubCompanyStores();
//                ->getSubCompanies();
        } else {
            $query = $company
                ->getStores();
        }

        return parent::getCandidateWorkingHour ($modelClass)
            ->andWhere(["IN", "candidate_working_hour.store_id", $query->select('store_id')])
            ->orderBy("candidate_working_hour.created_at DESC");
    }
}
