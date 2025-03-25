<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "company".
 *
 * @property integer $company_id
 * @property integer $parent_company_id
 * @property integer $staff_id
 * @property integer $country_id
 * @property string $company_name
 * @property string $company_common_name_en
 * @property string $company_common_name_ar
 * @property string $company_description_en
 * @property string $company_description_ar
 * @property string $company_website
 * @property string $company_logo
 * @property string $commercial_licence
 * @property string $company_email
 * @property decimal $company_hourly_rate
 * @property decimal $company_bonus_commission - % Of Bonus admin will take
 * @property string $currency_code 
 * @property boolean $company_followup
 * @property integer $total_candidate
 * @property integer $no_of_active_requests
 * @property integer $is_request_updates_in_30_days
 * @property boolean $company_followup_interval_weeks
 * @property datetime $company_last_followup_datetime
 * @property datetime $company_next_followup_datetime
 * @property boolean $company_approved_to_hire
 * @property integer $company_status
 * @property integer $company_status_override
 * @property integer $company_created_at
 * @property integer $company_updated_at
 * @property string|null $last_payment_datetime
 * @property string|null $last_request_datetime
 * @property integer $deleted
 *
 * @property Company $parentCompany
 * @property Company[] $subCompanies
 * @property Candidate[] $candidates
 * @property Invoice[] $invoices
 * @property Store[] $stores
 * @property Transfer[] $transfers
 * @property Transfer[] $parentTransfers
 * @property Store[] $subCompanyStores
 * @property Note[] $notes
 * @property Contract[] $contracts
 *
 * E.g.
 * company_hourly_rate = 1.5 KWD
 * company_bonus_commission = 20%
 * 
 * candidate 1 have worked for 2 hour having hourly rate 1.2 KWD + suppose getting bonus 20 KWD 
 * 
 * Total amount company will pay = (1.5 KWD * 2 hour) + 20 KWD = 23 KWD
 * 
 * Total amount admin will pay to candidate = (1.2 KWD * 2 hour) + 20 KWD - 20% of bonus as comission 
 *  = 2.4 KWD + 20 KWD - 4 KWD 
 *  = 18.4 KWD
 */
