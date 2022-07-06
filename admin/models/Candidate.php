<?php
namespace admin\models;

use Yii;


/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_auth_key'],
        $fields['candidate_password_hash'],
        $fields['candidate_password_reset_token']);
        $fields['deleted'] = function($model){
            return $model->deleted;
        };
        $fields['candidate_name'] = function($model){
            return strtolower($model->candidate_name);
        };
        return $fields;
    }

    /**
     * return total number of payable candidate
     * @return array
     */
    public static function getTotalPayableCandidate(){
        $totalCandidate = 0;
        $totalAmount = 0;
        
        $transfers = Transfer::find()
            ->andWhere(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer) {
            $candidates = $transfer->getUnPaidTransferCandidates()
                ->asArray()
                ->all();

            $totalCandidate += count($candidates);
            $totalAmount += Candidate::calculateRemainingPaymentTransferTotal($candidates);
        }
        
        return [
            'payable' => $totalCandidate,
            'amount' => $totalAmount,
        ];
    }

    /**
     * @param bool $condition
     * @return int|string
     */
    public static function candidateCountByCondition($condition = false) {
        $query = Candidate::find();

        switch ($condition) {
            case 'assigned':
                $query->filterAssigned();
                break;
            case 'approved':
                $query->byApprovalStatus(1);
                break;
        }
        $query->andWhere(['deleted' => 0]);
        return $query->count();
    }

    /**
     * @param $candidates
     * @return int
     */
    public static function calculateRemainingPaymentTransferTotal($candidates) {

        if(!isset(Yii::$app->params['transfer_cost'])) {
            Yii::$app->params['transfer_cost'] = 0;
        }

        $totalAmount = 0;

        if (count($candidates)>0) {
            foreach ($candidates as $candidateTransfer) {
                $totalAmount += round(
                    Yii::$app->params['transfer_cost'] + $candidateTransfer['bonus'] - $candidateTransfer['bonus_commission'] +
                    ($candidateTransfer['hours'] * $candidateTransfer['candidate_hourly_rate']),
                3
                );
            }
        }

        return $totalAmount;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation")
    {
        return parent::getInvitations($modelClass);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\admin\models\University")
    {
        return parent::getUniversity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\admin\models\Country")
    {
        return parent::getNationality ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\admin\models\Country")
    {
        return parent::getCountry ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\common\models\Bank")
    {
        return parent::getBank ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\admin\models\Area")
    {
        return parent::getArea ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\admin\models\Store")
    {
        return parent::getStore ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getTransfers ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getTransferCandidate ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCard($modelClass = "\common\models\CandidateIdCard")
    {
        return parent::getCandidateIdCard ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCards($modelClass = "\common\models\CandidateIdCard")
    {
        return parent::getCandidateIdCards ($modelClass);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\admin\models\CandidateToken")
    {
        return parent::getAccessTokens ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return parent::getWorkHistory ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateSkills($modelClass = "\common\models\CandidateSkill")
    {
        return parent::getCandidateSkills ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateExperiences($modelClass = "\common\models\CandidateExperience")
    {
        return parent::getCandidateExperiences ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\admin\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }
}
