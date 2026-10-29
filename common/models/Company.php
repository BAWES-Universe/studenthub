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
    const SCENARIO_REPLACE_LOGO = 'replaceLogo';
    const SCENARIO_REPLACE_LICENCE = 'replaceLicence';
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
            [['commercial_licence'], 'validatePrivateTemporaryLicence'],
            [['commercial_licence'], 'validateDocumentReplacement', 'params' => ['kind' => 'licence']],
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['company_logo'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('app',"Please upload a Company Logo"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function ($model) {
                    return self::shouldValidateTemporaryUpload(
                        $model->company_logo,
                        $model->getOldAttribute('company_logo'),
                        'logo'
                    );
                }
            ],
            [['company_logo'], 'validateDocumentReplacement', 'params' => ['kind' => 'logo']],
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

        $scenarios[self::SCENARIO_REPLACE_LOGO] = ['company_logo'];
        $scenarios[self::SCENARIO_REPLACE_LICENCE] = ['commercial_licence'];
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

        if (!self::exposesCommercialLicence()) {
            unset($fields['commercial_licence']);
        }
        $fields['company_logo_url'] = function ($model) {
            return $model->getCompanyLogoUrl();
        };

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

        // Replaced licence and logo objects stay in storage. Deleting them here would run
        // before an outer transaction commits, so a later rollback would lose the previous file.

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

    public const COMMERCIAL_LICENCE_S3_PREFIX = 'company-commercial-licences/';
    public const PRIVATE_COMMERCIAL_LICENCE_S3_PREFIX = 'private/company-commercial-licences/';
    public const PRIVATE_TEMPORARY_LICENCE_PREFIX = 'privatelicence-';
    public const LEGACY_COMMERCIAL_LICENCE_PREFIX = 'commercial-licence/';
    public const COMPANY_LOGO_S3_PREFIX = 'company-logos/';
    public const LEGACY_COMPANY_LOGO_PREFIX = 'company-logo/';

    /**
     * Copy a new commercial licence from the temporary bucket into permanent storage.
     * The previous value stays in place until this copy succeeds. The old object is
     * removed only after the database save, from afterSave().
     *
     * @return bool
     */
    public function updateLicence()
    {
        return $this->promoteTemporaryDocument('commercial_licence', 'licence');
    }

    /**
     * Copy a new company logo from the temporary bucket into permanent storage.
     *
     * @return bool
     */
    public function updateCompanyLogo()
    {
        return $this->promoteTemporaryDocument('company_logo', 'logo');
    }

    /**
     * Logo removal clears the stored key only. The object is retained so a rolled-back
     * save cannot lose the previous file.
     *
     * @return bool
     */
    public function deleteProfilePhotoFromCloudinary()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getCommercialLicenceUrl(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getCompanyLogoUrl(): ?string
    {
        return self::resolveStoredDocumentUrl($this->company_logo, 'logo');
    }

    /**
     * @param string|null $stored
     * @return string empty|permanent|legacy|temporary|unknown
     */
    public static function classifyCommercialLicence($stored): string
    {
        return self::classifyStoredDocument($stored, 'licence');
    }

    /**
     * @param string|null $stored
     * @return string
     */
    public static function classifyCompanyLogo($stored): string
    {
        return self::classifyStoredDocument($stored, 'logo');
    }

    /**
     * Reject a temporary licence that is public, missing, or not a private upload issued by this server.
     *
     * @param string $attribute
     * @return void
     */
    public function validatePrivateTemporaryLicence($attribute)
    {
        if (!self::shouldValidateTemporaryUpload(
            $this->$attribute,
            $this->getOldAttribute($attribute),
            'licence'
        )) {
            return;
        }

        $stored = self::normalizeStoredDocument($this->$attribute);
        if (self::classifyStoredDocument($stored, 'licence') !== 'temporary') {
            return;
        }

        if (!$this->privateTemporaryLicenceExists($stored)) {
            $this->addError($attribute, Yii::t('app', 'Please upload commercial licence'));
        }
    }

    /**
     * A promotable licence is a bare temporary key issued for a private upload.
     * Public temporary names, including objects from cached Staff clients, are rejected.
     *
     * @param mixed $stored
     * @return bool
     */
    public static function isPrivateTemporaryLicenceKey($stored): bool
    {
        $stored = self::normalizeStoredDocument($stored);
        if (self::classifyStoredDocument($stored, 'licence') !== 'temporary') {
            return false;
        }

        return strpos($stored, self::PRIVATE_TEMPORARY_LICENCE_PREFIX) === 0;
    }

    /**
     * Anonymous HEAD success means the temp object is public and must not be stored.
     * A following authenticated HeadObject uses the permanent resourceManager principal
     * (AWS_S3_RAILWAY_ACCESS_KEY_ID / AWS_S3_RAILWAY_SECRET_ACCESS_KEY, bucket studenthub-uploads)
     * against the temporary bucket. That principal needs s3:HeadObject and s3:GetObject
     * on studenthub-public-anyone-can-upload-24hr-expiry, and s3:PutObject plus s3:GetObject
     * on studenthub-uploads. The temp upload signer only signs the browser PUT.
     * The anonymous temporaryBucketResourceManager stays in place for public logos.
     *
     * @param mixed $stored
     * @return bool
     */
    public function privateTemporaryLicenceExists($stored): bool
    {
        $stored = self::normalizeStoredDocument($stored);
        if (!self::isPrivateTemporaryLicenceKey($stored)) {
            return false;
        }

        $temporary = Yii::$app->temporaryBucketResourceManager;
        if ($temporary->anonymouslyReadable($stored)) {
            return false;
        }

        return Yii::$app->resourceManager->objectExistsInBucket($temporary->bucket, $stored);
    }

    /**
     * @param mixed $submitted
     * @param mixed $existing
     * @param string $kind licence|logo
     * @return bool
     */
    public static function shouldValidateTemporaryUpload($submitted, $existing, $kind): bool
    {
        $submitted = self::normalizeStoredDocument($submitted);
        $existing = self::normalizeStoredDocument($existing);

        if ($submitted === '' || $submitted === $existing) {
            return false;
        }

        $class = self::classifyStoredDocument($submitted, $kind);

        return $class === 'temporary' || $class === 'unknown';
    }

    /**
     * @param self $model
     * @param array $params
     * @param string $bodyKey
     * @param string $attribute
     * @return void
     */
    public static function assignPostedDocumentValue($model, array $params, $bodyKey, $attribute)
    {
        if (array_key_exists($bodyKey, $params)) {
            $model->$attribute = $params[$bodyKey];
        }
    }

    /**
     * Staff and Admin responses include the stored licence key. They do not include
     * a retrieval URL. Employer, Candidate, and every other application omit the key.
     * The authenticated reader checks the signed-in identity again.
     *
     * @return bool
     */
    public static function exposesCommercialLicence(): bool
    {
        return self::identityMayReadCompanyLicence();
    }

    /**
     * @return bool
     */
    public static function identityMayChangeCompanyDocuments(): bool
    {
        return self::currentIdentity() instanceof \common\models\Staff;
    }

    /**
     * @return bool
     */
    public static function identityMayReadCompanyLicence(): bool
    {
        $identity = self::currentIdentity();

        return $identity instanceof \common\models\Staff || $identity instanceof \common\models\Admin;
    }

    /**
     * @return \yii\web\IdentityInterface|null
     */
    private static function currentIdentity()
    {
        if (!Yii::$app->has('user', true) || Yii::$app->user->getIsGuest()) {
            return null;
        }

        return Yii::$app->user->getIdentity();
    }

    /**
     * A replacement must be a new temporary upload. Another company's permanent key,
     * a legacy reference, or any other path cannot be saved in place of an upload.
     *
     * @param string $attribute
     * @param array $params
     * @return void
     */
    public function validateDocumentReplacement($attribute, $params)
    {
        $submitted = self::normalizeStoredDocument($this->$attribute);
        $existing = self::normalizeStoredDocument($this->getOldAttribute($attribute));

        if ($submitted === '' || $submitted === $existing) {
            return;
        }

        $class = self::classifyStoredDocument($submitted, $params['kind']);

        if ($class === 'temporary') {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'Please upload the file again.'));
    }

    /**
     * @param string $kind
     * @return string
     */
    public static function permanentDocumentPrefix($kind): string
    {
        $prefix = $kind === 'logo' ? self::COMPANY_LOGO_S3_PREFIX : self::PRIVATE_COMMERCIAL_LICENCE_S3_PREFIX;

        return (YII_ENV == 'prod' ? '' : 'dev/') . $prefix;
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

        if (!$this->guardDocumentMutation()) {
            return false;
        }

        // in case update

        if(
            !$insert && (
                $this->company_last_followup_datetime != ($this->oldAttributes['company_last_followup_datetime'] ?? null) ||
                $this->company_followup_interval_weeks != ($this->oldAttributes['company_followup_interval_weeks'] ?? null)
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
            $this->company_logo = $this->getOldAttribute('company_logo');
            return false;
        }

        // in case create

        if ($this->isNewRecord && $this->commercial_licence && !$this->updateLicence()) {
            $this->company_logo = $this->getOldAttribute('company_logo');
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

    /**
     * @param string $attribute
     * @param string $kind
     * @return bool
     */
    /**
     * A non-staff caller may resend the current key. A different key, including
     * an empty value that would remove the file, is rejected and restored.
     *
     * @return bool
     */
    protected function guardDocumentMutation(): bool
    {
        if (self::identityMayChangeCompanyDocuments()) {
            return true;
        }

        $allowed = true;
        foreach (['company_logo', 'commercial_licence'] as $attribute) {
            $submitted = self::normalizeStoredDocument($this->$attribute);
            $existing = self::normalizeStoredDocument($this->getOldAttribute($attribute));
            if ($submitted === $existing) {
                continue;
            }
            $this->$attribute = $existing === '' ? null : $this->getOldAttribute($attribute);
            $this->addError($attribute, Yii::t('app', 'You cannot change this file.'));
            $allowed = false;
        }

        return $allowed;
    }

    protected function promoteTemporaryDocument($attribute, $kind)
    {
        $submitted = self::normalizeStoredDocument($this->$attribute);
        $existing = self::normalizeStoredDocument($this->oldAttributes[$attribute] ?? '');

        if ($submitted === '' || $submitted === $existing) {
            $this->$attribute = $existing === '' ? null : $existing;

            return true;
        }

        $class = self::classifyStoredDocument($submitted, $kind);

        if ($class === 'permanent' || $class === 'public-copy' || $class === 'legacy') {
            $this->$attribute = $existing === '' ? $submitted : $existing;
            if ($submitted !== $existing) {
                $this->addError($attribute, Yii::t('app', 'Please upload the file again.'));

                return false;
            }

            return true;
        }

        if ($class !== 'temporary') {
            $this->$attribute = $existing === '' ? null : $existing;
            $this->addError($attribute, Yii::t('app', 'Please upload the file again.'));

            return false;
        }

        $temporary = Yii::$app->temporaryBucketResourceManager;
        $exists = $kind === 'licence'
            ? $this->privateTemporaryLicenceExists($submitted)
            : $temporary->fileExists($submitted);
        if (!$exists) {
            $this->$attribute = $existing === '' ? null : $existing;
            $this->addError($attribute, Yii::t('app', 'The file could not be saved. Please upload it again.'));

            return false;
        }

        $target = self::permanentDocumentPrefix($kind) . basename($submitted);

        if ($target === self::permanentDocumentPrefix($kind)) {
            $this->$attribute = $existing === '' ? null : $existing;
            $this->addError($attribute, Yii::t('app', 'The file could not be saved. Please upload it again.'));

            return false;
        }

        try {
            $copyOptions = $kind === 'licence' ? ['ACL' => 'private'] : [];
            Yii::$app->resourceManager->copy(
                $submitted,
                $target,
                Yii::$app->temporaryBucketResourceManager->bucket,
                $copyOptions
            );
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'company');
            $this->$attribute = $existing === '' ? null : $existing;
            $this->addError($attribute, Yii::t('app', 'The file could not be saved. Please upload it again.'));

            return false;
        }

        $this->$attribute = $target;

        return true;
    }

    /**
     * @param mixed $previous
     * @param mixed $current
     * @param string $kind
     * @return void
     */
    protected function deleteReplacedStoredObject($previous, $current, $kind)
    {
        $previous = self::normalizeStoredDocument($previous);
        $current = self::normalizeStoredDocument($current);

        if ($previous === '' || $previous === $current) {
            return;
        }

        if (self::classifyStoredDocument($current, $kind) !== 'permanent') {
            return;
        }

        $this->deleteStoredCompanyObject($previous, $kind);
    }

    /**
     * @param mixed $stored
     * @param string $kind
     * @return void
     */
    public function deleteStoredCompanyObject($stored, $kind)
    {
        $stored = self::normalizeStoredDocument($stored);

        if ($stored === '') {
            return;
        }

        if (self::classifyStoredDocument($stored, $kind) === 'permanent') {
            try {
                Yii::$app->resourceManager->delete($stored);
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), 'company');
            }

            return;
        }

        if (self::classifyStoredDocument($stored, $kind) !== 'legacy') {
            return;
        }

        try {
            $path = (YII_ENV == 'prod') ? '' : 'dev/';
            Yii::$app->cloudinaryManager->delete($path . $stored);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), 'company');
        }
    }

    /**
     * @param mixed $stored
     * @param string $kind
     * @return string|null
     */
    public static function resolveStoredDocumentUrl($stored, $kind): ?string
    {
        $stored = self::normalizeStoredDocument($stored);

        if ($stored === '' || $kind === 'licence') {
            return null;
        }

        if (self::classifyStoredDocument($stored, $kind) === 'permanent') {
            return Yii::$app->resourceManager->getUrl($stored);
        }

        if (self::classifyStoredDocument($stored, $kind) !== 'legacy') {
            return null;
        }

        $cloudName = Yii::$app->cloudinaryManager->cloud_name ?? null;
        if ($cloudName === null || $cloudName === '') {
            return null;
        }

        $publicId = ((YII_ENV == 'prod') ? '' : 'dev/') . $stored;

        return 'https://res.cloudinary.com/'
            . rawurlencode($cloudName)
            . '/image/upload/'
            . str_replace('%2F', '/', rawurlencode($publicId));
    }

    /**
     * @param mixed $stored
     * @param string $kind
     * @return string
     */
    public static function classifyStoredDocument($stored, $kind): string
    {
        $stored = self::normalizeStoredDocument($stored);

        if ($stored === '') {
            return 'empty';
        }

        if (preg_match('#^https?://#i', $stored)) {
            return 'unknown';
        }

        $base = $kind === 'logo' ? self::COMPANY_LOGO_S3_PREFIX : self::PRIVATE_COMMERCIAL_LICENCE_S3_PREFIX;
        $legacy = $kind === 'logo' ? self::LEGACY_COMPANY_LOGO_PREFIX : self::LEGACY_COMMERCIAL_LICENCE_PREFIX;
        $legacyDev = 'dev/' . $legacy;

        if (strpos($stored, $base) === 0 || strpos($stored, 'dev/' . $base) === 0) {
            return 'permanent';
        }

        if ($kind === 'licence') {
            $public = self::COMMERCIAL_LICENCE_S3_PREFIX;
            if (strpos($stored, $public) === 0 || strpos($stored, 'dev/' . $public) === 0) {
                return 'public-copy';
            }
        }

        if (strpos($stored, $legacy) === 0 || strpos($stored, $legacyDev) === 0) {
            return 'legacy';
        }

        if (strpos($stored, '/') !== false) {
            return 'unknown';
        }

        return 'temporary';
    }

    /**
     * @param mixed $stored
     * @return string
     */
    protected static function normalizeStoredDocument($stored): string
    {
        if ($stored === null) {
            return '';
        }

        return ltrim(trim((string) $stored), '/');
    }
}
