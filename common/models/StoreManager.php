<?php

namespace common\models;

use Detection\MobileDetect;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "store_manager".
 *
 * @property string $store_manager_uuid
 * @property int $company_id
 * @property int $store_id
 * @property string $name
 * @property string $email
 * @property string $new_email
 * @property bool $email_verification
 * @property string $phone_number
 * @property string $password_hash
 * @property string $limit_email
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 * @property Store $store
 */
class StoreManager extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    //Email verification values for `contact_email_verification`
    const EMAIL_VERIFIED = 1;
    const EMAIL_NOT_VERIFIED = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'store_manager';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            //'store_manager_uuid', 'created_at', 'updated_at'
            [['company_id', 'store_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['store_manager_uuid'], 'string', 'max' => 60],
            [['name', 'email', 'new_email', 'phone_number'], 'string', 'max' => 100],
            [['password_hash', 'auth_key', 'password_reset_token'], 'string', 'max' => 255],
            [['store_manager_uuid'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'store_manager_uuid',
                ],
                'value' => function() {
                    if (!$this->store_manager_uuid)
                        $this->store_manager_uuid = 'store_manager_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->store_manager_uuid;
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
            'store_manager_uuid' => Yii::t('app', 'Store Manager Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'name' => Yii::t('app', 'Name'),
            'email' => Yii::t('app', 'Email'),
            'new_email' => Yii::t('app', 'New Email'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'limit_email' => Yii::t('app', 'Limit Email'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['store_manager_uuid' => $id]);
    }

    /**
     * Create an Access Token Record for this Company
     * if the company already has one, it will return it instead
     * @return \common\models\ManagerToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = ManagerToken::find()
            ->andWhere([
                'store_manager_uuid' => $this->store_manager_uuid,
                'token_status' => ManagerToken::STATUS_ACTIVE
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->one();

        if($token) {
            return $token;
        }

        $detect = new MobileDetect();

        $device = "Desktop Device";

        if ($detect->isMobile()) {
            $device = "Mobile Device";
        } elseif ($detect->isTablet()) {
            $device = "Tablet Device";
        }

        // Create new inactive token
        $token = new ManagerToken();
        $token->store_manager_uuid = $this->store_manager_uuid;
        $token->token_value = ManagerToken::generateUniqueTokenString();
        $token->token_status = ManagerToken::STATUS_ACTIVE;
        $token->token_device = $device;
        $token->token_device_id = $detect->getUserAgent();
        $token->token_expiry_datetime = date('Y-m-d H:i:s', strtotime("+1 month"));
        $token->ip_address = isset(Yii::$app->params['user_ip_address']) ?? Yii::$app->request->getRemoteIP();
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {

        $token = ManagerToken::find()
            ->andWhere([
                'token_value' => $token,
                'token_status' => ManagerToken::STATUS_ACTIVE
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('manager')
            ->one();

        if (!$token)
            return false;

        //update last used datetime

        $token->token_last_used_datetime = new Expression('NOW()');
        $token->save();

        //should not able to login, if email not verified but have valid token

        if ($token->manager && $token->manager->email_verification) {
            return $token->manager;
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
        return static::findOne(['email' => $email]);
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
            'password_reset_token' => $token
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
        return $this->auth_key;
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
        if (!$this->password_hash) {
            return null;
        }

        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey() {
        $this->auth_key = strtoupper($this->generateUniqueRandomString('auth_key', 4));
        //Yii::$app->security->generateRandomString();
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

        return $this->auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    /**
     * Verifies the candidate email
     */
    public static function verifyEmail($email, $code) {

        $candidate = StoreManager::find()
            ->andWhere([
                'OR',
                ['new_email' => $email],
                ['email' => $email]
            ])
            //->andWhere(['candidate.deleted' => 0])
            ->one();

        if(!$candidate) {
            return [
                'success' => false,
                'message' =>Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
            ];
        }

        $candidate->setScenario('verifyEmail');

        if ($candidate->auth_key && $code && $candidate->auth_key == $code) { //to cope with sql case insensitivity
            //If not verified
            if ($candidate->email_verification == StoreManager::EMAIL_NOT_VERIFIED) {
                //Verify this candidates email
                $candidate->email_verification = StoreManager::EMAIL_VERIFIED;
            }

            // new email address

            if (!empty($candidate->new_email)) {
                $candidate->email = $candidate->new_email;
                $candidate->new_email = null;
            }

            $candidate->auth_key = ''; //remove auth key

            $candidate->save(false);

            return [
                'success' => true,
                'data' => $candidate
            ];
        } else {
            return [
                'success' => false,
                'message' =>Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByUnVerifiedTokenToken($token, $type = null) {
        $token = ManagerToken::find()
            ->andWhere(['token_value' => $token])
            ->with('manager')
            ->one();

        if ($token && $token->manager ) {//&& !$token->manager->deleted
            return $token->manager;
        }
    }

    /**
     * Send link in email to reset password
     * @return bool
     */
    public function sendPasswordResetEmail()
    {
        $this->generatePasswordResetToken();
        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['managerAppUrl'] . 'update-password/' . $this->password_reset_token;

        $ml = new MailLog();
        $ml->to = $this->email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Reset your StudentHub password";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("manager/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->email,
                "name" => $this->name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->email)
            ->setSubject('Reset your StudentHub password');

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
     * Sends an email requesting a user to verify his email address
     * @return boolean whether the email was sent
     */
    public function sendVerificationEmail() {

        $this->generateAuthKey();

        //Update contact last email limit timestamp
        $this->limit_email = new Expression('NOW()');
        $this->save(false);

        if ($this->new_email) {
            $email = $this->new_email;
        } else {
            $email = $this->email;
        }

        $ml = new MailLog();
        $ml->to = $email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Please confirm your email address";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'manager/verify-email-html',
            'text' => 'manager/verify-email-text',
        ], [
            'manager' => $this
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
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {

        $scenarios = parent::scenarios();

        $scenarios['updateEmail'] = ['email', 'new_email'];

        $scenarios['verifyEmail'] = ['email_verification', 'email', 'new_email', 'auth_key'];

        return $scenarios;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }
}
