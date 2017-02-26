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
 * @property integer $store_id
 * @property string $candidate_name
 * @property string $candidate_name_ar
 * @property string $candidate_email
 * @property string $candidate_birth_date
 * @property string $candidate_civil_id
 * @property string $candidate_civil_expiry_date
 * @property string $candidate_civil_photo_front
 * @property string $candidate_civil_photo_back
 * @property float $candidate_hourly_rate
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property integer $candidate_status
 * @property string $candidate_created_at
 * @property string $candidate_updated_at
 *
 * @property CandidateToken[] $accessTokens
 * @property Company $company
 * @property Store $store
 */
class Candidate extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const STATUS_INCOMPLETE = 10;
    const STATUS_READY = 1;

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
            [['candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_birth_date', 'candidate_civil_id', 'candidate_civil_expiry_date', 'candidate_hourly_rate'], 'required'],
            [['candidate_password_hash'], 'required', 'on'=>'newAccount'],
            [['company_id', 'store_id', 'candidate_status'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_password_hash', 'candidate_password_reset_token'], 'string', 'max' => 255],
            [['candidate_auth_key'], 'string', 'max' => 32],
            [['candidate_hourly_rate'], 'number', 'max' => Yii::$app->params['candidate_max_hourly_rate']],
            [['candidate_email'], 'unique'],
            [['candidate_email'], 'email'],
            [['candidate_civil_id'], 'unique'],
            [['candidate_birth_date'], 'validateAge'],
            [['candidate_password_reset_token'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function validateAge()
    {
        $years = floor((time() - strtotime($this->candidate_birth_date))/31556926);

        if($years < 18 || $years > 21) {
            $this->addError('password', 'Candidate age should be between 18 to 21.');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            //check all values

            $attr = $this->attributes;

            unset($attr['candidate_password_reset_token']);

            //if have empty value

            if(in_array('', $attr)) {
                $this->candidate_status = Candidate::STATUS_INCOMPLETE;
            } else {
                $this->candidate_status = Candidate::STATUS_READY;
            }

            return true;
        } else {
            return false;
        }
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
            'store_id' => 'Store ID',
            'candidate_name' => 'Name [English]',
            'candidate_name_ar' => 'Name [Arabic]',
            'candidate_email' => 'Email',
            'candidate_birth_date' => 'Birth Date',
            'candidate_civil_id' => 'Civil ID',
            'candidate_civil_expiry_date' => 'Civil Expiry Date',
            'candidate_civil_photo_front' => 'Civil Photo Front',
            'candidate_civil_photo_back' => 'Civil Photo Back',
            'candidate_hourly_rate' => 'Hourly Rate',
            'candidate_auth_key' => 'Auth Key',
            'candidate_password_hash' => 'Password',
            'candidate_password_reset_token' => 'Password Reset Token',
            'candidate_status' => 'Status',
            'candidate_created_at' => 'Created At',
            'candidate_updated_at' => 'Updated At',
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
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['store_id' => 'store_id']);
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
        if($this->validate()){
            $this->setPassword($this->candidate_password_hash);
            $this->generateAuthKey();
            $this->save(false);

            Yii::info("[New Candidate Account Created] ".$this->candidate_email, __METHOD__);

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
