<?php

namespace admin\models;

use common\models\MailLog;
use common\models\Staff;
use Yii;
use yii\db\Exception;
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
            return $model->candidate_total;
            //($model->candidate_hourly_rate * $model->hours) + $model->bonus - $model->bonus_commission;
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
     * @return array|string[]
     */
    public function extraFields()
    {
        $fields =  parent::extraFields ();

        return array_merge ($fields, [
            'transferFileEntry',
            "transfer"
        ]);
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
        $transferCandidate = TransferCandidate::findOne($tc_id);

        if (!$transferCandidate) {
            return [
                "operation" => "error",
                "message" => 'Candidate Transfer not found'
            ];
        }

        if (
            $transferCandidate->hours == 0 &&
            $transferCandidate->minutes == 0 &&
            $transferCandidate->seconds == 0 &&
            $transferCandidate->bonus = 0
        ) {
            return [
                "operation" => "error",
                "message" => "Candidate Transfer can't be mark as unpaid. As total paid amount is equal to zero"
            ];
        }

        $transferCandidate->paid = TransferCandidate::UNPAID;
        $transferCandidate->transfer_benef_iban = null;
        $transferCandidate->transfer_benef_name = null;
        $transferCandidate->bank_id = null;

        if ($transferCandidate->save(false)) {

            $transferCandidate->candidate->bank_id = null;
            $transferCandidate->candidate->bank_account_name = null;
            $transferCandidate->candidate->candidate_iban = null;
            $transferCandidate->candidate->save(false);

            $Transfer = Transfer::findOne($transferCandidate->transfer_id);

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

            if(YII_ENV == 'prod') {
                Transfer::triggerPayableCandidateEvent();
            }

            return [
                "operation" => "success",
                "message" => 'Candidate Transfer marked as "unpaid" successfully'
            ];
        }
    }

    /**
     * mark candidate transfer as paid
     * @param $tc_id number
     * @param $transfer_confirmation_id string
     * @return array
     */
    public static function markPaid(
        $tc_id,
        $transfer_confirmation_id = null,
        $initTransfer = false,
        $transferCandidate = null,
        $updateTransferStatus = true
    )
    {
        if(!$transferCandidate)
            $transferCandidate = TransferCandidate::findOne($tc_id);

        if (!$transferCandidate) {
            return [
                "operation" => "error",
                "message" => 'Candidate Transfer not found'
            ];
        }

        if (!$transfer_confirmation_id) {
            return [
                "operation" => "error",
                "message" => 'Missing transfer confirmation ID'
            ];
        }

        if($transferCandidate->paid == TransferCandidate::PAID)
        {
            return [
                "operation" => "error",
                "message" => 'Already marked as paid'
            ];
        }

        $amount = $transferCandidate->candidate_total == 0 ?
            $transferCandidate->totalPaidToCandidate: $transferCandidate->candidate_total;

        $transaction = Yii::$app->db->beginTransaction();

        $transferCandidate->paid = TransferCandidate::PAID;
        $transferCandidate->transfer_confirmation_id = $transfer_confirmation_id;

        if (!$transferCandidate->save()) {

            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $transferCandidate->errors
            ];
        }

        if($updateTransferStatus) {

            //optional
            Transfer::markTransferCompleteOnCandidatePaid($transferCandidate->transfer_id);

            /*$response =
            if($response['operation'] == 'error') {
                $transaction->rollBack();

                return $response;
            }*/
        }

        if(YII_ENV == 'prod') {
            // Wallet system removed - no longer tracking wallet entries
            Transfer::triggerPayableCandidateEvent();
        }

        try {
            $transaction->commit();
        } catch (Exception $e) {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        return [
            "operation" => "success",
            "message" => 'Candidate Transfer marked as "paid" successfully',
            'amount' => $amount
        ];
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
            ->andWhere(['in', 'tc_id', $transferCandidateList])
            ->all();
        
        foreach($transferCandidates as $transferCandidate) {

            if($transferCandidate->paid == TransferCandidate::PAID)
                continue;

            // Wallet system removed - no longer tracking wallet entries
            
            $transferCandidate->paid = TransferCandidate::PAID;
            $transferCandidate->save();
        }

        // fetch record of transfer list id and update one by one with condition
        
        $transferList = ArrayHelper::getColumn($transferCandidateIds,'transfer_id');
        
        foreach (array_unique($transferList) as $value)
        {
            Transfer::markTransferCompleteOnCandidatePaid($value);
        }

        Yii::info('[' . count($transferCandidateIds) . ' candidates have been marked as paid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        if(YII_ENV == 'prod') {
            Transfer::triggerPayableCandidateEvent();
        }

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
                ['>', 'minutes', 0],
                ['>', 'seconds', 0],
                ['>', 'bonus', 0],
            ],
            ['in', 'tc_id', $transferCandidateList],
        ];

        
        $transferCandidates = TransferCandidate::find()
            ->andWhere($condition)
            ->all();
        
        foreach($transferCandidates as $transferCandidate) {
            $transferCandidate->paid = TransferCandidate::UNPAID;
            $transferCandidate->save();
        }
        
        Transfer::updateAll(['transfer_status'=>Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS],['in', 'transfer_id', $transferList]);

        Yii::info('[' . count($transferCandidateIds) . ' candidates have been marked as unpaid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        if(YII_ENV == 'prod') {
            Transfer::triggerPayableCandidateEvent();
        }

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
        if(!$this->candidate->candidate_email_verification)
            return false;

        $tmpName = explode(" ",$this->candidate->candidate_name);

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $staffs = Staff::find()
            ->joinWith('staffNotifications')
            ->andWhere(['staff.deleted' => false, 'staff_notification' => true, 'permission' => "transfer-fail"])
            ->all();

        $allStaffEmails = ArrayHelper::map($staffs,'staff_email','staff_name');

        $ml = new MailLog();
        $ml->to = $this->candidate->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Transfer failed. Please update your bank info';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("candidate/transfer-fail.php",
            [
                "name" => (isset($tmpName[0]))  ? $tmpName[0] : $this->candidate->candidate_name,
                'logo' => Url::to('@web/images/logo.png', true),
                "webUrl" => Yii::$app->params['candidateAppUrl'] . 'view/payments',
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate->candidate_email)
            ->setBcc($allStaffEmails)
            ->setSubject('Transfer failed. Please update your bank info');

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
