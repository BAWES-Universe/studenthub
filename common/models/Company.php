<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "company".
 *
 * @property integer $company_id
 * @property integer $parent_company_id
 * @property string $company_name
 * @property string $company_email
 * @property string $company_auth_key
 * @property string $company_password_hash
 * @property string $company_password_reset_token
 * @property integer $company_status
 * @property integer $company_created_at
 * @property integer $company_updated_at
 * @property integer $deleted
 *
 * @property Company $parentCompany
 * @property Company[] $subCompanies
 * @property CompanyToken[] $accessTokens
 * @property Invoice[] $invoices
 * @property Store[] $stores
 * @property Transfer[] $transfers
 * @property Candidate[] $candidates
 */
class Company extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{

    const STATUS_ACTIVE = 10;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'company';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_name'], 'required'],
            [['company_password_hash', 'company_email'], 'required', 'on'=>'newAccount'],
            [['company_email'], 'unique', 'on'=>'newAccount'],
            [['company_email'], 'email' , 'on'=>'newAccount'],
            [['company_password_hash'], 'required', 'on'=>'newSubAccount'], // for sub account
            [['parent_company_id', 'company_status'], 'integer'],
            [['parent_company_id'], 'validateCompany'],
            [['company_name', 'company_email', 'company_password_reset_token'], 'string', 'max' => 255],
            [['company_auth_key'], 'string', 'max' => 32],
            [['company_password_reset_token'], 'unique']
        ];
    }

    /**
     * find if company have store
     */
    public function validateCompany()
    {
        if(!$this->parentCompany) { 
            $this->addError('parent_company_id', "Parent Company not found.");
        } 
        
        if($this->parentCompany && $this->parentCompany->stores) {
            $this->addError('parent_company_id', "Company can't be assigned to company having stores.");
        }
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'company_created_at',
                'updatedAtAttribute' => 'company_updated_at',
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
            'company_id' => 'Company ID',
            'parent_company_id' => 'Parent Company',
            'company_name' => 'Company Name',
            'company_email' => 'Company Email',
            'company_auth_key' => 'Company Auth Key',
            'company_password_hash' => 'Password',
            'company_password_reset_token' => 'Company Password Reset Token',
            'company_status' => 'Company Status',
            'company_created_at' => 'Company Created At',
            'company_updated_at' => 'Company Updated At',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted'],
            $fields['company_password_hash'],
            $fields['company_password_reset_token'],
            $fields['company_auth_key']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
//            'company',
            'candidates'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['parent_company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        if($this->subCompanyStores)
        {
            //for parent company
            return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
                ->via('subCompanyStores')
                ->where(['{{%candidate}}.deleted' => 0]);
        }
        else
        {
            //for child company
            return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
                ->via('stores');
        }        
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        if(!$this->parent_company_id) //parent company         
        {
            return $this->hasMany(Invoice::className(), ['company_id' => 'company_id'])
                ->via('subCompanies');
        }
        else //child company
        {
            return $this->hasMany(Invoice::className(), ['company_id' => 'company_id']);
        }        
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->where('parent_transfer_id IS NULL')
            ->orderBy('transfer_id DESC');
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens()
    {
        return $this->hasMany(CompanyToken::className(), ['company_id' => 'company_id']);
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup() {
        if($this->validate()){
            $this->setPassword($this->company_password_hash);
            $this->generateAuthKey();
            $this->save(false);
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
        return static::findOne(['company_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = CompanyToken::find()->where(['token_value' => $token])->with('company')->one();
        if($token){
            return $token->company;
        }
    }

    /**
     * Finds company by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['company_email' => $email,'deleted'=>0]);
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
            'company_password_reset_token' => $token,
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
        return $this->company_auth_key;
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
        return Yii::$app->security->validatePassword($password, $this->company_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->company_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey() {
        $this->company_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generate, save, and return an auth key for this account [1 time use token]
     * @return string
     */
    public function generateAuthKeyAndSave() {
        $this->generateAuthKey();
        $this->save(false);

        return $this->company_auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->company_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->company_password_reset_token = null;
    }

    /**
     * Create an Access Token Record for this Company
     * if the company already has one, it will return it instead
     * @return \common\models\CompanyToken
     */
    public function getAccessToken(){
        // Return existing inactive token if found
        $token = CompanyToken::findOne([
            'company_id' => $this->company_id,
            'token_status' => CompanyToken::STATUS_ACTIVE
        ]);

        if($token) {
            return $token;
        }

        // Create new inactive token
        $token = new CompanyToken();
        $token->company_id = $this->company_id;
        $token->token_value = CompanyToken::generateUniqueTokenString();
        $token->token_status = CompanyToken::STATUS_ACTIVE;
        $token->save(false);

        return $token;
    }

    /**
     * @return bool
     */
    public static function adminPendingPaymentNotification()
    {
        $list = [];
        $data = \common\models\Candidate::find()
            ->select('candidate.candidate_id,candidate.store_id,store.store_id,store.company_id')
            ->leftJoin('store','store.store_id = candidate.store_id ')
            ->where('candidate.store_id != "NULL"')
            ->groupBy('store.company_id')
            ->all();

        if ($data) {
            foreach($data as $key => $company) {
                $today = date('Y-m-d H:i:s');
                $interval = date('Y-m-d H:i:s',strtotime(Yii::$app->params['payment_notice_period']));
                $company_id =  $company->store->company_id;
                $condition = "transfer_created_at BETWEEN date('$interval') AND date('$today') AND transfer_status='".Transfer::STATUS_TRANSFER_COMPLETE."' AND `company_id`='$company_id'";
                if (!Transfer::find()->where($condition)->count()) {
                    $list[] = $company->store->company_id;
                }
            }
            if (count($list)>0) {

                return Yii::$app->mailer->compose("company-unpaid-notification-to-admin",
                    [
                        "companies" => \common\models\Company::find()->where(['company_id'=>$list])->all(),
                    ])
                    ->setFrom(Yii::$app->params['supportEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject('Company not paid after 35 days')
                    ->send();
            }
        }
    }

    /**
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\CompanyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CompanyQuery(get_called_class());
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getSubCompanyStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->via('subCompanies')
            ->where(['deleted'=>0]);
    }

    /**
     * @param $company_id
     * @return int|string
     */
    public static function getTotalCandidateCount($company_id){

        // create company_id array from all sub companies and self
        $companies = Company::findAll(['parent_company_id' => $company_id]);
        $company_ids = yii\helpers\ArrayHelper::map($companies, 'company_id', 'company_id');
        $company_ids[] = $company_id;

        // create store_id array

        $stores = Store::find()
            ->where(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = yii\helpers\ArrayHelper::map($stores, 'store_id', 'store_id');

        return Candidate::find()
            ->where(['in', 'store_id', $store_ids])
            ->count();
    }
}
