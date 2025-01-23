<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;
use yii\helpers\Url;


/**
 * This is the model class for table "inspector".
 *
 * @property string $inspector_uuid
 * @property string $inspector_name
 * @property string $inspector_email
 * @property string $inspector_auth_key
 * @property string $inspector_password_hash
 * @property string $inspector_password_reset_token
 * @property int $inspector_status
 * @property int $inspector_deleted
 * @property string $inspector_created_at
 * @property string $inspector_updated_at
 */
class Inspector extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inspector';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['inspector_name', 'inspector_email','inspector_password_hash'], 'required'],
            [['inspector_status', 'inspector_deleted'], 'integer'],
            [['inspector_created_at', 'inspector_updated_at'], 'safe'],
            [['inspector_uuid'], 'string', 'max' => 60],
            [['inspector_name', 'inspector_email', 'inspector_password_hash', 'inspector_password_reset_token'], 'string', 'max' => 255],
            [['inspector_auth_key'], 'string', 'max' => 32],
            [['inspector_email'], 'unique'],
            [['inspector_password_reset_token'], 'unique'],
            [['inspector_uuid'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'inspector_uuid',
                ],
                'value' => function() {
                    if(!$this->inspector_uuid)
                        $this->inspector_uuid = 'insp_'. Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->inspector_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'inspector_created_at',
                'updatedAtAttribute' => 'inspector_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['inspector_auth_key'],
            $fields['inspector_password_hash'],
            $fields['inspector_password_reset_token'],
            $fields['inspector_deleted']
        );

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'inspector_uuid' => 'Inspector Uuid',
            'inspector_name' => 'Inspector Name',
            'inspector_email' => 'Inspector Email',
            'inspector_auth_key' => 'Inspector Auth Key',
            'inspector_password_hash' => 'Inspector Password',
            'inspector_password_reset_token' => 'Inspector Password Reset Token',
            'inspector_status' => 'Inspector Status',
            'inspector_deleted' => 'Inspector Deleted',
            'inspector_created_at' => 'Inspector Created At',
            'inspector_updated_at' => 'Inspector Updated At',
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();

        $scenarios['updatePassword'] = ['inspector_password_hash', 'inspector_password_reset_token'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;

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
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave ($insert, $changedAttributes);

        //Send Email to inspector if password updated

        if($this->scenario == 'updatePassword') {
            $this->sendPasswordUpdatedEmail ();
        }
    }

    /**
     * notify inspector for password update
     */
    public function sendPasswordUpdatedEmail()
    {
        $ml = new MailLog();
        $ml->to = $this->inspector_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Your password reset was a success";
        $ml->save();

        $mailer = Yii::$app->mailer->compose("inspector/password-updated-html",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->inspector_email,
                "name" => $this->inspector_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->inspector_email)
            ->setSubject('Your password reset was a success');

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
     * Send link in email to reset password
     * @return bool
     */
    public function sendPasswordResetEmail()
    {
        $this->generatePasswordResetToken();
        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['inspectorAppUrl'] . 'update-password/' . $this->inspector_password_reset_token;

        $ml = new MailLog();
        $ml->to = $this->inspector_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Reset your StudentHub password";
        $ml->save();

        $mailer = Yii::$app->mailer->compose("inspector/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->inspector_email,
                "name" => $this->inspector_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->inspector_email)
            ->setSubject('Reset your StudentHub password');

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
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['inspector_uuid' => $id, 'inspector_deleted' => 0]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = InspectorToken::find()
            ->andWhere(['token_value' => $token])->with('inspector')->one();

        if($token){
            return $token->inspector;
        }
    }

    /**
     * Finds admin by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['inspector_email' => $email, 'inspector_deleted' => 0]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        /*if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }*/

        return static::findOne([
            'inspector_password_reset_token' => $token,
            'inspector_deleted' => 0
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
        return $this->inspector_auth_key;
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
        if (!$this->inspector_password_hash) {
            return null;
        }

        return Yii::$app->security->validatePassword($password, $this->inspector_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->inspector_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->inspector_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->inspector_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->inspector_password_reset_token = null;
    }

    /**
     * Create an Access Token Record for this Inspector
     * if the admin already has one, it will return it instead
     * @return \common\models\InspectorToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = InspectorToken::findOne([
            'inspector_uuid' => $this->inspector_uuid,
            'token_status' => InspectorToken::STATUS_ACTIVE
        ]);
        if($token){
            return $token;
        }

        // Create new inactive token
        $token = new InspectorToken();
        $token->inspector_uuid = $this->inspector_uuid;
        $token->token_value = AdminToken::generateUniqueTokenString();
        $token->token_status = AdminToken::STATUS_ACTIVE;
        $token->save(false);

        return $token;
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup() {
        if($this->validate()) {

            if($this->inspector_password_hash)
                $this->setPassword($this->inspector_password_hash);

            $this->generateAuthKey();
            $this->save(false);

            Yii::info("[New Inspector Account Created] ".$this->inspector_email, __METHOD__);

            return $this;
        }

        return null;
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = '\common\models\InspectorToken')
    {
        return $this->hasMany($modelClass::className(), ['inspector_uuid' => 'inspector_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\InspectorQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\InspectorQuery(get_called_class());
    }
}
