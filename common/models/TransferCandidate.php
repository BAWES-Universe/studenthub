<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use Segment\Segment;

/**
 * This is the model class for table "transfer_candidate".
 *
 * @property integer $tc_id
 * @property integer $transfer_id
 * @property integer $candidate_id
 * @property integer $prev_candidate_id
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
 * @property decimal $minutes - no of minutes candidate have worked
 * @property decimal $seconds - no of seconds candidate have worked
 * @property decimal $bonus - bonus amount company paying 
 * @property decimal $bonus_commission - commission admin will take from bonus in 
 * @property decimal $transfer_cost - transfer cost of payment
 * @property decimal $candidate_total - candidate total
 * @property decimal $company_total - company total
 * @property string $currency_code
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

    const SCENARIO_MARKING_PAID = "marking-paid";

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
            [['hours', "minutes", "seconds"], 'default', 'value'=> 0],

            [['currency_code'], 'required'],
            [['transfer_id', 'candidate_id', "prev_candidate_id", 'store_id', 'bank_id', 'company_id', 'transfer_file_id'], 'integer'],
            [['store_name', 'company_name'], 'string', 'max' => 225],
            [['company_email'], 'email'],
            [['transfer_confirmation_id'], 'string', 'max' => 128],
            [['transfer_benef_iban'], 'string', 'max' => 50],

            ['transfer_confirmation_id', 'unique', 'comboNotUnique' => 'Transfer confirmation id already exist.',
                'targetAttribute' => ['transfer_confirmation_id', "bank_id", 'deleted']],

            ['paid', 'validateStatus'],

            [['currency_code'], "string", "max" => 3],
            [['transfer_benef_name'], 'string', 'max' => 60],
            [['bank_id', 'transfer_confirmation_id', 'transfer_benef_name', 'transfer_benef_iban'], 'validateBankDetails'],
            [['transfer_cost', 'bonus', 'bonus_commission', 'candidate_hourly_rate', 'company_hourly_rate'], 'number'],

           // [['hours'], 'integer'],
            [['hours'], 'number'],
            [["minutes", "seconds"], "integer", "max" => 59],

            //[['hours'], 'validateHours'],

            [['tc_created_at', 'tc_updated_at'], 'safe'],
            
            ['company_hourly_rate', 'compare', 'compareAttribute' => 'candidate_hourly_rate', 'operator' => '>='],

            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::class, 'targetAttribute' => ['bank_id' => 'bank_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['prev_candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['prev_candidate_id' => 'candidate_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::class, 'targetAttribute' => ['transfer_id' => 'transfer_id']],
            [['transfer_file_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransferFile::class, 'targetAttribute' => ['transfer_file_id' => 'transfer_file_id']]
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
            return false;
        }

        return true;
    }
           
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'tc_created_at',
                'updatedAtAttribute' => 'tc_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return array|array[]
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[TransferCandidate::SCENARIO_MARKING_PAID] = [
            'paid',
            'transfer_file_id',
            "transfer_confirmation_id"
        ];

        return $scenarios;
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
            "prev_candidate_id"=> Yii::t('app','Prev Candidate ID'),
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
            'minutes' => Yii::t('app','Minutes'),
            'seconds' => Yii::t('app','Seconds'),
            'candidate_hourly_rate' => Yii::t('app','Candidate Hourly Rate'),
            'company_hourly_rate' => Yii::t('app','Company Hourly Rate'),
            'transfer_cost' => Yii::t('app','Transfer cost'),
            'bonus' => Yii::t('app','Bonus'),
            'bonus_commission' => Yii::t('app','Bonus Commission'),
            'tc_created_at' => Yii::t('app','Tc Created At'),
            'tc_updated_at' => Yii::t('app','Tc Updated At'),
            "currency_code" => Yii::t('app','Currency Code'),
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

        $fields['minutes'] = function ($model) {
            return (double)$this->minutes;
        };

        $fields['seconds'] = function ($model) {
            return (double)$this->seconds;
        };

        $fields['bonus'] = function ($model) {
            return (double)$this->bonus;
        };
        
        $fields['bonus_commission'] = function ($model) {
            return (double) $this->bonus_commission;
        };
        
        $fields['candidate_bonus'] = function ($model) {
            return (double) $this->bonus - (double) $this->bonus_commission;
        };
        
        $fields['transfer_cost'] = function ($model) {
            return (double) $this->transfer_cost;
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
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        if(!$this->currency_code) {
            $this->currency_code = $this->transfer->currency_code;
        }

        return true;
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

            $this->updateStats();

        } else if (isset($changedAttributes['paid']) && $this->paid == self::UNPAID) {

            $this->sendTransferUnpaidNotification();
        }
        
        return true;
    }

    /**
     * @return void
     */
    public function updateStats() {

        $profit = $this->getProfit();

        $candidateStat = CandidateStats::find()
            ->andWhere(['candidate_id' => $this->candidate_id, "currency_code" => $this->currency_code])
            ->one();

        // update if available

        if($candidateStat) {
            $candidateStat->updateCounters(['total_revenue' => $profit]);
        } else { // else add
            $candidateStat = new CandidateStats;
            $candidateStat->candidate_id = $this->candidate_id;
            $candidateStat->currency_code = $this->currency_code;
            $candidateStat->total_revenue = $profit;
            if(!$candidateStat->save()) {
                Yii::error(var_dump($candidateStat->errors));
            }
        }

        // check if available

        $stat = CompanyStats::find()
            ->andWhere(['company_id' => $this->company_id, "currency_code" => $this->currency_code])
            ->one();

        // update if available

        if($stat) {
            $stat->updateCounters(['total_revenue' => $profit]);
        } else { // else add
            $stat = new CompanyStats;
            $stat->company_id = $this->company_id;
            $stat->currency_code = $this->currency_code;
            $stat->total_revenue = $profit;
            if(!$stat->save()) {
                Yii::error(var_dump($stat->errors));
            }
        }
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
            'transferFile',
            'profit',
            'revenue',
            "duplicates"
        ];
    }

    public static function extractAccountNumber($iban) {
        return str_replace("000000000", "", substr($iban, 8));
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

        $model = new CandidateNotification();
        $model->candidate_id = $this->candidate_id;
        $model->tc_id = $this->tc_id;
        $model->company_id = $this->company_id;
        $model->store_id = $this->store_id;
        $model->type = CandidateNotification::TYPE_TRANSFER_PAID;
        if (!$model->save()) {
            Yii::error("Error saving notification: " . print_r($model->errors, true));
        }
    }

    /**
     * notify candidate on transfer marked as paid by admin
     */
    public function emailTransferSuccess() {

        if(!$this->candidate->candidate_email_verification) {
            return false;
        }

        $subjectLine = "KD " . number_format($this->totalPaidToCandidate, 3) . " has been transferred to your bank account";

        $name = $this->candidate->candidate_name? $this->candidate->candidate_name: $this->candidate->candidate_name_ar;

        if(YII_ENV != 'prod') {
            $subjectLine = '[Fake] [Ignore] ' . $subjectLine;
        }

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track('Candidate Transfer Paid',  [
                    'tc_id' => $this->tc_id,
                    'transfer_id' => $this->transfer_id,
                    'candidate_id' => $this->candidate_id,
                    'name' => $name,
                    'revenue' => $this->getProfit(),
                    'currency' => $this->transfer->currency_code
                ]);
        }

        $ml = new MailLog();
        $ml->to = $this->candidate->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = $subjectLine;
        $ml->save();

        $message = Yii::$app->mailer->compose('candidate/transfer-success',[
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
            ->setSubject($subjectLine);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $message->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return  $message->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * mobile notification on transfer marked as unpaid
     */
    public function sendTransferUnpaidNotification() 
    {
        if(YII_ENV == 'prod') {

            $name = !empty($this->candidate->candidate_name) ? $this->candidate->candidate_name: $this->candidate->candidate_name_ar;

            //Un-Paid
            Yii::$app->eventManager->track('Candidate Transfer Paid',  [
                    'tc_id' => $this->tc_id,
                    'transfer_id' => $this->transfer_id,
                    'candidate_id' => $this->candidate_id,
                    'name' => $name,
                    'revenue' => 0 - $this->getProfit(),
                    'currency' => $this->transfer->currency_code,
                    'transfer_cost' => $this->transfer_cost,
                    'candidate_total' => $this->candidate_total,
                    'company_total' => $this->company_total,
                ]);
        }

        $heading = Yii::t('app', 'Transfer marked as unpaid');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = $this->transfer->currency_code . ' ' . number_format($this->totalPaidToCandidate, 3);

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

        $model = new CandidateNotification();
        $model->candidate_id = $this->candidate_id;
        $model->tc_id = $this->tc_id;
        $model->company_id = $this->company_id;
        $model->store_id = $this->store_id;
        $model->type = CandidateNotification::TYPE_TRANSFER_UNPAID;
        if (!$model->save()) {
            Yii::error("Error saving notification: " . print_r($model->errors, true));
        }
    }
    
    /**
     * mobile notification on new transfer creation
     */
    public function sendNewTransferNotification() 
    {
        $heading = Yii::t('app', 'New transfer initiated');
        $subtitle = "@ " . $this->store_name . ', ' . $this->company_name;
        $content = $this->transfer->currency_code . ' ' . number_format($this->totalPaidToCandidate, 3);

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

        $model = new CandidateNotification();
        $model->candidate_id = $this->candidate_id;
        $model->tc_id = $this->tc_id;
        $model->company_id = $this->company_id;
        $model->store_id = $this->store_id;
        $model->type = CandidateNotification::TYPE_TRANSFER_INIT;
        if (!$model->save()) {
            Yii::error("Error saving notification: " . print_r($model->errors, true));
        }
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidToCandidate()
    {
        /*if(!isset(Yii::$app->params['transfer_cost'])) {
            Yii::$app->params['transfer_cost'] = 0;
        }*/

        //+ Yii::$app->params['transfer_cost']

        return $this->candidate_total;

        /*return round(
            ($this->candidate_hourly_rate * $this->hours) + $this->bonus - $this->bonus_commission,
            3
        );*/
    }

    /**
     * Revenue
     * @return string
     */
    public function getRevenue()
    {
        return $this->totalPaidByCompany;
    }

    /**
     * Revenue - Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidByCompany()
    {
        return $this->company_total; //($this->company_hourly_rate * $this->hours) + $this->bonus;
    }

    /**
     * gross profit
     * @return decimal|float|int
     */
    public function getProfit()
    {
        return (double) $this->company_total - (double) $this->candidate_total;
        //(($this->company_hourly_rate - $this->candidate_hourly_rate) * $this->hours) + $this->transfer_cost
        //    + $this->bonus_commission;
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
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getPrevCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'prev_candidate_id']);
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
    public function getDuplicates()
    {
        return self::find()
            ->andWhere(new Expression("transfer_candidate.candidate_id = ". $this->candidate_id ." AND transfer_candidate.company_id = ".$this->company_id." AND YEAR(transfer_candidate.tc_created_at) = YEAR('". $this->tc_created_at."') AND MONTH(transfer_candidate.tc_created_at) = MONTH('". $this->tc_created_at."') AND deleted = 0 AND transfer_candidate.tc_id != " . $this->tc_id))
            ->all();
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
    public static function getPayableCandidateListFormat($currency_code = "KWD", $offset = null, $limit = null)
    {
        $totalAmount = 0;

        $query = self::find()
            ->payable()
            ->havingBankInfo()
            ->activeCivilId()
            ->andWhere(['transfer_candidate.currency_code' => $currency_code]);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $transferCandidates = $query
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
                'currency' => $transferCandidate->transfer->currency_code,
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
     * get list of transferable candidate
     * for text export
     * @return array
     */
    public static function getPayableCandidateAdvice($currency_code = "KWD", $offset = null, $limit = null)
    {
        $totalAmount = 0;

        $query = self::find()
            ->payable()
            ->havingBankInfo()
            ->activeCivilId()
            ->andWhere(['transfer_candidate.currency_code' => $currency_code]);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $transferCandidates = $query
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
                'Section Index' => 'D',
                'Reference Number' => $transferCandidate->transfer_id,//Debit Narrative 1
                'Email ID' => $candidate->candidate_email,
                'Invoice Date' => date('dmY'),
                'Invoice Info' => $transferCandidate->tc_id,
                //'Invoice No' => $transferCandidate->tc_id,
                'Invoice Currency' => $transferCandidate->transfer->currency_code,
                'Invoice Amount' => number_format($transferCandidate->totalPaidToCandidate, 3, '.', '')
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
     * @param number $noOfPayout - how many times we paying per month, so can divide and pay
     * @return type
     */
    public static function saveCandidateTransfer($candidate, $model, $value, $noOfPayout = 1) {

        if (!isset($value['minutes'])) {
            $value['minutes'] = 0;
        }

        if (!isset($value['seconds'])) {
            $value['seconds'] = 0;
        }

        if(!isset(Yii::$app->params['transfer_cost'])) {
            Yii::$app->params['transfer_cost'] = 0;
        }

        $store = $candidate['store'];
        $company = $candidate['company'];

        $TCModel = new \company\models\TransferCandidate;
        $TCModel->attributes = $value;
        $TCModel->transfer_id = $model->transfer_id;
        $TCModel->store_id = $candidate['store_id'];
        if ($store) {
            $TCModel->store_name = $store['store_name'];
        }
        $TCModel->company_id = $store['company_id'];
        $TCModel->company_name = $company['company_name'];
        $TCModel->company_email = $company['company_email'];
        $TCModel->bank_id = $candidate['bank_id'];
        $TCModel->transfer_benef_name = $candidate['bank_account_name'];
        $TCModel->transfer_benef_iban = $candidate['candidate_iban'];

        $total = 0;
        $company_total = 0;
        $minute_rate = 0;
        $second_rate = 0;

        //todo: ability to override from contract form
        $company_bonus_commission = $company['company_bonus_commission'];

        if (!$model->contract)
        {
            $assignment = CandidateWorkHistory::find()
                ->andWhere ([
                    'candidate_id' => $candidate['candidate_id'],
                    'store_id' => $candidate['store_id'],
                ])
                ->orderBy(new Expression('start_date DESC'))
                ->one();

            $TCModel->candidate_hourly_rate = $assignment ? $assignment->candidate_hourly_rate : $candidate['candidate_hourly_rate'];
            $minute_rate = $TCModel->candidate_hourly_rate / 60;
            $second_rate = $minute_rate / 60;

            $TCModel->company_hourly_rate = $assignment && $assignment->company_hourly_rate > 0 ?
                $assignment->company_hourly_rate: $company['company_hourly_rate'];

            //if value not set take from parent company

            if(($company_bonus_commission + $TCModel->company_hourly_rate == 0) && $company['parent_company_id'])
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

                $TCModel->company_hourly_rate = $parent['company_hourly_rate'];
            }

            $TCModel->transfer_cost = $assignment ? $assignment->getTransferCost() : Yii::$app->params['transfer_cost'];
        }
        else if ($model->contract->type == Contract::TYPE_HOURLY)
        {
            $TCModel->candidate_hourly_rate = $model->contract->amount->candidate_hourly_rate;

            $TCModel->company_hourly_rate = $model->contract->amount->company_hourly_rate;

            $minute_rate = $TCModel->candidate_hourly_rate / 60;
            $second_rate = $minute_rate / 60;

            $TCModel->transfer_cost = $model->contract->transfer_cost;
        }
        else if ($model->contract->type == Contract::TYPE_MONTHLY_SALARY)
        {
            $TCModel->transfer_cost = $model->contract->transfer_cost;

            $TCModel->candidate_total = $model->contract->amount->candidate_total/ $noOfPayout;

            $TCModel->company_total = $model->contract->amount->company_total/ $noOfPayout;
        }
        else if ($model->contract->type == Contract::TYPE_FIXED_PRICE)
        {
            $TCModel->transfer_cost = $model->contract->transfer_cost;

            //todo: based on contract completion percentage
            $TCModel->candidate_total = $model->contract->amount->candidate_total;

            $TCModel->company_total = $model->contract->amount->company_total;
        }

        // hourly based calculation

        if (!$model->contract || $model->contract->type == Contract::TYPE_HOURLY) {

            //if bonus commission or hourly rate not set

            if($company_bonus_commission == 0 && $TCModel->company_hourly_rate == 0) {
                return [
                    "operation" => "error",
                    "message" => "Company hourly rate not set, please contact us for assistance"
                ];
            }

            $company_minute_rate = $TCModel->company_hourly_rate/ 60;
            $company_second_rate = $company_minute_rate / 60;

            //calculate and save bonus_commission

            $bonus = (float)$value['bonus'];

            $hours = (float)$value['hours'];

            $minutes = (float)$value['minutes'];

            $seconds = (float)$value['seconds'];

            $TCModel->bonus_commission = $bonus * $company_bonus_commission / 100;

            if ($minutes > 0 || $seconds > 0 || $hours > 0 || $bonus > 0) {

                $total = $bonus - $TCModel->bonus_commission + ($hours * $TCModel->candidate_hourly_rate) + ($minutes * $minute_rate)
                    + ($seconds * $second_rate);

                $company_total = $bonus + ($hours * $TCModel->company_hourly_rate) + ($minutes * $company_minute_rate)
                    + ($seconds * $company_second_rate) + $TCModel->transfer_cost ;

                $TCModel->candidate_total = round($total, 3);

                $TCModel->company_total = round($company_total, 3);
            }
        }

        // in case if amount is 0
        if ($TCModel->company_total  == 0) {
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
            "total" => $TCModel->candidate_total,
            "company_total" => $TCModel->company_total,
            "transfer_cost" => $TCModel->transfer_cost
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
