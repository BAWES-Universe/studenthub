<?php

namespace common\models;

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
 */
class Contact extends \yii\db\ActiveRecord
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
            [['contact_password_reset_token'], 'unique'],
            [['contact_status', 'contact_email_verified_by'], 'number'],
            [['contact_email_verified_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['contact_email_verified_by' => 'staff_id']],
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
                'class' => AttributeBehavior::className(),
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
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'contact_created_at',
                'updatedAtAttribute' => 'contact_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {

        $scenarios = parent::scenarios();

        $scenarios['signup'] = ['contact_status', 'contact_name', 'contact_email', 'contact_password_hash', 'contact_receive_email', 'contact_receive_suggestions','contact_otp'];

        $scenarios['signupAuth0'] = ['contact_status', 'contact_name', 'contact_email', 'contact_password_hash', 'contact_receive_email', 'contact_email_verification', 'contact_receive_suggestions','contact_otp'];

        $scenarios['updateEmail'] = ['contact_email', 'contact_new_email'];

        $scenarios['verifyEmail'] = ['contact_email_verification', 'contact_email', 'contact_new_email', 'contact_auth_key'];

        $scenarios['updateStatus'] = ['contact_status'];

        return $scenarios;
    }

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
                $IParray = array_values(array_filter(explode(',', $forwardedFor)));

                // Get the first ip from forwarded array to get original requester
                $ip = $IParray[0];
            }

            $this->ip_address = $ip;

            if ($insert) {

                $count = self::find()
                    ->andWhere(['ip_address' => $this->ip_address])
                    ->andWhere("DATE(contact_created_at) = DATE('".date('Y-m-d')."')")
                    ->count();

                if ($count > 1) {
                    Yii::error("too may contact signup from same ip");
                    return $this->addError('ip_address', "Too many requests");
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
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {

        $token = ContactToken::find()->andWhere([
                'token_value' => $token,
                'token_status' => ContactToken::STATUS_ACTIVE
            ])
            ->with('contact')
            ->one();

        if (!$token)
            return false;

        //update last used datetime

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
    public function generateAuthKey() {
        $this->contact_auth_key = strtoupper($this->generateUniqueRandomString('contact_auth_key', 4));
        //Yii::$app->security->generateRandomString();
    }

    /**
     * Generate unique string for a given attribute of given length
     * @param type $attribute
     * @param type $length
     * @return type
     */
    public function generateUniqueRandomString($attribute, $length = 32) {
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
     * Create an Access Token Record for this Company
     * if the company already has one, it will return it instead
     * @return \common\models\ContactToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = ContactToken::findOne([
            'contact_uuid' => $this->contact_uuid,
            'token_status' => ContactToken::STATUS_ACTIVE
        ]);

        if($token) {
            return $token;
        }

        // Create new inactive token
        $token = new ContactToken();
        $token->contact_uuid = $this->contact_uuid;
        $token->token_value = ContactToken::generateUniqueTokenString();
        $token->token_status = ContactToken::STATUS_ACTIVE;
        $token->save(false);

        return $token;
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

    public function getCompanyContact($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
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
        $ml->save();

        $mailer = Yii::$app->mailer->compose([
            'html' => 'company/verify-email-html',
            'text' => 'company/verify-email-text',
        ], [
            'contact' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($email)
            ->setSubject('Please confirm your email address');

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "password-reset-token");
        }
    }
}
