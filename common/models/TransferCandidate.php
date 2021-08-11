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
 * @property decimal $candidate_total - candidate total
 * @property decimal $company_total - company total
 * @property decimal $transfer_candidate
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
            //[['hours'], 'validateHours'],
            [['tc_created_at', 'tc_updated_at'], 'safe'],
            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::className(), 'targetAttribute' => ['bank_id' => 'bank_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
            [['transfer_file_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransferFile::className(), 'targetAttribute' => ['transfer_file_id' => 'transfer_file_id']]
        ];
    }

    /**
     * max length validation for no of hours
     * @param $attribute
     * @param $params
     * @param $validator
     *
    public function validateHours($attribute, $params, $validator)
    {
        if(strlen ((string)$this->hours) > 15) {
            $this->addError($attribute, 'Hours can not be more than 15 character long.');
            return false;
        }

        return true;
    }*/

    public function validateStatus($attribute, $params, $validator)
    {
        //on mark as unpaid clear out the name/iban/bank id/transfer_confirmation_id
        
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

        $fields['company_total'] = function ($model) {
            return (double)$this->company_total;
        };

        $fields['candidate_total'] = function ($model) {
            return (double)$this->candidate_total;
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
        
//        if($insert) {
//
//            $this->sendNewTransferNotification();
//
//        } else
        if (isset($changedAttributes['paid']) && $this->paid == self::PAID) {

            $this->emailTransferSuccess();

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
    
    /**
     * mobile notification on transfer marked as paid
     */
    public function sendTransferPaidNotification() 
    {
        $heading = "Transfer paid";
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = Yii::t('app', "KD {amount} has been transferred to your bank account", [
            "amount" => number_format($this->totalPaidToCandidate, 3)
        ]);

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
     * notify candidate on transfer marked as paid by admin
     */
    public function emailTransferSuccess() {

        $subjectLine = "KD " . number_format($this->totalPaidToCandidate, 3) . " has been transferred to your bank account";

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
     * mobile notification on transfer marked as unpaid
     */
    public function sendTransferUnpaidNotification() 
    {
        $heading = Yii::t('app', 'Transfer marked as unpaid');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = 'KWD ' . number_format($this->totalPaidToCandidate, 3);

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
     * mobile notification on new transfer creation
     */
    public function sendNewTransferNotification() 
    {
        $heading = Yii::t('app', 'New transfer initiated');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = 'KWD ' . number_format($this->totalPaidToCandidate, 3);

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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFileEntry($modelClass = "\common\models\TransferFileEntry")
    {
        return $this->hasOne($modelClass::className(), ['credit_narrative' => 'tc_id'])
            ->andWhere(['transfer_file_entry.status' => 'SUCCESS']);
    }

    /**
     * get list of transferable candidate
     * for text export
     * @return array
     */

    public static function getPayableCandidateListFormat()
    {
        $totalAmount = 0;

        $transferCandidates = self::find()
            ->payable()
            ->andWhere(new \yii\db\Expression('transfer_candidate.bank_id IS NOT NULL'))
            ->all();

        if (!$transferCandidates) {
            return false;
        }

        $list = [];

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile
        foreach ($transferCandidates as $transferCandidate) {

            $candidate = $transferCandidate->candidate;

            if (
                empty($candidate->bank) ||
                !$transferCandidate->bank_id ||
                !$transferCandidate->transfer_benef_iban ||
                !$transferCandidate->transfer_benef_name ||
                !$transferCandidate->invoiceNumber ||
                !$candidate->isProfileCompleted
            ) {
                continue;
            }

            $totalAmount += $transferCandidate->totalPaidToCandidate;

            $list[] = [
                'transfer' => 'S2',
                'bank_transfer_type' => $candidate->bank->bank_transfer_type,
                'amount' => number_format($transferCandidate->totalPaidToCandidate, 3, '.', ''),
                'currency' => 'KWD',
                'emptyField1' => '',
                'emptyField2' => '',
                'emptyField3' => '',
                'Field1' => '11622216',
                'iban' => ltrim(rtrim($candidate->candidate_iban)),
                'transfer_id' => $transferCandidate->transfer_id,
                'tc_id' => $transferCandidate->tc_id,
                'description' => 'Internship ' . $transferCandidate->hours . ' Hours',
                'emptyField4' => '',
                'emptyField5' => '',
                'emptyField6' => '',
                'bank_account_name' => ltrim(rtrim($candidate->bank_account_name)),
                'bank_name' => $candidate->bank->bank_name,
                'emptyField7' => '',
                'bank_name_repeat' => $candidate->bank->bank_name,
                'bank_address' => $candidate->bank->bank_address,
                'emptyField8' => '',
                'emptyField9' => '',
                'bank_swift_code' => $candidate->bank->bank_swift_code,
                'emptyField10' => '',
                'emptyField11' => '',
                'emptyField12' => '',
                'emptyField13' => '',
                'emptyField14' => '',
                'emptyField15' => '',
                'Field2' => 'B',
                'emptyField16' => '',
                'emptyField17' => '',
                'candidate_iban' => ltrim(rtrim($candidate->candidate_iban))
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

        //check if we have sub invoice/transfer, else return invoice for main company 
        
        $childTransfer = Transfer::findOne(
            [
                'parent_transfer_id'=> $this->transfer_id,
                'company_id' => $this->company_id //$this->candidate->company->company_id
            ]
        );
        
        if ($childTransfer && isset($childTransfer->invoices[0])) { 
            return $childTransfer->invoices[0]->invoice_id;
        } 
            
        if ($this->transfer && isset($this->transfer->invoices[0])) {
            return $this->transfer->invoices[0]->invoice_id;
        } 
    }

    /**
     * save transfer candidate for given transfer, candidate, hours and bonus
     * @param type $candidate
     * @param type $model
     * @param type $value
     * @return type
     */
    public static function saveCandidateTransfer($candidate, $model, $value) {

        $total = 0;
        $company_total = 0;
        $hourly_rate = 0;

        $hourly_rate = $candidate['candidate_hourly_rate'];

        $store = $candidate['store'];
        $company = $candidate['company'];

        $TCModel = new \company\models\TransferCandidate;
        $TCModel->attributes = $value;
        $TCModel->transfer_cost = Yii::$app->params['transfer_cost'];
        $TCModel->candidate_hourly_rate = $hourly_rate;
        $TCModel->transfer_id = $model->transfer_id;
        $TCModel->store_id = $candidate['store_id'];
        $TCModel->store_name = $store['store_name'];
        $TCModel->company_id = $store['company_id'];
        $TCModel->company_name = $company['company_name'];
        $TCModel->company_email = $company['company_email'];
        $TCModel->bank_id = $candidate['bank_id'];
        $TCModel->transfer_benef_name = $candidate['bank_account_name'];
        $TCModel->transfer_benef_iban = $candidate['candidate_iban'];

        $company_bonus_commission = $company['company_bonus_commission'];
        $company_hourly_rate = $company['company_hourly_rate'];

        //if value not set take from parent company

        if(($company_bonus_commission + $company_hourly_rate == 0) && $company['parent_company_id'])
        {
            $parent = Company::findOne(['company_id' => $company['parent_company_id']]);

            if(!$parent)
            {
                return [
                    "operation" => "error",
                    "message" => "Parent not found."
                ];
            }

            $company_bonus_commission = $parent['company_bonus_commission'];
            $company_hourly_rate = $parent['company_hourly_rate'];
        }

        //if bonus commission or hourly rate not set

        if($company_bonus_commission == 0 && $company_hourly_rate == 0) {
            return [
                "operation" => "error",
                "message" => "Company hourly rate not set, please contact us for assistance"
            ];
        }

        //calculate and save bonus_commission
        $bonus = (float)$value['bonus'];
        $hours = (float)$value['hours'];
        $TCModel->bonus_commission = $bonus * $company_bonus_commission / 100;

        $TCModel->company_hourly_rate = $company_hourly_rate;

        if ($hours > 0 || $bonus > 0) {
            $total = $bonus - $TCModel->bonus_commission + ($hours * $hourly_rate) + Yii::$app->params['transfer_cost'];
            $company_total = $bonus + ($hours * $company_hourly_rate);
            $TCModel->candidate_total = round($total, 3);
            $TCModel->company_total = round($company_total, 3);
        }

        // in case if amount is 0
        if ($total  == 0) {
            return [
                "operation" => "error",
                "message" => "Can not create candidate transfer with 0 amount."
            ];
        }

        if (!$TCModel->save()) {

            if(isset($TCModel->errors)){
                return [
                    "operation" => "error",
                    "message" => $TCModel->errors
                ];
            }

            return [
                "operation" => "error",
                "message" => "We've faced an issue saving your request, please contact us for assistance."
            ];
        }

        return [
            "operation" => "success",
            "total" => $total,
            "company_total" => $company_total
        ];
    }

    /**
     * @inheritdoc
     * @return query\TransferCandidateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferCandidateQuery(get_called_class());
    }
}
