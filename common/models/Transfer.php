<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use common\models\Store;
use common\models\Company;
use common\models\Candidate;

/**
 * This is the model class for table "transfer".
 *
 * @property integer $transfer_id
 * @property integer $company_id
 * @property integer $company_total
 * @property integer $transfer_status
 * @property integer $parent_transfer_id
 * @property number $total
 * @property string $transfer_created_at
 * @property string $transfer_updated_at
 *
 * @property Company $company
 * @property TransferCandidate[] $transferCandidates
 * @property Invoice $invoice
 * @property Transfer[] $childTransfers
 * @property TransferCandidate[] $childTransferCandidates
 * @property Invoice $childTransferInvoices
 */
class Transfer extends \yii\db\ActiveRecord
{
    const STATUS_PAYMENT_SENT = 1;
    const STATUS_SALARY_DISTRIBUTION_IN_PROGRESS = 3;
    const STATUS_TRANSFER_COMPLETE = 4;
    const STATUS_LOCK = 5;
    const STATUS_INITIATED = 10; // Draft

    /**
     * @return array
     */
    public static function statusList()
    {
        return [
            self::STATUS_INITIATED => 'Draft',
            self::STATUS_LOCK => 'Locked',
            self::STATUS_PAYMENT_SENT => 'Payment Sent',
            self::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS => 'Received & Distributing Salary',
            self::STATUS_TRANSFER_COMPLETE => 'Transfer Completed',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'transfer_status'], 'integer'],
            [['total', 'company_total'], 'number'],
            [['transfer_created_at', 'transfer_updated_at', 'payment_received_on'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'transfer_created_at',
                'updatedAtAttribute' => 'transfer_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transfer_id' => 'Transfer ID',
            'company_id' => 'Company ID',
            'company_total' => 'Total for company',
            'total' => 'Total',
            'transfer_status' => 'Transfer Status',
            'transfer_created_at' => 'Transfer Created At',
            'transfer_updated_at' => 'Transfer Updated At',
            'payment_received_on' => 'Payment Received On'
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted']);

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
            'transferCandidates'
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * Get all TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        // If this is a child transfer return all TransferCandidate records
        // belonging to its parent transfer
        if($this->parent_transfer_id)
        {
            return $this->getParentTransferCandidates($modelClass)
                ->andWhere(['company_id' => $this->company_id]);
        }

        // Otherwise return all TransferCandidate records belonging to this transfer
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\common\models\Invoice")
    {
        // If this is a parent transfer, return all invoices belonging to its children transfers
        if($this->childTransfers)
            return $this->getChildTransferInvoices($modelClass);

        // Otherwise return all invoices belonging to it
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id'=>'parent_transfer_id'])
            ->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('parentTransfer');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getChildTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['parent_transfer_id'=>'transfer_id']);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\common\models\Invoice")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('childTransfers');
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('childTransfers');
    }

    /**
     * Generate Invoice for Tranfer
     * @return integer invoice_id
     */
    public function generateInvoice()
    {
        $invoice = Invoice::findOne(['transfer_id' => $this->transfer_id]);

        if(!$invoice) {
            $invoice = new Invoice;
            $invoice->transfer_id = $this->transfer_id;
            $invoice->invoice_date = date('Y-m-d');
            $invoice->invoice_status = 'unpaid';
            $invoice->save();
        }

        return $invoice->invoice_id;
    }

    /**
     * @inheritdoc
     * @return query\TransferQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferQuery(get_called_class());
    }
}
