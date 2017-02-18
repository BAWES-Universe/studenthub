<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "candidate".
 *
 * @property integer $candidate_id
 * @property integer $company_id
 * @property string $candidate_name
 * @property string $candidate_email
 * @property string $candidate_civil_id
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property integer $candidate_status
 * @property integer $candidate_created_at
 * @property integer $candidate_updated_at
 *
 * @property CandidateToken[] $accessTokens
 * @property Company $company
 */
class Candidate extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'candidate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_auth_key'], 'required'],
            [['candidate_password_hash'], 'required', 'on'=>'newAccount'],
            [['company_id', 'candidate_status'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_password_hash', 'candidate_password_reset_token'], 'string', 'max' => 255],
            [['candidate_auth_key'], 'string', 'max' => 32],
            [['candidate_email'], 'unique'],
            [['candidate_email'], 'email'],
            [['candidate_civil_id'], 'unique'],
            [['candidate_password_reset_token'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'candidate_created_at',
                'updatedAtAttribute' => 'candidate_updated_at',
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
            'candidate_id' => 'Candidate ID',
            'company_id' => 'Company ID',
            'candidate_name' => 'Candidate Name',
            'candidate_email' => 'Candidate Email',
            'candidate_civil_id' => 'Candidate Civil ID',
            'candidate_auth_key' => 'Candidate Auth Key',
            'candidate_password_hash' => 'Candidate Password Hash',
            'candidate_password_reset_token' => 'Candidate Password Reset Token',
            'candidate_status' => 'Candidate Status',
            'candidate_created_at' => 'Candidate Created At',
            'candidate_updated_at' => 'Candidate Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens()
    {
        return $this->hasMany(CandidateToken::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup() {
        $oldPasswordInput = $this->candidate_password_hash;

        $this->setPassword($this->candidate_password_hash);
        $this->generateAuthKey();

        if ($this->save()) {
            //Log candidate signup
            Yii::info("[New Candidate Account Created] ".$this->candidate_email, __METHOD__);

            return $this;
        }else{
            //Reset password to hide encrypted value
            $this->candidate_password_hash = $oldPasswordInput;
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
        return static::findOne(['candidate_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = CandidateToken::find()->where(['token_value' => $token])->with('candidate')->one();
        if($token){
            return $token->candidate;
        }
    }

    /**
     * Finds candidate by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['candidate_email' => $email]);
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
            'candidate_password_reset_token' => $token,
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
        return $this->candidate_auth_key;
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
        return Yii::$app->security->validatePassword($password, $this->candidate_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->candidate_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey() {
        $this->candidate_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generate, save, and return an auth key for this account [1 time use token]
     * @return string
     */
    public function generateAuthKeyAndSave() {
        $this->generateAuthKey();
        $this->save(false);

        return $this->candidate_auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->candidate_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->candidate_password_reset_token = null;
    }

    /**
     * Create an Access Token Record for this Candidate
     * if the candidate already has one, it will return it instead
     * @return \common\models\CandidateToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = CandidateToken::findOne([
            'candidate_id' => $this->candidate_id,
            'token_status' => CandidateToken::STATUS_ACTIVE
        ]);
        if($token){
            return $token;
        }

        // Create new inactive token
        $token = new CandidateToken();
        $token->candidate_id = $this->candidate_id;
        $token->token_value = CandidateToken::generateUniqueTokenString();
        $token->token_status = CandidateToken::STATUS_ACTIVE;
        $token->save(false);

        return $token;
    }
}
