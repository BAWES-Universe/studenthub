<?php

namespace admin\models;

use common\models\Staff;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


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

        $fields['status'] = function($model){
            return ($model->paid) ? 'Paid' : 'Unpaid';
        };
        
        $fields['paid'] = function($model){
            return (int)$model->paid;
        };      

        //total amount candidate will receive 
        $fields['total'] = function($model) {
            return ($model->candidate_hourly_rate * $model->hours) + $model->bonus - $model->bonus_commission;
        };

        $fields['tc_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_created_at, "long");
        };

        $fields['tc_updated_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_updated_at, "long");
        };

    	return $fields;
    }

    /**
     * mark transfer candidate as unpaid
     * also mark transfer from complete to
     * progress in case if its completed
     * @param $tc_id
     * @return array
     */
    public static function markUnpaid($tc_id)
    {
        $TransferCandidate = TransferCandidate::findOne($tc_id);

        if (!$TransferCandidate) {
            return [
                "operation" => "error",
                "message" => 'Candidate Transfer not found'
            ];
        }

        if (!($TransferCandidate->hours > 0)) {
            return [
                "operation" => "error",
                "message" => "Candidate Transfer can't be mark as unpaid. As total paid amount is equal to zero"
            ];
        }

        $TransferCandidate->paid = TransferCandidate::UNPAID;
        $TransferCandidate->transfer_benef_iban = null;
        $TransferCandidate->transfer_benef_name = null;
        $TransferCandidate->bank_id = null;

        if ($TransferCandidate->save(false)) {

            $Transfer = Transfer::findOne($TransferCandidate->transfer_id);

            // in case if transfer is paid
            if ($Transfer->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE) {

                $Transfer->transfer_status = Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;

                if ($Transfer->save(false)) {
                    return [
                        "operation" => "success",
                        "message" => 'Candidate Transfer marked as "unpaid" with transfer status changed to salary distribution in progress successfully'
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => $Transfer->errors
                    ];
                }
            }

            $TransferCandidate->candidate->bank_id = null;
            $TransferCandidate->candidate->bank_account_name = null;
            $TransferCandidate->candidate->candidate_iban = null;
            $TransferCandidate->candidate->save(false);

            return [
                "operation" => "success",
                "message" => 'Candidate Transfer marked as "unpaid" successfully'
            ];
        }
    }

    /**
     * notify candidate on transfer marked as paid by admin
     */
    public function emailTransferSuccess() {

        $subjectLine = number_format($this->totalPaidToCandidate, 3) . " transferred to your account!";

        $name = $this->candidate->candidate_name? $this->candidate->candidate_name: $this->candidate->candidate_name_ar;

        if(YII_ENV != 'prod') {
            $subjectLine = '[Fake] [Ignore] ' . $subjectLine;
        }

        Yii::$app->mailer->compose('candidate/transfer-success',[
            'name' => strtoupper (explode (' ', $name)[0]),
            'totalPaidToCandidate' => $this->totalPaidToCandidate,
            'imageMoney' => Yii::$app->urlManagerStaff->createUrl(
                '../images/money.gif'
            ),
            'logo' => Yii::$app->urlManagerStaff->createUrl(
                '../images/logo.png'
            )
        ])
            ->setTo($this->candidate->candidate_email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setSubject($subjectLine)
            ->send();
    }

    /**
     * mark candidate transfer as paid
     * @param $tc_id number
     * @param $transfer_confirmation_id string
     * @return array
     */
    public static function markPaid($tc_id, $transfer_confirmation_id)
    {
        $TransferCandidate = TransferCandidate::findOne($tc_id);

        if (!$TransferCandidate) {
            return [
                "operation" => "error",
                "message" => 'Candidate Transfer not found'
            ];
        }

        $TransferCandidate->paid = TransferCandidate::PAID;
        $TransferCandidate->transfer_confirmation_id = $transfer_confirmation_id;
        
        if ($TransferCandidate->save(false)) {

            Transfer::markTransferCompleteOnCandidatePaid($TransferCandidate->transfer_id);

            $TransferCandidate->emailTransferSuccess();

            return [
                "operation" => "success",
                "message" => 'Candidate Transfer marked as "paid" successfully'
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $TransferCandidate->errors
            ];
        }
    }

    /**
     * mark bulk transfer candidate as paid
     * & transfer paid on base of that
     * @param $transferCandidateIds
     * @return array
     */
    public static function markAllPaid($transferCandidateIds) {

        if (count($transferCandidateIds) == 0) {
            return [
                "operation" => "error",
                "message" => 'Empty Transfer Record'
            ];
        }
        
        // fetch record of transfer candidate id and update all
        
        $transferCandidateList = ArrayHelper::getColumn($transferCandidateIds,'tc_id');
        
        $transferCandidates = TransferCandidate::find()
            ->filterWhere(['in', 'tc_id', $transferCandidateList])
            ->all();
        
        foreach($transferCandidates as $transferCandidate) {
            $transferCandidate->paid = TransferCandidate::PAID;
            $transferCandidate->save();

            $transferCandidate->emailTransferSuccess();
        }

        // fetch record of transfer list id and update one by one with condition
        
        $transferList = ArrayHelper::getColumn($transferCandidateIds,'transfer_id');
        
        foreach (array_unique($transferList) as $value)
        {
            Transfer::markTransferCompleteOnCandidatePaid($value);
        }

        Yii::info('[' . count($transferCandidateIds) . ' candidates have been marked as paid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            'operation' => 'success',
            'message' => count($transferCandidateIds). ' candidates have been marked as paid',
        ];
    }

    /**
     * mark all transfer candidate as unpaid
     * and also mark transfer in progress on base of that
     * @param $transferCandidateIds
     * @return array
     */
    public static function markAllUnpaid($transferCandidateIds) {

        if (
            ($transferCandidateIds && count($transferCandidateIds) == 0) ||
            !$transferCandidateIds
        ) {
            return [
                "operation" => "error",
                "message" => 'empty transfer record'
            ];
        }

        // fetch record of transfer candidate id and transfer list id
        $transferCandidateList = ArrayHelper::getColumn($transferCandidateIds,'tc_id');
        
        $transferList = array_unique(array_values(ArrayHelper::getColumn($transferCandidateIds,'transfer_id')));
        
        $condition = [
            'and',
            [
                'or',
                ['>', 'hours', 0],
                ['>', 'bonus', 0],
            ],
            ['in', 'tc_id', $transferCandidateList],
        ];

        
        $transferCandidates = TransferCandidate::find()
            ->filterWhere($condition)
            ->all();
        
        foreach($transferCandidates as $transferCandidate) {
            $transferCandidate->paid = TransferCandidate::UNPAID;
            $transferCandidate->save();
        }
        
        Transfer::updateAll(['transfer_status'=>Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS],['in', 'transfer_id', $transferList]);

        Yii::info('[' . count($transferCandidateIds) . ' candidates have been marked as unpaid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            'operation' => 'success',
            'message' => count($transferCandidateIds). ' candidates have been marked as unpaid',
        ];
    }

    /**
     * sending notification to all candidate with
     * unpaid transfer due to bank issue
     * @return bool
     */
    public function unpaidNotification()
    {
        $tmpName = explode(" ",$this->candidate->candidate_name);

        Yii::$app->mailer->htmlLayout = 'layouts/html';
        
        $allStaffEmails = ArrayHelper::map(Staff::find()->all(),'staff_email','staff_name');
        
        return Yii::$app->mailer->compose("candidate/transfer-fail.php",
            [
                "name" => (isset($tmpName[0]))  ? $tmpName[0] : $this->candidate->candidate_name,
                'logo' => Url::to('@web/images/logo.png', true),
                "webUrl" => Yii::$app->params['candidateAppUrl'] . 'view/payments',
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate->candidate_email)
            ->setBcc($allStaffEmails)
            ->setSubject('Transfer failed. Please update your bank info')
            ->send();
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
        return parent::getTransfer($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\admin\models\Invoice")
    {
        return parent::getInvoice($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\common\models\Bank")
    {
        return parent::getBank($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFile($modelClass = "\common\models\TransferFile")
    {
        return parent::getTransferFile($modelClass);
    }
}
