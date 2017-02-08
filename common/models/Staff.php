<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "staff".
 *
 * @property integer $staff_id
 * @property string $staff_name
 * @property string $staff_email
 * @property string $staff_auth_key
 * @property string $staff_password_hash
 * @property string $staff_password_reset_token
 * @property integer $staff_status
 * @property integer $staff_created_at
 * @property integer $staff_updated_at
 */
class Staff extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'staff';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['staff_name', 'staff_email', 'staff_auth_key', 'staff_password_hash', 'staff_created_at', 'staff_updated_at'], 'required'],
            [['staff_status', 'staff_created_at', 'staff_updated_at'], 'integer'],
            [['staff_name', 'staff_email', 'staff_password_hash', 'staff_password_reset_token'], 'string', 'max' => 255],
            [['staff_auth_key'], 'string', 'max' => 32],
            [['staff_email'], 'unique'],
            [['staff_password_reset_token'], 'unique'],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'staff_created_at',
                'updatedAtAttribute' => 'staff_updated_at',
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
            'staff_id' => 'Staff ID',
            'staff_name' => 'Staff Name',
            'staff_email' => 'Staff Email',
            'staff_auth_key' => 'Staff Auth Key',
            'staff_password_hash' => 'Staff Password Hash',
            'staff_password_reset_token' => 'Staff Password Reset Token',
            'staff_status' => 'Staff Status',
            'staff_created_at' => 'Staff Created At',
            'staff_updated_at' => 'Staff Updated At',
        ];
    }


    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['staff_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = AgentToken::find()->where(['token_value' => $token])->with('agent')->one();
        if($token){
            return $token->agent;
        }
    }

    /**
     * Finds agent by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['staff_email' => $email]);
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
            'staff_password_reset_token' => $token,
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
        return $this->staff_auth_key;
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
        return Yii::$app->security->validatePassword($password, $this->staff_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->staff_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey() {
        $this->staff_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generate, save, and return an auth key for this account [1 time use token]
     * @return string
     */
    public function generateAuthKeyAndSave() {
        $this->generateAuthKey();
        $this->save(false);

        return $this->staff_auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->staff_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->staff_password_reset_token = null;
    }

    /**
     * Create an Access Token Record for this Agent
     * if the agent already has one, it will return it instead
     * @return \common\models\AgentToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        // $token = AgentToken::findOne([
        //     'staff_id' => $this->staff_id,
        //     'token_status' => AgentToken::STATUS_ACTIVE
        // ]);
        // if($token){
        //     return $token;
        // }
        //
        // // Create new inactive token
        // $token = new AgentToken();
        // $token->staff_id = $this->staff_id;
        // $token->token_value = AgentToken::generateUniqueTokenString();
        // $token->token_status = AgentToken::STATUS_ACTIVE;
        // $token->save(false);
        //
        // return $token;
    }
}
