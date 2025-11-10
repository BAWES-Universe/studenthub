<?php

namespace common\models;

use common\helpers\DeviceDetector;
use staff\models\Staff;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * This is the model class for table "company_contact".
 *
 * @property string $contact_uuid
 * @property string $utm_uuid
 * @property string $contact_name
 * @property string $contact_email
 * @property string $contact_new_email
 * @property string $contact_email_verification
 * @property integer $contact_email_verified_by
 * @property string $contact_limit_email
 * @property string $contact_password_hash
 * @property string $contact_auth_key
 * @property string $contact_otp
 * @property string $contact_receive_email
 * @property string $contact_receive_suggestions
 * @property string $contact_receive_notification
 * @property string $contact_status
 * @property boolean $deleted
 * @property string $contact_created_at
 * @property string $contact_updated_at
 *
 * @property Company $company
 * @property CompanyContactEmail[] $companyContactEmails
 * @property CompanyContactPhone[] $companyContactPhones
 * @property Campaign $campaign
 */
class Contact extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    //Email verification values for `contact_email_verification`
    const EMAIL_VERIFIED = 1;
    const EMAIL_NOT_VERIFIED = 0;

    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contact_name', 'contact_email'], 'required'],//'contact_password_hash'
            [['contact_created_datetime', 'contact_updated_datetime'], 'safe'],
            [['contact_uuid'], 'string', 'max' => 60],//'contact_otp'
            [['contact_email', 'contact_new_email'], 'email'],
            [['contact_email', 'contact_new_email'], 'validateEmail'],
            [['contact_new_email'], 'validateNewEmail'],
            [['contact_name', 'contact_password_reset_token',], 'string', 'max' => 255],
            [['contact_uuid'], 'unique'],//'contact_email'
            [['enable_two_step_auth'], 'safe'],
            [['contact_password_reset_token'], 'unique'],
            [['contact_status', 'contact_email_verified_by'], 'number'],
            [['utm_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Campaign::class, 'targetAttribute' => ['utm_uuid' => 'utm_uuid']],
            [['contact_email_verified_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['contact_email_verified_by' => 'staff_id']],
        ];
    }

    /**
     * new email can not be same as old
     * @param $attribute
     */
    public function validateNewEmail($attribute) {

        if ($this->contact_new_email == $this->contact_email) {
            $this->addError('contact_new_email', Yii::t('app', 'Email already registered'));
        }
    }

    /**
     * Validate email in new_email field
     */
    public function validateEmail($attribute) {

        $query = self::find()
            ->andWhere([
                'or',
                ['contact_new_email' => $this->$attribute],
                ['contact_email' => $this->$attribute]
            ]);

        if($this->contact_uuid) {
            $query->andWhere(['!=', 'contact_uuid', $this->contact_uuid]);
        }

        if ($query->exists()) {
            $this->addError('contact_email', Yii::t('app', 'Email already registered'));
        }
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'contact_uuid',
                ],
                'value' => function() {
                    if(!$this->contact_uuid)
                        $this->contact_uuid = 'contact_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->contact_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'contact_created_at',
                'updatedAtAttribute' => 'contact_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if($insert && $this->campaign) {
            $this->campaign->no_of_signups++;
            $this->campaign->save(false);
        }

        /*if (isset($changedAttributes['deleted']) && $changedAttributes['deleted'] == 1) {
            CompanyContact::deleteAll(['contact_uuid' => $this->contact_uuid]);
        }*/

        return true;
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {

        $scenarios = parent::scenarios();

        $scenarios['signup'] = ['utm_uuid', 'contact_status', 'contact_name', 'contact_email', 'contact_password_hash', 'contact_receive_email', 'contact_receive_suggestions','contact_otp'];

        $scenarios['signupAuth0'] = ['utm_uuid', 'contact_status', 'contact_name', 'contact_email', 'contact_password_hash', 'contact_receive_email', 'contact_email_verification', 'contact_receive_suggestions','contact_otp'];

        $scenarios['updateEmail'] = ['contact_email', 'contact_new_email'];

        $scenarios['verifyEmail'] = ['contact_email_verification', 'contact_email', 'contact_new_email', 'contact_auth_key'];

        $scenarios['updateStatus'] = ['contact_status'];

        return $scenarios;
    }

    /**
     * @param $insert
     * @return bool|void
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        if(Yii::$app->request instanceof \yii\web\Request) {

            // Get initial IP address of requester
            $ip = Yii::$app->request->getRemoteIP();

            // Check if request is forwarded via load balancer or cloudfront on behalf of user
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

                // as "X-Forwarded-For" is usually a list of IP addresses that have routed
                if ($forwardedFor) {
                    $IParray = array_values(array_filter(explode(',', $forwardedFor)));

                    // Get the first ip from forwarded array to get original requester
                    if ($IParray) {
                        $ip = $IParray[0];
                    }
                }
            }

            $this->ip_address = $ip;

            if ($insert) {

                $count = self::find()
                    ->andWhere(['ip_address' => $this->ip_address])
                    ->andWhere("DATE(contact_created_at) = DATE('".date('Y-m-d')."')")
                    ->count();

                if ($count > 10) {
                    Yii::error("too may contact signup from same ip");
                    //return $this->addError('ip_address', "Too many requests");
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        //remove unique fields, so can create new account with same details

        $this->contact_password_reset_token = null;

        ContactEmail::deleteAll(['contact_uuid' => $this->contact_uuid]);
        ContactPhone::deleteAll(['contact_uuid' => $this->contact_uuid]);
        CompanyContact::deleteAll(['contact_uuid' => $this->contact_uuid]);
        ContactToken::deleteAll(['contact_uuid' => $this->contact_uuid]);

        return parent::beforeDelete ();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'contact_uuid' => Yii::t('app', 'Contact ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'contact_name' => Yii::t('app', 'Contact Name'),
            'contact_email' => Yii::t('app', 'Contact Email'),
            'contact_new_email' => Yii::t('app', 'Contact New Email'),
            'contact_email_verification' => Yii::t('app', 'Contact Email Verified?'),
            'contact_email_verified_by' => Yii::t('app', 'Contact Email Verified By'),
            'contact_limit_email' => Yii::t('app', 'Contact Limit Email'),
            'contact_otp' => Yii::t('app', 'One Time Password'),
            'contact_receive_email' => Yii::t('app','Receive Email?'),
            'contact_receive_notification' => Yii::t('app','Receive Notification?'),
            'contact_receive_suggestions' => Yii::t('app','Receive suggestions?'),
            'contact_auth_key' => Yii::t('app','Auth Key'),
            'contact_password_hash' => Yii::t('app','Password'),
            'contact_password_reset_token' => Yii::t('app','Password Reset Token'),
            'contact_created_datetime' => Yii::t('app', 'Contact Created Datetime'),
            'contact_updated_datetime' => Yii::t('app', 'Contact Updated Datetime'),
            'contact_status' => Yii::t('app', 'Contact Status')
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted'],
            //$fields['contact_email_verification'],
            $fields['contact_limit_email'],
            $fields['contact_new_email'],
            $fields['contact_password_hash'],
            $fields['contact_password_reset_token'],
            $fields['contact_auth_key'],
            $fields['contact_otp']);

        $fields['contact_email_verification'] = function($model) {
            return (boolean) $model->contact_email_verification;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'campaign',
            'companies',
            'requests',
            'contactEmails',
            'contactPhones',
            'notes',
            'contactStats',
            'companyContacts'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactsHavingAccess($modelClass = "\common\models\CompanyContact")
    {
        return $this->getCompanyContacts()
            ->andWhere(['allow_access' => true]);
    }

    /**
     * list all parents companies where this contact is owner or HR
     * @return \yii\db\ActiveQuery
     */
    public function getManagedCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->via('companyContactsHavingAccess');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->via('companyContacts');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\common\models\ContactEmail")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\common\models\ContactPhone")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\common\models\Request")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid'])
            ->orderBy('note_updated_datetime DESC');
    }

    /**
     * @return array
     */
    public function getContactStats() {
        return [
            'contactEmails' => $this->getContactEmails()->count(),
            'contactPhones' => $this->getContactPhones()->count(),
            'requests' => $this->getRequests()->count(),
            'notes' => $this->getNotes()->count(),
            'lastNotes' => $this->getNotes()->orderBy('note_updated_datetime DESC')->one(),
        ];
    }

    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['contact_id' => $id]);
    }

    /**
     * Create an Access Token Record for this Company
     * if the company already has one, it will return it instead
     * @return \common\models\ContactToken
     */
    public function getAccessToken($type = ContactToken::STATUS_ACTIVE){
        // Return existing inactive token if found
        /*$token = ContactToken::find()
            ->andWhere([
                'contact_uuid' => $this->contact_uuid,
                'token_status' => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->one();

        if($token) {
            return $token;
        }*/

        $detect = new DeviceDetector();

        $device = "Desktop Device";

        if ($detect->isMobile()) {
            $device = "Mobile Device";
        } elseif ($detect->isTablet()) {
            $device = "Tablet Device";
        }

        // Create new inactive token
        $token = new ContactToken();
        $token->contact_uuid = $this->contact_uuid;
        $token->token_value = ContactToken::generateUniqueTokenString();
        $token->token_status = $type;
        $token->token_device = $device;
        $token->token_device_id = mb_strimwidth( $detect->getUserAgent(), 0, 250, "...");
        $token->token_expiry_datetime = date('Y-m-d H:i:s', strtotime("+1 month"));
        $token->ip_address = isset(Yii::$app->params['user_ip_address']) ?
            Yii::$app->params['user_ip_address']: Yii::$app->request->getRemoteIP();
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        //if 2 step auth enable, send OTP
        if ($type == AdminToken::STATUS_INACTIVE) {
            $this->sendOTPMail($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $authType = HttpBearerAuth::class, $type = ContactToken::STATUS_ACTIVE, $otp = null) {

        $token = \company\models\ContactToken::find()
            ->andWhere([
                'token_value' => $token,
                'token_status' => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('contact')
            ->one();

        if (!$token)
            return false;

        if ($otp && $otp != $token->otp) {
            $token->total_attempt = $token->total_attempt + 1;

            if ($token->total_attempt > 3) {
                $token->delete();
                return false;
            }

            if (!$token->save()) {
                Yii::error($token->errors);
            }

            return false;
        }
        //update last used datetime

        $token->token_status = ContactToken::STATUS_ACTIVE;//make inactive token to active on found with OTP
        $token->token_last_used_datetime = new Expression('NOW()');
        $token->save();

        //should not able to login, if email not verified but have valid token

        if ($token->contact && $token->contact->contact_email_verification) {
            return $token->contact;
        }

        //invalid token

        $token->delete();
    }


    /**
     * Send OTP mail to contact
     * @param \common\models\ContactToken $token
     * @return bool
     */
    public function sendOTPMail($token) {

        //generate OTP
        $token->otp = Yii::$app->security->generateRandomString(4);
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        $ml = new MailLog();
        $ml->to = $this->contact_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'OTP for 2 step verification';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("company/contact-otp",
            [
                "model" => $this,
                "otp" => $token->otp,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->contact_email)
            ->setSubject('OTP for 2 step verification');

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
     * Finds company by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['contact_email' => $email]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'contact_password_reset_token' => $token
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->contact_auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        if (!$this->contact_password_hash) {
            return null;
        }

        return Yii::$app->security->validatePassword($password, $this->contact_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->contact_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey($length = 4) {
        $this->contact_auth_key = $length?
            strtoupper($this->generateUniqueRandomString('contact_auth_key', $length)):
            Yii::$app->security->generateRandomString();
    }

    /**
     * Generate unique string for a given attribute of given length
     * @param type $attribute
     * @param type $length
     * @return type
     */
    public function generateUniqueRandomString(string $attribute, $length = 32) {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        $randomString = mt_rand($min, $max);

        if (!$this->findOne([$attribute => $randomString]))
            return $randomString;
        else
            return $this->generateUniqueRandomString($attribute, $length);
    }

    /**
     * Generate, save, and return an auth key for this account [1 time use token]
     * @return string
     */
    public function generateAuthKeyAndSave() {
        $this->generateAuthKey();
        $this->save(false);

        return $this->contact_auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->contact_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->contact_password_reset_token = null;
    }

    /**
     * return otp(one time password)
     */
    public function getOtp() {
        return $this->contact_otp;
    }

    /**
     * Validate this agents otp against supplied OTP if it hasn't already expired.
     * @param  string $otp
     * @return boolean      Whether OTP is valid or not
     */
    public function validateOtp($otp) {
        if (static::isOtpExpired($otp, 5))
            return false;

        return $this->getOtp() === $otp;
    }

    /**
     * Generates otp to unable user to get login by otp
     */
    public function generateOtp() {
        $this->contact_otp = Yii::$app->db->createCommand('SELECT uuid()')->queryScalar()
            . '@' . time();
    }

    /**
     * Function to check supplied OTP if time has passed and it expired
     *
     * @param  string  $otp             The OTP to check for expiry
     * @param  integer $minutesToExpire How many minutes until it expires
     * @return boolean                  Whether the OTP is expired or not
     */
    public static function isOtpExpired($otp, $minutesToExpire = 5) {
        $timeGeneratedAt = isset(explode('@', $otp)[1]) ? explode('@', $otp)[1] : null;
        $expiryTime = $timeGeneratedAt + 60 * $minutesToExpire;

        if (time() > $expiryTime) {
            return true;
        }

        return false;
    }

    /**
     * Return candidate by otp (one time password)
     * @param  string  $otp             The OTP belonging to agent
     * @param  integer $minutesToExpire How many minutes until it expires
     * @return type
     */
    public static function findByOtp($otp, $minutesToExpire = 5) {
        // Check if OTP is still valid before attempting to find agent
        if (static::isOtpExpired($otp, $minutesToExpire))
            return false;

        return self::find()
            ->andWhere(['contact_otp' => $otp])
            ->one();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContact($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCampaign($modelClass = "\common\models\Campaign")
    {
        return $this->hasOne($modelClass::className(), ['utm_uuid' => 'utm_uuid']);
    }

    /**
     * get contact email
     * @return string|null
     */
    public function getEmail() {

        if($this->contactEmails && count($this->contactEmails) > 0) {
            return $this->contactEmails[0]->email_address;
        } else if ($this->contact_email) {
            return $this->contact_email;
        } else {
            return null;
        }
    }

    /**
     * Sends an email requesting a user to verify his email address
     * @return boolean whether the email was sent
     */
    public function sendVerificationEmail() {

        $this->generateAuthKey();

        //Update contact last email limit timestamp
        $this->contact_limit_email = new Expression('NOW()');
        $this->save(false);

        if ($this->contact_new_email) {
            $email = $this->contact_new_email;
        } else {
            $email = $this->contact_email;
        }

        $ml = new MailLog();
        $ml->to = $email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Please confirm your email address";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'company/verify-email-html',
            'text' => 'company/verify-email-text',
        ], [
            'contact' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($email)
            ->setSubject('Please confirm your email address');

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
     * @inheritdoc
     * @return query\ContactQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\ContactQuery(get_called_class());
    }

}
