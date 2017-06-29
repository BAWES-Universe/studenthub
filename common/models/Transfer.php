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
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'invoice',
            'invoices',
            'transferCandidates',
            'childTransfers',
            'childTransferInvoices',
            'childTransferCandidates'
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
     * Get the invoice belonging to this transfer
     * Each transfer can have max a single invoice, unless it has sub-transfers
     * then each subtransfer can have an invoice each.
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferInvoices()
     * @return \yii\db\ActiveQuery|static
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::className(), ['transfer_id'=>'transfer_id']);
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
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        if($this->parent_transfer_id)
        {
            //child transfer
            return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id'])
                ->via('parentTransfer')
                ->andWhere([
                    '{{%transfer_candidate}}.company_id' => $this->company_id
                ]);
        }
        else
        {
            //parent transfer
            return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id']);
        }        
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getChildTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['parent_transfer_id'=>'transfer_id']);
    }

    public function getInvoices()
    {
        if($this->childTransfers) 
        {
            //parent transfer 
            return $this->hasMany(Invoice::className(), ['transfer_id' => 'transfer_id'])
                ->via('childTransfers');
        }
        else
        {
            //child transfer 
            return $this->hasMany(Invoice::className(), ['transfer_id' => 'transfer_id']);
        }
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
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])->via('childTransfers');
    }

    /**
     * Static function to validate candidate array to initiate transfer
     * @param $company_id
     * @param $candidates
     * @return array|string
     */
    public static function validateCandidates($company_id, $candidates)
    {
        $errors = [];
        $total = 0;
        $company_total = 0;
        if(!is_array($candidates)) {
            $candidates = [];
        }

        // check if empty field
        foreach ($candidates as $key => $value)
        {
            if(empty($value['candidate_id']))
            {
                $errors['candidate_id'][] = 'Candidate field require.';
                return $errors;
            }
            $bonus = (isset($value['bonus'])) ? $value['bonus'] : 0;
            $hours = (isset($value['hours'])) ? $value['hours'] : 0;
            $company_total += $bonus + ($hours * Yii::$app->params['candidate_max_hourly_rate']);
        }

        // Case where transfer total is zero/empty
        if ($company_total == 0) {
            return "Transfer total is zero. Please input the actual hours worked.";
        }

        // Get list of all subcompanies belonging to this company.
        $companies = Company::findAll(['parent_company_id' => $company_id]);
        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');
        $company_ids[] = $company_id;

        // Use subcompany list to Get list of all stores belonging to the parent company
        $stores = Store::find()
            ->where(['in', 'company_id', $company_ids])
            ->all();
        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

        // Find all candidates that work in stores belonging to company but not included in candidate list
        // that is being validated. Show error if any missing
        $candidate_ids = ArrayHelper::map($candidates, 'candidate_id', 'candidate_id');
        $missing = Candidate::find()
            ->where(['in', 'store_id', $store_ids])
            ->andWhere(['NOT IN', 'candidate_id', $candidate_ids])
            ->count();
        if($missing > 0)
        {
            $errors['candidate_id'][] = 'Missing ' . $missing . ' candidate(s).';
        }

        return $errors;
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
