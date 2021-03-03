<?php
namespace staff\models;

use Yii;
use yii\base\Exception;


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
    	return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(),[
            'company',
            'invoices',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates',
            'totalPaid',
            'totalUnpaid',
            'totalCandidateTransferTotal',
            'unPaidTransferCandidates',
            'remainingPaymentTransferTotal',
            'profit'
        ]);
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
     * Get count of Candidates who weren't paid for this transfer
     * @return double
     */
    public function getTotalCandidateTransferTotal()
    {
        return (int) $this->getTransferCandidates()->count();
    }

    /**
     * Get the profit calculation
     * @return double
     */
    public function getProfit()
    {
        return Yii::$app->formatter->asDecimal($this->company_total - $this->total, 3);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
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
    public function getTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\staff\models\Invoice")
    {
        return parent::getInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getChildTransfers($modelClass)->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\staff\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getChildTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Get all unpaid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return \common\models\Transfer|\yii\db\ActiveQuery
     */
    public function getUnPaidTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getUnPaidTransferCandidates($modelClass);
    }

    /**
     * Get all paid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getPaidTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\staff\models\Transfer")
    {
        return parent::getParentTransfer($modelClass);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getParentTransferCandidates($modelClass);
    }

    /**
     * @param $transfer_id
     * @return mixed
     */
    public static function getTransferCost($transfer_id) {
        return TransferCandidate::find()
            ->where([
                'transfer_id' => $transfer_id
            ])
            ->andWhere('hours > 0 OR bonus > 0')
            ->sum('transfer_cost');
    }

    /**
     * @param int $statusCode
     * @return array|bool|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public static function getTransferStatusRecordDetail($statusCode = 0){
        $queryResult = Transfer::find()
            ->select('count(*) as total,transfer_status')
            ->andWhere(['transfer_status'=>$statusCode])
            ->isParentTransfer()
            ->groupBy('transfer_status')
            ->asArray()
            ->one();

        if ($queryResult) {
            return $queryResult;
        } else {
            return [];
        }
    }
}
