<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
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
 * @property integer $deleted
 *
 * @property StaffToken[] $accessTokens
 */
class Staff extends ActiveRecord implements IdentityInterface
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
            [['staff_name', 'staff_email'], 'required'],
            [['staff_password_hash'], 'required', 'on'=>'newAccount'],
            [['staff_status'], 'integer'],
            [['staff_name', 'staff_email', 'staff_password_hash', 'staff_password_reset_token'], 'string', 'max' => 255],
            [['staff_auth_key'], 'string', 'max' => 32],
            [['staff_email'], 'unique'],
            [['staff_email'], 'email'],
            [['staff_password_reset_token'], 'unique'],
        ];
    }

    /**
     * @return array
     */
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
            'staff_id' => Yii::t('app','Staff ID'),
            'staff_name' => Yii::t('app','Staff Name'),
            'staff_email' => Yii::t('app','Staff Email'),
            'staff_auth_key' => Yii::t('app','Staff Auth Key'),
            'staff_password_hash' => Yii::t('app','Password'),
            'staff_password_reset_token' => Yii::t('app','Staff Password Reset Token'),
            'staff_status' => Yii::t('app','Staff Status'),
            'staff_created_at' => Yii::t('app','Staff Created At'),
            'staff_updated_at' => Yii::t('app','Staff Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['deleted']);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
        ];
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens()
    {
        return $this->hasMany(StaffToken::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes()
    {
        return $this->hasMany(\staff\models\Note::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup() {
        if($this->validate()){
            $this->setPassword($this->staff_password_hash);
            $this->generateAuthKey();
            $this->save(false);

            Yii::info("[New Staff Account Created] ".$this->staff_email, __METHOD__);

            return $this;
        }
        return null;
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
        $token = StaffToken::find()->where(['token_value' => $token])->with('staff')->one();
        if($token){
            return $token->staff;
        }
    }

    /**
     * Finds staff by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['staff_email' => $email,'deleted'=>0]);
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
     * @return mixed
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @return string
     */
    public function getAuthKey() {
        return $this->staff_auth_key;
    }

    /**
     * @param string $authKey
     * @return bool
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
     * Create an Access Token Record for this Staff
     * if the staff user already has one, it will return it instead
     * @return \common\models\StaffToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = StaffToken::findOne([
            'staff_id' => $this->staff_id,
            'token_status' => StaffToken::STATUS_ACTIVE
        ]);
        if($token){
            return $token;
        }

        // Create new inactive token
        $token = new StaffToken();
        $token->staff_id = $this->staff_id;
        $token->token_value = StaffToken::generateUniqueTokenString();
        $token->token_status = StaffToken::STATUS_ACTIVE;
        $token->save(false);

        return $token;
    }

    /**
     * @return bool
     */
    public function softDelete() {
        $this->deleted = 1;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\StaffQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\StaffQuery(get_called_class());
    }
}
