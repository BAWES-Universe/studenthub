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
 * @property integer $bank_id
 * @property string $transfer_confirmation_id
 * @property integet $transfer_file_id
 * @property string $transfer_benef_name
 * @property string $transfer_benef_iban
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
            [['transfer_id', 'candidate_id', 'store_id', 'bank_id', 'company_id', 'transfer_file_id'], 'integer'],
            [['store_name', 'company_name'], 'string', 'max' => 100],
            [['company_email'], 'email'],
            [['transfer_confirmation_id'], 'string', 'max' => 128],
            [['transfer_benef_iban'], 'string', 'max' => 50],
            [['transfer_confirmation_id'], 'unique'],
            ['paid', 'validateStatus'],
            [['transfer_benef_name'], 'string', 'max' => 60],
            [['bank_id', 'transfer_confirmation_id', 'transfer_benef_name', 'transfer_benef_iban'], 'validateBankDetails'],
            [['hours', 'transfer_cost', 'bonus', 'bonus_commission', 'candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['tc_created_at', 'tc_updated_at'], 'safe'],
            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::className(), 'targetAttribute' => ['bank_id' => 'bank_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
            [['transfer_file_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransferFile::className(), 'targetAttribute' => ['transfer_file_id' => 'transfer_file_id']]
        ];
    }

    public function validateStatus($attribute, $params, $validator)
    {
        //on mark as paid clear out the name/iban/bank id/transfer_confirmation_id
        
        if($this->getOldAttribute('paid') && !$this->paid) {
            $this->bank_id = null; 
            $this->transfer_confirmation_id = null; 
            $this->transfer_benef_iban = null; 
            $this->transfer_benef_name = null;
        }
        
        return true;
    }
    
    /**
     * validate bank detail
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function validateBankDetails($attribute, $params, $validator)
    {   
        //if paid, don't allow bank detail change 
        
        if(
            $this->getOldAttribute('paid') &&
            (
                $this->$attribute != $this->getOldAttribute($attribute)
            )      
        ) {
            $this->addError($attribute, 'Bank detail can not be updated on paid transfer.');
        }
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
            'tc_id' => Yii::t('app','TC ID'),
            'transfer_id' => Yii::t('app','Transfer ID'),
            'candidate_id' => Yii::t('app','Candidate ID'),
            'store_id' => Yii::t('app','Store ID'),
            'store_name' => Yii::t('app','Store Name'),
            'company_id' => Yii::t('app','Company ID'),
            'company_name' => Yii::t('app','Company Name'),
            'company_email' => Yii::t('app','Company Email'),
            'bank_id' => Yii::t('app','Bank ID'),
            'transfer_confirmation_id' => Yii::t('app','Transfer confirmation ID'),
            'transfer_file_id' => Yii::t('app', 'Transfer File ID'),
            'transfer_benef_name' => Yii::t('app','Transfer Benef Name'),
            'transfer_benef_iban' => Yii::t('app','Transfer Benef IBAN'),
            'hours' => Yii::t('app','Hours'),
            'candidate_hourly_rate' => Yii::t('app','Candidate Hourly Rate'),
            'company_hourly_rate' => Yii::t('app','Company Hourly Rate'),
            'transfer_cost' => Yii::t('app','Transfer cost'),
            'bonus' => Yii::t('app','Bonus'),
            'bonus_commission' => Yii::t('app','Bonus Commission (KWD)'),
            'tc_created_at' => Yii::t('app','Tc Created At'),
            'tc_updated_at' => Yii::t('app','Tc Updated At'),
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
     * after object saved
     * @param boolean $insert
     * @param array $changedAttributes
     * @return boolean
     */
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);
        
        if($insert) {

            $this->sendNewTransferNotification();
           
        } else if (isset($changedAttributes['paid']) && $this->paid == self::PAID) {
            
            $this->sendTransferPaidNotification();
            
        } else if (isset($changedAttributes['paid']) && $this->paid == self::UNPAID) {
            
            $this->sendTransferUnpaidNotification();
            
        }
        
        return true;
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
            'invoice',
            'bank',
            'transferFile'
        ];
    }
    
    public function sendTransferPaidNotification() 
    {
        $heading = Yii::t('app', 'Transfer paid');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = 'KWD ' . $this->totalPaidToCandidate;

        $filters = [
            [
                "field" => "tag",
                "key" => "candidate_id",
                "relation" => "=",
                "value" => $this->candidate_id
            ]
        ];

        $data = [
            'subject' => 'transfer',   
            'transfer_id' => $this->transfer_id,
            'tc_id' => $this->tc_id
        ];

        MobileNotification::notifyCandidate($heading, $data, $filters, $subtitle, $content);
    }
    
    public function sendTransferUnpaidNotification() 
    {
        $heading = Yii::t('app', 'Transfer marked as unpaid');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = 'KWD ' . $this->totalPaidToCandidate;

        $filters = [
            [
                "field" => "tag",
                "key" => "candidate_id",
                "relation" => "=",
                "value" => $this->candidate_id
            ]
        ];

        $data = [
            'subject' => 'transfer',   
            'transfer_id' => $this->transfer_id,
            'tc_id' => $this->tc_id
        ];

        MobileNotification::notifyCandidate($heading, $data, $filters, $subtitle, $content);
    }
    
    public function sendNewTransferNotification() 
    {
        $heading = Yii::t('app', 'New transfer initiated');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = 'KWD ' . $this->totalPaidToCandidate;

        $filters = [
            [
                "field" => "tag",
                "key" => "candidate_id",
                "relation" => "=",
                "value" => $this->candidate_id
            ]
        ];

        $data = [
            'subject' => 'transfer',   
            'transfer_id' => $this->transfer_id,
            'tc_id' => $this->tc_id
        ];

        MobileNotification::notifyCandidate($heading, $data, $filters, $subtitle, $content);
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
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
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
    public function getBank($modelClass = "\common\models\Bank")
    {
        return $this->hasOne($modelClass::className(), ['bank_id' => 'bank_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFile($modelClass = "\common\models\TransferFile")
    {
        return $this->hasOne($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
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
            ->andWhere(new \yii\db\Expression('transfer_candidate.bank_id IS NOT NULL'))    
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
