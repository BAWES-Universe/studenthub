<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use yii\web\IdentityInterface;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use common\helpers\DeviceDetector;

/**
 * Admin model
 *
 * @property integer $admin_id
 * @property string $admin_name
 * @property string $admin_email
 * @property string $admin_auth_key
 * @property string $admin_password_hash write-only password
 * @property string $admin_password_reset_token
 * @property string $admin_status
 * @property string $admin_limited_access
 * @property string $admin_created_at
 * @property string $admin_updated_at
 *
 * @property AdminToken[] $accessTokens
 */
class Admin extends ActiveRecord implements IdentityInterface {

    //Values for `admin_status`
    const STATUS_ACTIVE = 10;
    const ACCESS_LIMITED = 1;
    const ACCESS_FULL = 0;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'admin';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['admin_name' ,'admin_email'], 'required'],
            [['admin_email'], 'unique'],
            [['admin_password_hash'], 'required', 'on'=>'newAccount'],
            [['admin_email'], 'email'],
            [['enable_two_step_auth'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['admin_auth_key'],
            $fields['admin_password_hash'],
            $fields['admin_password_reset_token']);

        return $fields;
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'admin_created_at',
                'updatedAtAttribute' => 'admin_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'admin_id' => Yii::t('app', 'Admin ID'),
            'admin_name' => Yii::t('app', 'Admin Name'),
            'admin_email' => Yii::t('app', 'Admin Email'),
            'admin_auth_key' => Yii::t('app', 'Admin Auth Key'),
            'admin_password_hash' => Yii::t('app', 'Admin Password'),
            'admin_password_reset_token' => Yii::t('app', 'Admin Password Reset Token'),
            'admin_status' => Yii::t('app','Admin Status'),
            'admin_limited_access' => Yii::t('app','Admin Status'),
            'admin_created_at' => Yii::t('app','Admin Created At'),
            'admin_updated_at' => Yii::t('app','Admin Updated At'),
        ];
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\AdminToken")
    {
        return $this->hasMany($modelClass::className(), ['admin_id' => 'admin_id']);
    }

    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['admin_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $authType = HttpBearerAuth::class, $type = AdminToken::STATUS_ACTIVE, $otp = null) {

        $token = \admin\models\AdminToken::find()
            ->andWhere([
                'token_value' => $token,
                "token_status" => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('admin')
            ->one();

        if (!$token) {
            return false;
        }

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

        $token->token_status = AdminToken::STATUS_ACTIVE;//make inactive token to active on found with OTP
        $token->token_last_used_datetime = new Expression('NOW()');
        $token->save();

        //should not able to login, if email not verified but have valid token
        //max 3 attempt on 2 step auth
        if ($token->admin) {//&& $token->admin->email_verification
            return $token->admin;
        }

        //invalid token

        $token->delete();
    }

    /**
     * Create an Access Token Record for this Admin
     * if the admin already has one, it will return it instead
     * @return \common\models\AdminToken
     */
    public function getAccessToken($type = AdminToken::STATUS_ACTIVE) {
        // Return existing active token if found

        /* always generating new token, to have separate token per device
         * if ($type == AdminToken::STATUS_ACTIVE) {
            $token = AdminToken::find()
                ->andWhere([
                    'admin_id' => $this->admin_id,
                    'token_status' => $type
                ])
                ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
                ->one();

            if ($token) {
                return $token;
            }
        }*/

        //for in-active token, always sending new token, as to not activate token generated by hacker

        $detect = new DeviceDetector();

        $device = "Desktop Device";

        if ($detect->isMobile()) {
            $device = "Mobile Device";
        } elseif ($detect->isTablet()) {
            $device = "Tablet Device";
        }

        $token = new AdminToken();
        $token->admin_id = $this->admin_id;
        $token->token_value = AdminToken::generateUniqueTokenString();
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
     * Send OTP mail to admin
     * @param \common\models\AdminToken $token
     * @return bool
     */
    public function sendOTPMail($token) {

        //generate OTP
        $token->otp = Yii::$app->security->generateRandomString(4);
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        $ml = new MailLog();
        $ml->to = $this->admin_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'OTP for 2 step verification';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("admin/admin-otp",
            [
                "model" => $this,
                "otp" => $token->otp,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->admin_email)
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
     * Finds admin by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['admin_email' => $email, 'admin_status' => self::STATUS_ACTIVE]);
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
            'admin_password_reset_token' => $token,
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
        return $this->admin_auth_key;
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
        if (!$this->admin_password_hash) {
            return null;
        }

        return Yii::$app->security->validatePassword($password, $this->admin_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->admin_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->admin_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->admin_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->admin_password_reset_token = null;
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup() {
        if($this->validate()){
            $this->setPassword($this->admin_password_hash);
            $this->generateAuthKey();
            $this->save(false);

            Yii::info("[New Admin Account Created] ".$this->admin_email, __METHOD__);

            return $this;
        }
        return null;
    }

}