class Company extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 10;
    const STATUS_UNDER_REVIEW = 9;
    const STATUS_INACTIVE = 0;

    const SCENARIO_ACTIVATE = "activate";
    const SCENARIO_APPROVE = "approve";
    const SCENARIO_UPDATE = "update";

    private $_cacheDuration = 60 * 60; //1 hour then delete from cache

    /**
     * @var mixed|null
     */

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'company';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_name','company_common_name_en','company_common_name_ar', 'company_bonus_commission', 'currency_code', "country_id"], 'required'],
            [['company_email', 'company_hourly_rate'], 'required', 'on' => 'newAccount'],//, 'commercial_licence'
            [['company_email'], 'unique', 'on'=>'newAccount'],
            [['company_email'], 'email' , 'on'=>'newAccount'],
            [['company_hourly_rate'], 'required', 'on'=>'newSubAccount'], // for sub account
            [['parent_company_id', 'company_followup_interval_weeks','total_candidate','no_of_active_requests','is_request_updates_in_30_days'], 'integer'],
            [['company_followup'], 'boolean'],
            ['company_last_followup_datetime', 'safe'],
            ['company_next_followup_datetime', 'safe'],
            [['company_bonus_commission', 'company_hourly_rate', 'company_status_override'], 'number'],
            [['parent_company_id'], 'validateCompany'],
            ['company_hourly_rate', 'validateHourlyRate'],
            [['company_name', 'company_email', 'company_common_name_en','company_common_name_ar'], 'string', 'max' => 255],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['company_common_name_en','company_common_name_ar','company_description_en','company_description_ar','company_website',
                'company_status_override', 'last_request_datetime', 'last_payment_datetime'], 'safe'],
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['commercial_licence'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('app',"Please upload commercial licence"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['company_logo'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('app',"Please upload a Company Logo"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'country_id']],
        ];
    }
  
    /**
     * Company hourly rate should be higher than his candidates' hourly rate 
     */
    public function validateHourlyRate() 
    {
        $result = $this->getCandidates()
            ->andWhere(['>', 'candidate_hourly_rate', $this->company_hourly_rate])
            ->orderBy('candidate_hourly_rate DESC')
            ->one();
        
        if($result)
        {
            $this->addError('candidate_hourly_rate', "Company has candidates with higher hourly rate.");
        }
    }
    
    /**
     * find if company have store
     */
    public function validateCompany()
    {
        if(!$this->parentCompany) { 
            $this->addError('parent_company_id', "Parent Company not found.");
        } 
        
        if($this->parentCompany && $this->parentCompany->stores) {
            $this->addError('parent_company_id', "Company can't be assigned to company having stores.");
        }
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'company_created_at',
                'updatedAtAttribute' => 'company_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_ACTIVATE] = ['company_logo', 'commercial_licence', 'company_description_en',
            'company_description_ar', 'company_website', 'company_status_override', "currency_code", "country_id"];

        $scenarios[self::SCENARIO_APPROVE] = ["company_name", "company_common_name_en", "company_common_name_ar",
            "company_email", "company_bonus_commission", "company_approved_to_hire", "company_followup", "company_followup_interval_weeks",
            "company_last_followup_datetime", "company_next_followup_datetime", "currency_code", "country_id"];

        $scenarios[self::SCENARIO_UPDATE] = ["company_name", "company_common_name_en", "company_common_name_ar",
            "company_description_en", "company_description_ar", "company_website",
            "company_email", "currency_code", "country_id"];

        $scenarios['updateFollowup'] = ['company_followup'];
        $scenarios['updateStaff'] = ['staff_id'];
        $scenarios['updateStatus'] = ['company_status_override'];
        $scenarios['updateFollowupInterval'] = ['company_followup_interval_weeks'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id' => Yii::t('app','Company ID'),
            'parent_company_id' => Yii::t('app','Parent Company'),
            'staff_id' => Yii::t('app','staff id'),
            'company_name' => Yii::t('app','Company Name'),
            'company_common_name_en' => Yii::t('app','Company Common Name English'),
            'company_common_name_ar' => Yii::t('app','Company Common Name Arabic'),
            'company_description_en' => Yii::t('app','Company Description English'),
            'company_description_ar' => Yii::t('app','Company Description Arabic'),
            'company_website' => Yii::t('app','Company Website'),
            'company_email' => Yii::t('app','Company Email'),
            'company_logo' => Yii::t('app','Company Logo'),
            'commercial_licence'  => Yii::t('app','Commercial Licence'),
            'company_followup' => Yii::t('app','Company Followup'),
            'company_status_override' => Yii::t('app','Status Override'),
            'company_created_at' => Yii::t('app','Company Created At'),
            'company_updated_at' => Yii::t('app','Company Updated At'),
            'last_request_datetime'=> Yii::t('app','Last Request At'),
            'last_payment_datetime'=> Yii::t('app','Last Payment At'),
            "currency_code" => Yii::t('app', "currency_code"),
            "country_id" => Yii::t('app', "country_id"),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted']);

        $fields['company_status'] = function($model) {

            if($this->company_status_override) {
                return $this->company_status_override;
            }

            if(
                $this->total_candidate > 0 ||
                $this->is_request_updates_in_30_days > 0 ||
                $this->no_of_active_requests > 0
            ) {
                return self::STATUS_ACTIVE;
            }

            return self::STATUS_INACTIVE;
        };

        return $fields;
    }

    /**
     * @return int
     */
    public function getCompany_status() {

        if($this->company_status_override) {
            return $this->company_status_override;
        }

        if(
            $this->total_candidate > 0 ||
            $this->is_request_updates_in_30_days > 0 ||
            $this->no_of_active_requests > 0
        ) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_INACTIVE;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            "contracts",
//            'company',
            'candidates',
            'suggestions',
            'subCompanies',
            'stores',
            'files',
            'brands',
            'notes',
            'requests',
            'parentTransfers',
            'malls',
            'companyContacts',
            'contacts',
            'profit',
            'revenue',
            'staff',
            'country',
            'companyStats',
            /**
             * Staff: If a company is "Active" and we have not received any payment from them in last 40 days
             * (ignore transfer drafts and locked). Show on the company listing card a red badge saying
             * "40 days passed without payment"
             */
            'transferInLast40Days' => function($model) {

                return (int) $model->getTransfers()
                    ->andWhere([
                        'AND',
                        [
                            'in',
                            'transfer_status',
                            [
                                Transfer::STATUS_PAYMENT_SENT,
                                Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                                Transfer::STATUS_TRANSFER_COMPLETE
                            ]
                        ],
                        new Expression("DATE(transfer_created_at) > DATE_SUB(NOW(),INTERVAL 40 DAY)")
                    ])
                    ->count();
            },
            "averageHireRate",
            "averageHourlyRate",
            "totalHire",
            "totalActiveHire",
            "totalHoursHired",
            "totalSpent",
            "totalRequests",
            "totalOpenRequests"
        ];
    }

    /*
     * # of jobs posted,
     * current open requests,
     * total money spent,
     * total hours hired ppl for.
     * total hires and
     * total active hires,
     * average hourly rate and
     *
     * hire rate,
     * average hire rates,
     */

    /**
     * 100 * total contract (hired) / total suggestion
     * @return float
     */
    public function getAverageHireRate(): float
    {
        return self::getDb()->cache(function($db) {

            $totalSuggestion = Suggestion::find()
                ->joinWith(['request'])
                ->andWhere(["request.company_id" => $this->company_id])
                ->count();

            if ($totalSuggestion == 0)  {
                return 0;
            }

            $totalContract = Contract::find()
                ->select("contract_uuid")
                ->andWhere([
                    "OR",
                    ["company_id" => $this->company_id],
                    ["parent_company_id" => $this->company_id],
                ])
                ->count();

            return round(100 * $totalContract / $totalSuggestion, 2);
        }, $this->_cacheDuration);//$cacheDependency
    }

    /**
     * @return float
     */
    public function getAverageHourlyRate() {
        return self::getDb()->cache(function($db) {
            $contractQuery = Contract::find()
                ->select("contract_uuid")
                ->andWhere([
                    "OR",
                    ["company_id" => $this->company_id],
                    ["parent_company_id" => $this->company_id],
                ]);

            return (float) HourlyContract::find()
                ->andWhere(["IN", "contract_uuid", $contractQuery])
                ->average("company_hourly_rate");

        }, $this->_cacheDuration);//$cacheDependency
    }

    public function getTotalHire()
    {
        return self::getDb()->cache(function($db) {
            return Contract::find()
                ->andWhere([
                    "OR",
                    ["company_id" => $this->company_id],
                    ["parent_company_id" => $this->company_id],
                ])
                ->count();
        }, $this->_cacheDuration);//$cacheDependency
    }

    public function getTotalActiveHire()
    {
        return self::getDb()->cache(function($db) {
            return Contract::find()
                ->andWhere([
                    "OR",
                    ["company_id" => $this->company_id],
                    ["parent_company_id" => $this->company_id],
                ])
                ->filterActive()
                ->count();
        }, $this->_cacheDuration);//$cacheDependency
    }

    /**
     * @param $modelClass
     * @return mixed
     */
    public function getActiveContracts($modelClass = "\common\models\Contract")
    {
        return $this->getContracts($modelClass)
            ->andWhere(['contract.deleted' => false])
            ->filterActive();
    }

    public function getTotalHoursHired() {
        return self::getDb()->cache(function($db) {
            return TransferCandidate::find()
                ->andWhere(["IN", "company_id", [
                    $this->company_id,
                    ArrayHelper::getColumn($this->getSubCompanies()->select('company_id')->all(), 'company_id'),
                ]])
                ->andWhere(['deleted' => 0])
                ->select("SUM(hours) + SUM(minutes / 60) + SUM(seconds / 3600)")
                ->scalar();
        }, $this->_cacheDuration);//$cacheDependency
    }

    public function getTotalSpent() {
        return self::getDb()->cache(function($db) {
            return $this->getTransfers()
                ->andWhere(['deleted' => 0])
                ->sum("company_total");
        }, $this->_cacheDuration);//$cacheDependency
    }

    public function getTotalRequests() : int {
        return $this->getRequests()->count();
    }

    public function getTotalOpenRequests() {
        return self::getDb()->cache(function($db) {
            return $this->getRequests()
                ->andWhere([
                    'NOT IN',
                    "request_status",
                    [
                        Request::STATUS_DELIVERED,
                        Request::STATUS_CANCELLED
                    ]
                ])
                ->count();
        }, $this->_cacheDuration);//$cacheDependency
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        /*if (array_key_exists('company_logo', $changedAttributes)) {
            $this->updateCompanyLogo();
        }

        if (array_key_exists('commercial_licence', $changedAttributes)) {
            $this->updateLicence();
        }*/

        if (array_key_exists('company_status_override', $changedAttributes) &&
            $changedAttributes['company_status_override'] == self::STATUS_UNDER_REVIEW
        ) {
            $contacts = $this->getContacts()
                ->andWhere(['!=', 'contact_email', $this->company_email])
                ->all();

            $contactEmails = ArrayHelper::getColumn($contacts, 'contact_email');

            $ml = new MailLog();
            $ml->to = $this->company_email;
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = 'Your account is live now, let’s explore';
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }

            Yii::$app->mailer->compose ([
                'html' => 'company/account-live-email-html',
                //  'text' => 'company/account-live-email-text',
            ], [
                'company' => $this
            ])
                ->setFrom ([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
                ->setSubject ('Your account is live now, let’s explore')
                ->setTo ($this->company_email)
                ->setCc($contactEmails)
                ->send ();
        }

        if(YII_ENV == 'prod' && !$insert) {
            Yii::$app->eventManager->track(
                'Company Profile Updated',
                $this->attributes);
        }
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\common\models\Request")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\common\models\Suggestion")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
                    ->via('requests');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations() {
        $subQuery = Request::find()->select('request_uuid')->andWhere(['company_id'=>$this->company_id]);
        return Invitation::find()->andWhere(['in', 'request_uuid', $subQuery]);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['parent_company_id' => 'company_id'])
            ->andWhere(['{{%company}}.deleted' => 0]);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency($modelClass = "\common\models\Currency")
    {
        return $this->hasOne($modelClass::className(), ['code' => 'currency_code']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferRates($modelClass = "\common\models\TransferCost")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        $query = null;

        if($this->subCompanyStores)
        {
            //for parent company
            $query = $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
                ->via('subCompanyStores');
        }
        else
        {
            //for child company
            $query = $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
                ->via('stores');
        }

        return $query->andWhere(['{{%candidate}}.deleted' => 0]);/*
            ->andWhere([
                "IN",
                "candidate.candidate_id",
                $this->getActiveContracts()
                   // ->andWhere([''])
                    ->andWhere(new Expression("contract.candidate_id IS NOT NULL"))
                    ->select('contract.candidate_id')
            ]);*/
    }

    /**
     * @return void
     */
    public static function requestForAttendance() {

        $subject = "Request for Attendance and Working Hours for Part-Time Employees";

        if(YII_ENV != 'prod') {
            $subject = '[Fake] [Ignore] ' . $subject;
        }

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $mailer = Yii::$app->mailer->compose("company/request-working-hours",
            [
                'logo' => Yii::$app->urlManagerStaff->createUrl(
                    '../images/logo.png'
                )
            ])
            ->setFrom([Yii::$app->params['finance_transfer'] => Yii::$app->params['appName']])
            ->setSubject($subject);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        $companiesQuery = Company::find()
            ->andWhere(new Expression('parent_company_id IS NULL AND total_candidate > 0'));

        foreach ($companiesQuery->batch(100) as $companies) {

            foreach ($companies as $company) {

                $ml = new MailLog();
                $ml->to = $company->company_email;
                $ml->from = \Yii::$app->params['finance_transfer'];
                $ml->subject = $subject;
                if (!$ml->save()) {
                    Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                }

                $mailer
                    ->setTo($company->company_email);

                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    // Handle email transport-specific exceptions
                    Yii::error( "Failed to send email: " . $e->getMessage());
                } catch (\Exception $e) {
                    // Handle any other exceptions
                    Yii::error( "An error occurred: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\common\models\Invoice")
    {
        if(!$this->parent_company_id) //parent company         
        {
            return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
                ->via('subCompanies');
        }
        else //child company
        {
            return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
        }        
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->andWhere(['store.deleted'=>0]);
    }

    /**
     * candidate work history
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistories($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $modelClass::find()
            ->andWhere([
                "OR",
                ['company_id' => $this->company_id],
                ["parent_company_id" => $this->company_id]
            ]);

        /*$this->hasMany($modelClass::className(), [
            "OR",
            ['company_id' => 'company_id'],
            ["parent_company_id" => "company_id"]
        ]);*/
    }

    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->andWhere('parent_transfer_id IS NULL')
            ->orderBy('transfer_id DESC');
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getSubCompanyStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->via('subCompanies')
            ->andWhere(['store.deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrands($modelClass = "\common\models\Brand")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContacts($modelClass = "\common\models\Contact")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid'])
            ->via('companyContacts');
    }

    /**
     * Revenue
     * @return string
     */
    public function getRevenue()
    {
        return (double) $this->getTransfers ()
            ->filterPaymentReceived()
            ->sum ('transfer.company_total');
    }

    /**
     * Revenue
     * @return string
     */
    public function getProfit()
    {
        return (double) $this->getTransfers ()
            ->filterPaymentReceived()
            ->sum ('transfer.company_total - transfer.total');
    }

    /**
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;

        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\CompanyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CompanyQuery(get_called_class());
    }

    /**
     * @param $company_id
     * @return int|string
     */
    public static function getTotalCandidateCount($company_id){

        // create company_id array from all sub companies and self
        $companies = Company::findAll(['parent_company_id' => $company_id]);
        $company_ids = yii\helpers\ArrayHelper::map($companies, 'company_id', 'company_id');
        $company_ids[] = $company_id;

        return Store::find()
            ->andWhere(['in', 'company_id', $company_ids])
            ->sum('store_total_candidates');
    }

    /**
     * Update licence photo from temp s3 bucket
     * @return type
     */
    public function updateLicence() {

        try {

            $url = Yii::$app->temporaryBucketResourceManager->getUrl($this->commercial_licence);

            return $this->setLicence($url);

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('commercial_licence', Yii::t('app', $e->getMessage() . ': Image not available to save.'));//

            return false;
        }
    }

    /**
     * Set licence photo by url
     * @param string $url
     */
    public function setLicence($url) {

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->commercial_licence) {
            $this->deleteLicenceFromCloudinary();
        }

        try {
            $path = (YII_ENV == 'prod') ?  "commercial-licence/" : "dev/commercial-licence/";

            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' => $path . $filename,
                    "eager" => [
                        [
                            //id card thumbnail
                            "width" => 319, "height" => 319, "crop" => "thumb", "gravity" => "face",
                        ],
                        [
                            //profile pic in apps
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->commercial_licence = "commercial-licence/" . basename($result['url']);

                return true;
            }

        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('commercial_licence', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('commercial_licence', Yii::t('app', $e->getMessage() . ': Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old licence photo from cloudinary
     * @return boolean
     */
    public function deleteLicenceFromCloudinary() {

        try {
            $path = (YII_ENV == 'prod') ? "" : "dev/";

            if(isset($this->oldAttributes['commercial_licence'])) {
                return Yii::$app->cloudinaryManager->delete($path . $this->oldAttributes['commercial_licence']);
            } else {
                return Yii::$app->cloudinaryManager->delete($path . $this->commercial_licence);
            }

        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'company');

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');
            return false;
        }
    }

    /**
     * Update profile photo from temp s3 bucket
     * @return type
     */
    public function updateCompanyLogo() {

        try {

            $url = Yii::$app->temporaryBucketResourceManager->getUrl($this->company_logo);

            return $this->setCompanyLogo($url);

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('company_logo', Yii::t('app', $e->getMessage(). ': Image not available to save.'));

            return false;
        }
    }

    /**
     * Set profile photo by url
     * @param string $url
     */
    public function setCompanyLogo($url) {

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->company_logo) {
            $this->deleteProfilePhotoFromCloudinary();
        }

        try {
            $path = (YII_ENV == 'prod') ?  "company-logo/" : "dev/company-logo/";

            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' => $path . $filename,
                    "eager" => [
                        [
                            //id card thumbnail
                            "width" => 319, "height" => 319, "crop" => "thumb", "gravity" => "face",
                        ],
                        [
                            //profile pic in apps
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->company_logo = "company-logo/" . basename($result['url']);

                return true;
            }

        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('company_logo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');

            $this->addError('company_logo', Yii::t('app', $e->getMessage(). ': Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old profile photo from cloudinary
     * @return boolean
     */
    public function deleteProfilePhotoFromCloudinary() {

        try {

            $path = (YII_ENV == 'prod') ? "" : "dev/";

            if(isset($this->oldAttributes['company_logo'])) {
                return Yii::$app->cloudinaryManager->delete($path . $this->oldAttributes['company_logo']);
            } else {
                return Yii::$app->cloudinaryManager->delete($path . $this->company_logo);
            }
            
        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'company');

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'company');
            return false;
        }
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // in case update

        if(
            !$insert && (
                $this->company_last_followup_datetime != $this->oldAttributes['company_last_followup_datetime'] ||
                $this->company_followup_interval_weeks != $this->oldAttributes['company_followup_interval_weeks']
            )
        ) {
            $this->company_next_followup_datetime = new Expression("DATE_ADD(company_last_followup_datetime,INTERVAL company_followup_interval_weeks WEEK)");
            //UPDATE company SET company_next_followup_datetime = DATE_ADD(company_last_followup_datetime,INTERVAL company_followup_interval_weeks WEEK)
        }

        if (
            !$this->isNewRecord &&
            $this->company_logo &&
            $this->company_logo != $this->oldAttributes['company_logo'] &&
            !$this->updateCompanyLogo()
        ) {
            return false;
        }

        // in case create

        if ($this->isNewRecord && $this->company_logo && !$this->updateCompanyLogo()) {
            return false;
        }

        // in case update

        if (
            !$this->isNewRecord &&
            $this->commercial_licence &&
            $this->commercial_licence != $this->oldAttributes['commercial_licence'] &&
            !$this->updateLicence()
        ) {
            return false;
        }

        // in case create

        if ($this->isNewRecord && $this->commercial_licence && !$this->updateLicence()) {
            return false;
        }

        if(!$this->currency_code) {
            $this->currency_code = "KWD";
        }

        return true;
    }

    /**
     * @param $currency_code
     * @return bool|int|string|null
     */
    public static function companyFollowupCount($currency_code = "KWD") {
        $query = self::find()
            ->followups()
            ->filterParent();

        if($currency_code) {
            $query->andWhere(['company.currency_code' => $currency_code]);
        }

        return $query->count();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getMalls($modelClass = "\common\models\Mall") {

        if($this->subCompanyStores)
        {
            //for parent company
            return $this->hasMany($modelClass::className(), ['mall_uuid' => 'mall_uuid'])
                ->via('subCompanyStores');
        }
        else
        {
            return $this->hasMany($modelClass::className(), ['mall_uuid' => 'mall_uuid'])
                ->via('stores');
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles($modelClass = "\common\models\File")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * https://www.pivotaltracker.com/story/show/175798834
     * method is used to find active company dynamically
     * update company table no_of_active_requests /is_request_updates_in_30_days
     * on every request create, update and delete
     * @param $rid
     * @throws \yii\db\Exception
     */
    public static function updateRequest($company_id = 0) {

        $company = Company::findOne($company_id);

        $ID = ($company->parent_company_id) ? $company->parent_company_id : $company_id;

        if ($company_id) {
            // check total request for parent company and child company.
            // to update no_of_active_requests everytime request updated
            $q = 'SELECT count(*) FROM request left join company on request.company_id = company.company_id ';
            $q .= "where (company.company_id = $company_id or company.parent_company_id =$company_id) AND request.request_status = 'started'";
            $requestQuery = Yii::$app->db->createCommand($q)->queryScalar();
            
            Yii::$app->db->createCommand()->update('company', ['no_of_active_requests' => $requestQuery], 'company_id = ' . $ID)->execute();

            // check total request for parent company and child company.in last 30 days
            // to update is_request_updates_in_30_days everytime request updated
            $q30Days = 'SELECT count(*) FROM request left join company on request.company_id = company.company_id ';
            $q30Days .= "where (company.company_id = $company_id or company.parent_company_id =$company_id) AND ";
            $q30Days .= "DATE(request.request_updated_datetime) > DATE_SUB(NOW(),INTERVAL 30 DAY)";
            $request30daysQuery = Yii::$app->db->createCommand($q30Days)->queryScalar();

            Yii::$app->db->createCommand()->update('company', ['is_request_updates_in_30_days' => ($request30daysQuery) ? 1 : 0], 'company_id = ' . $ID)->execute();
        }
    }

    /**
     * https://www.pivotaltracker.com/story/show/175798834
     * method is used to find active company dynamically
     * update student counter
     * @param $store_id
     * @param $counter
     */
    public static function updateCandidate($store_id, $counter) {
        $store = Store::findOne($store_id);
        if ($store) {
            $company = $store->company;
            Company::updateAllCounters(
                ['total_candidate' => $counter],
                ['company_id' => ($company->parent_company_id) ? $company->parent_company_id : $company->company_id]
            );
        }
    }

    /*
     *  Add card to the top that should show when we have
     *  active client with staff assigned and hasn't made payment in 40 days
     */
    public static function companiesCountWithNoPaymentIn40Days($currency_code = null) {

        $query = Company::find()
            ->filterParent()
            ->filterByActive40DaysPassedWithoutPayment()
            ->notDeleted();

        if($currency_code) {
            $query->andWhere(['company.currency_code' => $currency_code]);
        }

        return $query->count();
    }

    /*
     *  Add card to the top that should show when we have
     *  active client with staff assigned and hasn't made payment in 40 days
     */
    public static function last40daysWithoutRequest($currency_code = "KWD") {
        $query = Company::find()
            ->filterParent()
            ->filterActive()
           // ->andWhere(new \yii\db\Expression("company_created_at < DATE_SUB(NOW(),INTERVAL 40 DAY)"))//last 40 day
            ->filterByActive40DaysPassedWithoutRequest()
            ->notDeleted();

        if($currency_code) {
            $query->andWhere(['company.currency_code' => $currency_code]);
        }

        return $query->count();
    }

    /**
     * @return void
     */
    public function notifyUnderReview() {

        $ml = new MailLog();
        $ml->to = "sales@bawes.net";
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "[Studenthub] Company under review!";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->compose ([
            'html' => 'company/under-review-email-html',
          //  'text' => 'company/under-review-email-text',
        ], [
            'company' => $this
        ])
            ->setFrom ([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            //->setTo ($model->contact_email)
            ->setTo('sales@bawes.net')
            ->setCc (['meet@bawes.net'])
            ->setSubject ('[Studenthub] Company under review!')
            ->send ();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyStats($modelClass = "\common\models\CompanyStats")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStoreManager($modelClass = "\common\models\StoreManager")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContracts($modelClass = "\common\models\Contract")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->orderBy("contract.created_at DESC");
    }
}
