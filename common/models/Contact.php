<?php

namespace common\models;

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
 * @property string $contact_limit_email
 * @property string $contact_password_hash
 * @property string $contact_auth_key
 * @property string $contact_receive_email
 * @property string $contact_receive_notification
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
            [['contact_name', 'contact_email', 'contact_password_hash'], 'required'],
            [['contact_created_datetime', 'contact_updated_datetime'], 'safe'],
            [['contact_uuid'], 'string', 'max' => 60],
            [['contact_email', 'contact_new_email'], 'email'],
            [['contact_name', 'contact_password_reset_token',], 'string', 'max' => 255],
            [['contact_uuid', 'contact_email'], 'unique'],
            [['contact_password_reset_token'], 'unique'],
        ];
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

        $scenarios['signup'] = ['contact_name', 'contact_email', 'contact_password_hash', 'contact_receive_email'];

        $scenarios['updateEmail'] = ['contact_email', 'contact_new_email'];

        return $scenarios;
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
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
            'contact_limit_email' => Yii::t('app', 'Contact Limit Email'),
            'contact_receive_email' => Yii::t('app','Receive Email?'),
            'contact_receive_notification' => Yii::t('app','Receive Notification?'),
            'contact_auth_key' => Yii::t('app','Auth Key'),
            'contact_password_hash' => Yii::t('app','Password'),
            'contact_password_reset_token' => Yii::t('app','Password Reset Token'),
            'contact_created_datetime' => Yii::t('app', 'Contact Created Datetime'),
            'contact_updated_datetime' => Yii::t('app', 'Contact Updated Datetime'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted'],
            $fields['contact_password_hash'],
            $fields['contact_password_reset_token'],
            $fields['contact_auth_key']);

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
            ->filterWhere(['allow_access' => true]);
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

        $token = ContactToken::find()->where([
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
        $this->contact_auth_key = Yii::$app->security->generateRandomString();
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

    public function getCompanyContact($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
}
