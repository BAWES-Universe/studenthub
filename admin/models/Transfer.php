<?php
namespace admin\models;

use Yii;
use yii\base\Exception;
use admin\models\TransferCandidate;
use admin\models\Company;
use admin\models\Invoice;

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
    		return TransferCandidate::find()
                ->where([
                    'transfer_id' => $model->transfer_id
                ])
                ->sum('transfer_cost');
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
            'invoices',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates',
            'totalPaid',
            'totalUnpaid',
            'profit'
        ];
    }

    /**
     * Return the transfer status to be marked as locked
     * This is only possible after the status has been marked as `Payment Sent` by mistake
     * @return [type] [description]
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
        $this->save(false);
    }

    /**
     * Unlock a locked transfer
     * To unlock a transfer, transfer status should be already locked
     */
    public function unlock()
    {
        if($this->transfer_status == Transfer::STATUS_INITIATED) {
            throw new Exception('Transfer already unlocked.');
        }
        if($this->transfer_status != Transfer::STATUS_LOCK) {
            throw new Exception('Transfer status should be "Locked" to unlock it!');
        }
        $this->transfer_status = Transfer::STATUS_INITIATED;
        $this->save(false);
    }

    /**
     * Mark transfer and its invoices as payment received
     */
    public function paymentReceived()
    {
        if($this->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS) {
            throw new Exception('Transfer already marked as payment received and distribution in progress.');
        }

        if($this->transfer_status != Transfer::STATUS_PAYMENT_SENT) {
            throw new Exception('Transfer status need to be "Payment Sent" first before marking as "Payment Received"');
        }

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
    }

    /**
     * Get Total Paid
     * @return double
     */
    public function getTotalPaid()
    {
        return $this->getTransferCandidates()
            ->totalPaid();
    }

    /**
     * Get Total Unpaid
     * @return double
     */
    public function getTotalUnpaid()
    {
        return $this->getTransferCandidates()
            ->totalUnpaid();
    }

    /**
     * Get the profit calculation
     * @return double
     */
    public function getProfit()
    {
        return $this->getTransferCandidates()
            ->profit();
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
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getChildTransfers($modelClass)->andWhere(['{{%transfer}}.deleted'=>0]);
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
}
