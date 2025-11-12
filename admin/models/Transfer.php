<?php
namespace admin\models;

use common\models\Currency;
use Yii;
use yii\base\Exception;
use yii\db\Expression;


/**
 * This is the model class for table "Transfer".
 * It extends from \common\models\Transfer but with custom functionality for this application module
 */
class Transfer extends \common\models\Transfer
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

        $fields['company_name'] = function($model) {
            return $model->company->company_name;
        };

        $fields['company_email'] = function($model) {
            return $model->company->company_email;
        };
        
    	$fields['total_transfer_cost'] = function($model) {
    		return floatval(Transfer::getTransferCost($model->transfer_id));
    	};

    	return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            "contract",
            'invoices',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates',
            'totalPaid',
            'totalUnpaid',
            'unPaidTransferCandidates',
            'remainingPaymentTransferTotal',
            'profit',
            'transferFileEntries',
            'isSuspicious'
        ];
    }
    
    /**
     * Get count of Candidates who got paid for this transfer
     * @return double
     */
    public function getRemainingPaymentTransferTotal()
    {
        $unpaidCandidates = $this->getUnPaidTransferCandidates()->asArray()->all();
        
        return Candidate::calculateRemainingPaymentTransferTotal($unpaidCandidates);
    }

    /**
     * Return the transfer status to be marked as locked
     * This is only possible after the status has been marked as `Payment Sent` by mistake
     * @throws yii\base\Exception
     */
    public function lock()
    {
        if($this->transfer_status == Transfer::STATUS_LOCK) {
            throw new Exception('Transfer already locked.');
        }

        if($this->transfer_status != Transfer::STATUS_PAYMENT_SENT) {
            throw new Exception('Transfer needs to be marked as "Payment Sent" to revert to "Locked" status.');
        }

        $this->transfer_status = Transfer::STATUS_LOCK;

        return $this->save(false);
    }

    /**
     * Unlock a locked transfer
     * To unlock a transfer, transfer status should be already locked
     * @throws yii\base\Exception
     */
    public function unlock()
    {
        if($this->transfer_status == Transfer::STATUS_INITIATED) {
            throw new Exception('Transfer already unlocked.');
        }
        
        if(!in_array($this->transfer_status, [Transfer::STATUS_LOCK, Transfer::STATUS_PAYMENT_SENT])) {
            throw new Exception('Transfer status should be "Locked" or "Payment Sent" to unlock it!');
        }
        
        $this->transfer_status = Transfer::STATUS_INITIATED;

        return $this->save(false);        
    }

    /**
     * Mark transfer and its invoices as payment received
     * @throws yii\base\Exception
     */
    public function paymentReceived()
    {
        if ($this->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS) {
            throw new Exception('Transfer already marked as payment received and distribution in progress.');
        }

        #https://www.pivotaltracker.com/story/show/174315865 adding lock option also due to this ticket.
        if (($this->transfer_status == Transfer::STATUS_PAYMENT_SENT) || ($this->transfer_status == Transfer::STATUS_LOCK)) {

            // Set payment received date and update transfer status
            $this->payment_received_on = date('Y-m-d');
            $this->transfer_status = Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;
            $this->save(false);

            // Mark invoice as paid for all child transfer and main transfer in case of no child company
            Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $this->transfer_id]);

            // Mark all invoices belonging to child transfers belonging to this transfer as paid
            $child_transfers = Transfer::findAll(['parent_transfer_id' => $this->transfer_id]);

            foreach ($child_transfers as $key => $value) {
                Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $value->transfer_id]);
            }

            //notify all candidates 
            
            $transferCandidates = $this->getTransferCandidates()
                ->joinWith(['store', 'company'])
                ->all();
            
            foreach($transferCandidates as $tc) {
                $this->sendNewTransferNotification($tc);
            }
            
            return true;
        } else  {
            throw new Exception('Transfer status need to be "Payment Sent" first before marking as "Payment Received"');
        }
    }

    /**
     * Get count of Candidates who got paid for this transfer
     * @return double
     */
    public function getTotalPaid()
    {
        return (int) $this->getTransferCandidates()
            ->totalPaid();
    }

    /**
     * Get count of Candidates who weren't paid for this transfer
     * @return double
     */
    public function getTotalUnpaid()
    {
        return (int) $this->getTransferCandidates()
            ->totalUnpaid();
    }

    /**
     * Get the profit calculation
     * @return double
     */
    public function getProfit()
    {
        return floatval($this->company_total - $this->total);
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
     * Get all TransferCandidate links under this transfer
     * which include hours worked, hourly rate, etc
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferCandidates()
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\admin\models\Invoice")
    {
        return parent::getInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getChildTransfers($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\admin\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getChildTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\admin\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\admin\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Get all unpaid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return \common\models\Transfer|\yii\db\ActiveQuery
     */
    public function getUnPaidTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getUnPaidTransferCandidates($modelClass);
    }

    /**
     * Get all paid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getPaidTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\admin\models\Transfer")
    {
        return parent::getParentTransfer($modelClass);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getParentTransferCandidates($modelClass);
    }

    /**
     * @param $transfer_id
     * @return mixed
     */
    public static function getTransferCost($transfer_id) {
        return TransferCandidate::find()
            ->andWhere([
                'transfer_id' => $transfer_id
            ])
            ->andWhere(new Expression('company_total > 0'))
            ->sum('transfer_cost');
    }

    /**
     * @param $statusCode
     * @param $startDate
     * @param $endDate
     * @param $currency_code
     * @return array|\yii\db\ActiveRecord
     */
    public static function getTransferStatusRecordDetail($statusCode = 0, $startDate = null, $endDate = null, $currency_code = "KWD") {
        
        $query = Transfer::find()
            ->select('count(*) as total,transfer_status')
            ->andWhere(['transfer_status'=>$statusCode])
            ->isParentTransfer()
            ->groupBy('transfer_status');

        if($currency_code) {
            $query->andWhere(['transfer.currency_code' => $currency_code]);
        }

        if($startDate) {
            $query->andWhere(new Expression("DATE(start_date) >= DATE('" . $startDate . "')"));
        }
        if($endDate) {
            $query->andWhere(new Expression("DATE(end_date) <= DATE('" . $endDate . "')"));
        }

        $queryResult = $query->asArray()
            ->one();

        if ($queryResult) {
            return $queryResult;
        } else {
            return [];
        }
    }

    /**
     * mark transfer complete on base of
     * transfer candidate check if all candidate
     * paid then make transfer paid
     * @param $transferID
     * @return array
     */
    public static function markTransferCompleteOnCandidatePaid($transferID)
    {
        $unpaid = TransferCandidate::find()
            ->andWhere([
                'paid' => 0
            ])
            ->andWhere(['transfer_id' => $transferID])
            ->count();

        if ($unpaid) {
            return [
                "operation" => "error",
                "message" => "Should not have unpaid candidate to mark transfer as paid"
            ];
        }
        
        $transfer = Transfer::findOne($transferID);
        
        $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
        
        if (!$transfer->save(false)) {
            return [
                "operation" => "error",
                "message" => $transfer->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => 'Candidate Transfer marked as "paid" with transfer status changed to completed successfully'
        ];
    }

    /**
     * @return void
     */
    public static function triggerPayableCandidateEvent() {

        /*$currencies = [
            [
                'currency_code' => "KWD"
            ],
            [
                'currency_code' => "BHD"
            ]
        ]; Transfer::find()
            ->distinct('currency_code')
            ->asArray()
            ->all();*/

        $currencies = Currency::find()
            ->andWhere(['status' => 1])
            ->all();

        $data = [];

        foreach ($currencies as $currency) {
            $result = Candidate::getTotalPayableCandidate($currency['code']);

            $data[$currency['code']] = $result['payable'];
        }

        Yii::$app->eventManager->track(
            'Payable Candidates', $data);
    }
}
