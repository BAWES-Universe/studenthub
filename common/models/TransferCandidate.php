<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "transfer_candidate".
 *
 * @property integer $tc_id
 * @property integer $transfer_id
 * @property integer $candidate_id
 * @property integer $store_id
 * @property string $store_name
 * @property integer $company_id
 * @property string $company_name
 * @property string $company_email
 * @property decimal $candidate_hourly_rate - hourly rate candidate will receive
 * @property decimal $company_hourly_rate - hourly rate company paying 
 * @property decimal $hours - no of hours candidate have worked
 * @property decimal $bonus - bonus amount company paying 
 * @property decimal $bonus_commission - commission admin will take from bonus in KWD
 * @property decimal $transfer_cost - transfer cost of payment 
 * @property integer $paid
 * @property string $tc_created_at
 * @property string $tc_updated_at
 *
 * @property Candidate $candidate
 * @property Transfer $transfer
 * @property Invoice $Invoice
 * 
 * Company paying = ($hours * $company_hourly_rate) + $bonus;
 * 
 * Candidate getting = ($hours * $candidate_hourly_rate) + $bonus - $bonus_commission;
 * 
 * Admin profit = Company paying - Candidate getting - $transfer_cost;
 */
class TransferCandidate extends \yii\db\ActiveRecord
{
    const PAID = 1;
    const UNPAID = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_candidate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transfer_id', 'candidate_id', 'store_id', 'company_id'], 'integer'],
            [['store_name', 'company_name'], 'string', 'max' => 100],
            [['company_email'], 'email'],
            [['hours', 'transfer_cost', 'bonus', 'bonus_commission', 'candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['tc_created_at', 'tc_updated_at'], 'safe'],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'tc_created_at',
                'updatedAtAttribute' => 'tc_updated_at',
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
            'tc_id' => 'TC ID',
            'transfer_id' => 'Transfer ID',
            'candidate_id' => 'Candidate ID',
            'store_id' => 'Store ID',
            'store_name' => 'Store Name',
            'company_id' => 'Company ID',
            'company_name' => 'Company Name',
            'company_email' => 'Company Email',
            'hours' => 'Hours',
            'candidate_hourly_rate' => 'Candidate Hourly Rate',
            'company_hourly_rate' => 'Company Hourly Rate',
            'transfer_cost' => 'Transfer cost',
            'bonus' => 'Bonus',
            'bonus_commission' => 'Bonus Commission (KWD)',
            'tc_created_at' => 'Tc Created At',
            'tc_updated_at' => 'Tc Updated At',
        ];
    }

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // Total amount paid by company
        $fields['total_paid'] = function ($model) {
            return $this->totalPaidByCompany;
        };
        // Total amount we need to pay to candidate
        $fields['total_amount'] = function ($model) {
            return $this->totalPaidToCandidate;
        };
        // Our Profile
        $fields['profit'] = function ($model) {
            return $this->profit;
        };

        /**
         * Format as numbers/double so API doesnt output as a string
         */
        $fields['hours'] = function ($model) {
            return (double)$this->hours;
        };
        $fields['bonus'] = function ($model) {
            return (double)$this->bonus;
        };
        
        $fields['bonus_commission'] = function ($model) {
            return (double)$this->bonus_commission;
        };
        
        $fields['candidate_bonus'] = function ($model) {
            return $this->bonus - $this->bonus_commission;
        };
        
        $fields['transfer_cost'] = function ($model) {
            return (double)$this->transfer_cost;
        };
        $fields['candidate_hourly_rate'] = function ($model) {
            return (double)$this->candidate_hourly_rate;
        };
        $fields['company_hourly_rate'] = function ($model) {
            return (double)$this->company_hourly_rate;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'store',
            'company',
            'candidate',
            'transfer',
            'invoice'
        ];
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidToCandidate()
    {
        return ($this->candidate_hourly_rate * $this->hours) + $this->bonus - $this->bonus_commission;
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidByCompany()
    {
        return ($this->company_hourly_rate * $this->hours) + $this->bonus;
    }

    public function getProfit()
    {
        return (($this->company_hourly_rate - $this->candidate_hourly_rate) * $this->hours) - $this->transfer_cost + $this->bonus_commission;
    }

    /**
     * Relations below
     */

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\common\models\Invoice")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @inheritdoc
     * @return query\TransferCandidateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferCandidateQuery(get_called_class());
    }

    /**
     * get list of transferable candidate
     * for text export
     * @return array
     */
    public static function getPayableCandidateListFormat()
    {
        $totalAmount = 0;

        $candidates = TransferCandidate::find()
            ->payable()
            ->all();

        if (!$candidates) {
            return false;
        }
        $list = [];
        
        foreach ($candidates as $detail) {
            $totalAmount += $detail->totalPaidToCandidate;

            if (empty($detail->candidate->bank) || !$detail->invoiceNumber) {
                continue;
            }

            $list[] = [
                'transfer' => 'S2',
                'bank_transfer_type' => $detail->candidate->bank->bank_transfer_type,
                'amount' => number_format($detail->totalPaidToCandidate, 3, '.', ''),
                'currency' => 'KWD',
                'emptyField1' => '',
                'emptyField2' => '',
                'emptyField3' => '',
                'Field1' => '11622216',
                'iban' => ltrim(rtrim($detail->candidate->candidate_iban)),
                'transfer_id' => $detail->transfer_id,
                'tc_id' => $detail->tc_id,
                'description' => 'Internship ' . $detail->hours . ' Hours',
                'emptyField4' => '',
                'emptyField5' => '',
                'emptyField6' => '',
                'bank_account_name' => ltrim(rtrim($detail->candidate->bank_account_name)),
                'bank_name' => $detail->candidate->bank->bank_name,
                'emptyField7' => '',
                'bank_name_repeat' => $detail->candidate->bank->bank_name,
                'bank_address' => $detail->candidate->bank->bank_address,
                'emptyField8' => '',
                'emptyField9' => '',
                'bank_swift_code' => $detail->candidate->bank->bank_swift_code,
                'emptyField10' => '',
                'emptyField11' => '',
                'emptyField12' => '',
                'emptyField13' => '',
                'emptyField14' => '',
                'emptyField15' => '',
                'Field2' => 'B',
                'emptyField16' => '',
                'emptyField17' => '',
                'candidate_iban' => ltrim(rtrim($detail->candidate->candidate_iban))
            ];
        }

        return [
            'candidate_list' => $list,
            'total_amount' => number_format($totalAmount, 3, '.', ''),
        ];
    }


    /**
     * get invoice number
     * @return string
     */
    public function getInvoiceNumber() {

        $invoice = false;
        
        //check if we have sub invoice/transfer, else return invoice for main company 
        
        $parentTransfer = Transfer::findOne(
            [
                'parent_transfer_id'=> $this->transfer_id,
                'company_id' => $this->company_id //$this->candidate->company->company_id
            ]
        );
        
        if ($parentTransfer && isset($parentTransfer->invoices[0])) { //
            $invoice = $parentTransfer->invoices[0]->invoice_id;
        } else { 
            $childTransfer = Transfer::findOne($this->transfer_id);
            if ($childTransfer && isset($childTransfer->invoices[0])) {
                $invoice = $childTransfer->invoices[0]->invoice_id;
            }
        }
        
        return $invoice;
    }
}