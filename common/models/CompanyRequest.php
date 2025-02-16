<?php

namespace common\models;

use common\models\Company;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "company_request".
 *
 * @property string $company_request_uuid
 * @property string $utm_uuid
 * @property string $company_name
 * @property string $company_email
 * @property string $contact_position
 * @property string $contact_name 
 * @property string $contact_password_hash 
 * @property string $contact_receive_email 
 * @property string $phone_number
 * @property string $requesting_for
 * @property int $status pending=0, processing=1,  accepted=2, rejected=3
 * @property int $country_id
 * @property string $currency_code
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Contact $contactUu
 */
class CompanyRequest extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_name', 'company_email', 'contact_name', 'contact_password_hash', 'phone_number', 'currency_code', "country_id"], 'required'],
            [['status'], 'integer'],
            [['company_email'], 'validateEmail'],
            [['created_at', 'updated_at'], 'safe'],
            [['company_request_uuid'], 'string', 'max' => 60],
            [['company_name', 'contact_position'], 'string', 'max' => 100],
            [['company_email', 'requesting_for'], 'string', 'max' => 255],
            [['utm_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Campaign::class, 'targetAttribute' => ['utm_uuid' => 'utm_uuid']],
        ];
    }

    /**
     * Validate email in new_email field
     */
    public function validateEmail($attribute) {

        $query = Contact::find()
            ->andWhere([
                'or',
                ['contact_new_email' => $this->$attribute],
                ['contact_email' => $this->$attribute]
            ]);

        if ($query->exists()) {
            $this->addError('company_email', Yii::t('app', 'Email already registered'));
        }
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'company_request_uuid',
                ],
                'value' => function() {
                    if(!$this->company_request_uuid)
                        $this->company_request_uuid = 'company_request_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->company_request_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'company_request_uuid' => Yii::t('app', 'Company Request Uuid'),
            'company_name' => Yii::t('app', 'Company Name'),
            'company_email' => Yii::t('app', 'Company Email'),
            'contact_position' => Yii::t('app', 'Contact Position'),
            'status' => Yii::t('app', 'Status'),
            'requesting_for' => Yii::t('app', 'Requesting for'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            "currency_code" => Yii::t('app', "currency_code"),
            "country_id" => Yii::t('app', "country_id"),
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if($insert)
            $this->notifyStaff();

    }

    /**
     * notify staff for new account request
     * @return bool
     */
    private function notifyStaff() {

        $ml = new MailLog();
        $ml->to = "sales@bawes.net";
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "New company account request";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'staff/company-account-request-html',
            'text' => 'staff/company-account-request-text',
        ], [
            'model' => $this,
            "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo("sales@bawes.net")
            ->setSubject('New company account request');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * notify company for new account approval
     * @return bool
     */
    private function notifyApprove($contact, $company) {

        $ml = new MailLog();
        $ml->to = $this->company_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Congratulation! Your account request approved!";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'company/account-approved-html',
            'text' => 'company/account-approved-text',
        ], [
            'model' => $this,
            "contact" => $contact,
            "company" => $company,
            "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($this->company_email)
            ->setSubject('Congratulation! Your account request approved!');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * notify company for new account rejection
     * @return bool
     */
    private function notifyReject() {

        $ml = new MailLog();
        $ml->to = $this->company_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "New company account request not approved!";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'company/account-rejected-html',
            'text' => 'company/account-rejected-text',
        ], [
            'model' => $this,
            "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($this->company_email)
            ->setSubject('New company account request not approved!');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * approve company registration request
     * @return array|string[]
     * @throws \yii\db\Exception
     */
    public function approve() {

        $transaction = Yii::$app->db->beginTransaction ();

        $this->status = self::STATUS_ACCEPTED;

        if(!$this->save()) {
            return [
                "operation" => "error",
                "code" => 6,
                "message" => $this->errors
            ];
        }

        $model = new Contact();
        $model->contact_name = $this->contact_name;
        $model->contact_email = $this->company_email;
        $model->contact_password_hash = $this->contact_password_hash;
        $model->contact_receive_email = $this->contact_receive_email;
        $model->utm_uuid = $this->utm_uuid;
        //$model->contact_email_verification = true;
        $model->generateAuthKey();

        if (!$model->save()) {

            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 2,
                "message" => $model->errors
            ];
        }

        $company = new Company();
        $company->setScenario(Company::SCENARIO_APPROVE);
        $company->company_name = $this->company_name;
        $company->company_common_name_en = $this->company_name;
        $company->company_common_name_ar = $this->company_name;
        $company->company_email = $this->company_email;
        $company->company_bonus_commission = 0;
        $company->company_approved_to_hire = true;
        $company->company_followup = true;
        $company->company_followup_interval_weeks = 1;
        $company->company_last_followup_datetime = date('Y-m-d', strtotime ('-7 days'));
        //$company->company_status_override = Company::STATUS_ACTIVE;
        $company->currency_code = $this->currency_code;
        $company->country_id = $this->country_id;

        if (!$company->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 3,
                "message" => $company->errors
            ];
        }

        $companyContact = new CompanyContact();
        $companyContact->company_id = $company->company_id;
        $companyContact->contact_uuid = $model->contact_uuid;
        $companyContact->contact_position = $this->contact_position;
        $companyContact->allow_access = true;

        if (!$companyContact->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 4,
                "message" => $companyContact->errors
            ];
        }

        if($this->phone_number) 
        {
            $contactPhone = new ContactPhone;
            $contactPhone->contact_uuid = $model->contact_uuid;
            $contactPhone->phone_number = $this->phone_number;

            if (!$contactPhone->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "code" => 5,
                    "message" => $contactPhone->errors
                ];
            }

        }

        $transaction->commit();

        if(YII_ENV == 'prod')
        {
            Yii::$app->eventManager->track('Company Profile Created',
                [
                    'contact_uuid' => $model->contact_uuid,
                    'contact_name' => $this->contact_name,
                    'contact_email' => $this->company_email,
                    'company_id' => $company->company_id,
                    'company_name' => $company->company_name,
                    'company_email' => $company->company_email,
                    'phone_number' => $this->phone_number
                ]);
        }

        $this->notifyApprove($model, $company);

        return [
            "operation" => "success",
            "message" => "Company account created successfully"
        ];
    }

    /**
     * reject company registration request
     * @return array|string[]
     */
    public function reject() {

        $this->status = self::STATUS_REJECTED;

        if(!$this->save()) {
            return [
                "operation" => "error",
                "message" => $this->errors
            ];
        }

        $this->notifyReject();

        return [
            "operation" => "success",
            "message" => "Company registration request rejected"
        ];
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            "country",
            "currency"
        ]);
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
     * @return \yii\db\ActiveQuery
     */
    public function getCampaign($modelClass = "\common\models\Campaign")
    {
        return $this->hasOne($modelClass::className(), ['utm_uuid' => 'utm_uuid']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }
}
