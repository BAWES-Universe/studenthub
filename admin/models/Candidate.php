<?php
namespace admin\models;

use common\models\MailLog;
use Yii;
use yii\db\Expression;
use yii\helpers\Url;


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
     * @param $currency_code
     * @return array
     */
    public static function getTotalPayableCandidate($currency_code = "KWD") {

        $totalCandidate = 0;
        $totalAmount = 0;

        $query = Transfer::find()
            ->andWhere(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->isParentTransfer();

        if($currency_code) {
            $query->andWhere(['transfer.currency_code' => $currency_code]);
        }

        $transfers = $query->all();

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
     * @param false $condition
     * @param null $startDate
     * @param null $endDate
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function candidateCountByCondition($condition = false, $startDate = null, $endDate = null, $currency_code = "KWD") {
        $query = Candidate::find();
        /*
            ->andWhere([
                '{{%candidate}}.deleted' => 0
            ]);*/

        switch ($condition) {
            case 'assigned':
                $query->filterAssigned();
                $query->filterByJoiningDate($startDate, $endDate);
                break;
            case 'approved':
                $query->byApprovalStatus(1);
                break;
        }

        if($startDate) {
            $query->andWhere(new Expression("DATE(candidate_created_at) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $query->andWhere(new Expression("DATE(candidate_created_at) <= DATE('" . $endDate . "')"));
        }

        if($currency_code) {
            $query->andWhere(['candidate.currency_code' => $currency_code]);
        }

//        return $query->getSqlQuery();
        return $query->count();
    }

    /**
     * @param false $condition
     * @param null $startDate
     * @param null $endDate
     * @param $currency_code
     * @return bool|int|string|null
     */
     public static function candidateCountByAssigned($startDate = null, $endDate = null, $currency_code = "KWD") {

        $query = Candidate::find()
            ->filterAssigned()
            ->filterByJoiningDate($startDate, $endDate);
//        return $query->getSqlQuery();

         if($currency_code) {
             $query->andWhere(['candidate.currency_code' => $currency_code]);
         }

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
                //$candidateTransfer['transfer_cost']
                $totalAmount += $candidateTransfer['candidate_total'];/* round(
                    $candidateTransfer['bonus']
                    - $candidateTransfer['bonus_commission'] +
                    (
                        $candidateTransfer['hours'] * $candidateTransfer['candidate_hourly_rate']
                    ),
                3
                );*/
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

    /**
     * its experiment
     * @return query\CandidateQuery
     */
    public static function findCustom()
    {
        return new \admin\models\query\CandidateQuery(get_called_class());
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function invited($startDate = null, $endDate = null, $currency_code = "KWD") {
        $query = Invitation::find();

        if($currency_code) {
            $query->joinWith(['company'])
                ->andWhere(['company.currency_code' => $currency_code]);
        }

        if($startDate) {
             $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('" . $startDate . "')"));
         }

        if($endDate) {
            $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('" . $endDate . "')"));
        }
        return $query->count();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function suggested($startDate = null, $endDate = null, $currency_code = "KWD") {

        $query = Suggestion::find();

        if($currency_code) {
            $query->joinWith(['company'])
                ->andWhere(['company.currency_code' => $currency_code]);
        }

        if($startDate) {
            $query->andWhere(new Expression("DATE(suggestion_datetime) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $query->andWhere(new Expression("DATE(suggestion_datetime) <= DATE('" . $endDate . "')"));
        }

        return $query->count();
    }

    /**
     * Send new password to customer
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        if(!$model->candidate_email_verification)
            return false;

        $ml = new MailLog();
        $ml->to = $model->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Your account password has been reset';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("candidate/candidate-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->candidate_email)
            ->setSubject('Your account password has been reset');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }
}
