<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;


/**
 * This is the model class for table "candidate".
 *
 * @property integer $candidate_id
 * @property string $candidate_uid
 * @property integer $store_id
 * @property integer $bank_id
 * @property integer $university_id
 * @property integer $country_id
 * @property string $bank_account_name
 * @property string $candidate_iban
 * @property string $candidate_name
 * @property string $candidate_name_ar
 * @property string $candidate_personal_photo
 * @property string $candidate_email
 * @property string $candidate_phone
 * @property string $candidate_address_line1
 * @property string $candidate_birth_date
 * @property string $candidate_civil_id
 * @property string $candidate_civil_expiry_date
 * @property string $candidate_civil_photo_front
 * @property string $candidate_civil_photo_back
 * @property float $candidate_hourly_rate
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property string $user_language_pref 
 * @property integer $candidate_status
 * @property integer $approved
 * @property string $candidate_created_at
 * @property string $candidate_updated_at
 * @property integer $deleted
 *
 * @property Bank $bank
 * @property Country $country
 * @property Store $store
 * @property Company $company
 * @property University $university
 * @property CandidateIdCard[] $candidateIdCards
 * @property CandidateToken[] $accessTokens
 * @property TransferCandidate[] $TransferCandidate
 */
class Candidate extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    // Candidate Status
    const STATUS_READY = 1;
    const STATUS_PENDING = 0;

    // Array of attribute names and folder names to store them in the permanent bucket
    public $FILE_ATTRIBUTES = [
        'candidate_personal_photo' => 'photos',
        'candidate_civil_photo_front' => 'civil-id',
        'candidate_civil_photo_back' => 'civil-id'
    ];

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
            [['university_id', 'country_id', 'bank_account_name', 'candidate_iban', 'candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_phone', 'candidate_birth_date', 'candidate_civil_id', 'candidate_civil_expiry_date', 'candidate_civil_photo_front', 'candidate_civil_photo_back', 'candidate_hourly_rate', 'candidate_personal_photo'], 'required'],
            [['candidate_password_hash'], 'required'],
            [['store_id', 'candidate_status', 'approved', 'bank_id'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_password_hash', 'candidate_password_reset_token', 'candidate_personal_photo'], 'string', 'max' => 255],
            [['candidate_iban', 'candidate_address_line1'], 'string', 'max' => 70],
            [['bank_account_name'], 'string', 'max' => 35],
            [['candidate_auth_key'], 'string', 'max' => 32],
            ['candidate_address_line1', 'default', 'value' => 'Kuwait'],
            [['candidate_uid'], 'string', 'max' => 20],
            [['candidate_email','candidate_phone'], 'unique'],
            [['candidate_email'], 'email'],
            ['candidate_language_pref', 'in', 'range' => ['en', 'ar']],
            [['candidate_civil_id'], 'unique'],
            [['bank_account_name', 'candidate_iban'], 'trim'],
            [['bank_account_name', 'candidate_iban'], 
                'match',
                'pattern' => '/^[0-9a-zA-Z\s]+$/',
                'message' => 'Special characters not allowed'
            ],  
            ['candidate_iban', 'validateIban'],
            ['candidate_hourly_rate', 'validateHourlyRate'],
            [['candidate_birth_date'], 'validateAge'],
            [['candidate_civil_expiry_date'], 'validateCivilExpiry'],
            [['candidate_password_reset_token'], 'unique'],
            ['candidate_status', 'default', 'value' => self::STATUS_PENDING],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::className(), 'targetAttribute' => ['country_id' => 'country_id']],
            [['university_id'], 'exist', 'skipOnError' => true, 'targetClass' => University::className(), 'targetAttribute' => ['university_id' => 'university_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],

            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['candidate_personal_photo'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => "Please upload a personal photo for the candidate",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
            [
                ['candidate_civil_photo_front'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => "Please upload a civil id photo (front) for the candidate",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
            [
                ['candidate_civil_photo_back'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => "Please upload a civil id photo (back) for the candidate",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ]
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();
 
        $scenarios["updateLanguagePref"] = ["user_language_pref"];
        
        $scenarios['signup'] = ['candidate_name', 'candidate_email', 'candidate_phone', 'candidate_password_hash'];
        
        return $scenarios;
    }

    /**
     * validate bank IBAN value
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function validateIban($attribute, $params, $validator)
    {  
        $banks = Bank::find()->all();
        
        $found = false; 
         
        foreach($banks as $bank) {
            if($bank->bank_iban_code && strpos(strtolower($this->candidate_iban), strtolower($bank->bank_iban_code)) > -1) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->addError($attribute, 'We do not support transfers to this bank.');
        } else if (!preg_match('/^[a-zA-Z0-9]{30}$/', $this->$attribute)) {
            $this->addError($attribute, 'Bank IBAN must contain exactly 30 digits.');
        }
    }

    /**
     * Validate candidate hourly rate 
     */
    public function validateHourlyRate()
    {
        if($this->candidate_hourly_rate <= 0)
        {
            $this->addError('candidate_hourly_rate', 'Candidate hourly rate should be greater than 0.');
            return null;
        }
        
        $max = 0;
        
        if($this->company && $this->company->company_hourly_rate)
        {
            $max = $this->company->company_hourly_rate;
        }
        elseif($this->company && $this->company->parentCompany)
        {
            $max =  $this->company->parentCompany->company_hourly_rate;
        }
        
        if($max && $this->candidate_hourly_rate > $max)
        {
            $this->addError('candidate_hourly_rate', 'Candidate hourly rate should be less than or equal to ' . $max . '.');
        }
    }
    
    /**
     * Validate Civil ID Expiry Date
     */
    public function validateCivilExpiry()
    {
        if(strtotime($this->candidate_civil_expiry_date) < strtotime(date('Y-m-d')))
        {
            $this->addError('candidate_civil_expiry_date', 'Candidate have expired civil id.');
        }
    }

    /**
     * Validate candidate age if exceeds limit
     */
    public function validateAge()
    {
        if($this->age < 18 || $this->age > 24) {
            $this->addError('candidate_birth_date', 'Candidate age should be between 18 to 24.');
        }
    }

    /**
     * @return array
     */
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
            'store_id' => 'Store ID',
            'bank_id' => 'Bank ID',
            'bank_account_name' => 'Bank account name',
            'candidate_iban' => 'IBAN',
            'candidate_name' => 'Name [English]',
            'candidate_name_ar' => 'Name [Arabic]',
            'candidate_personal_photo' => 'Personal Photo',
            'candidate_email' => 'Email',
            'candidate_phone' => 'Phone',
            'candidate_address_line1' => 'Candidate Address',
            'candidate_birth_date' => 'Birth Date',
            'candidate_civil_id' => 'Civil ID',
            'candidate_civil_expiry_date' => 'Civil Expiry Date',
            'candidate_civil_photo_front' => 'Civil Photo Front',
            'candidate_civil_photo_back' => 'Civil Photo Back',
            'candidate_hourly_rate' => 'Hourly Rate',
            'candidate_auth_key' => 'Auth Key',
            'candidate_password_hash' => 'Password',
            'candidate_password_reset_token' => 'Password Reset Token',
            'candidate_language_pref' => 'Language preference',
            'candidate_status' => 'Status',
            'candidate_created_at' => 'Created At',
            'candidate_updated_at' => 'Updated At',
            'employee_id' => 'Employee ID',
        ];
    }

    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);
        
        if($insert) 
        {
            Store::updateAllCounters(['store_total_candidates' => 1], ['store_id' => $this->store_id]);
        } 
        else if (array_key_exists('store_id', $changedAttributes)) 
        {
            Store::updateAllCounters(['store_total_candidates' => 1], ['store_id' => $this->store_id]);
            Store::updateAllCounters(['store_total_candidates' => -1], ['store_id' => $changedAttributes['store_id']]);
        } 
        else if (
            array_key_exists('candidate_iban', $changedAttributes) ||
            array_key_exists('bank_account_name', $changedAttributes) ||
            array_key_exists('bank_id', $changedAttributes)
        ) {
            //update bank details on all non paid transfers 
            
            \company\models\TransferCandidate::updateAll([
                'bank_id' => $this->bank_id,
                'transfer_benef_name' => $this->bank_account_name,
                'transfer_benef_iban' => $this->candidate_iban
            ], [
                'paid' => 0,
                'candidate_id' => $this->candidate_id
            ]);
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // Candidate Age
        $fields['age'] = function($model) {
            return $model->age;
        };

        $fields['employee_id'] = function($data) {
            $prefix = 'C';

            $digit_missing = 5 - strlen($this->candidate_id);

            if($digit_missing > 0) {
                $prefix .= str_repeat("0", $digit_missing);
            }

            return $prefix . $this->candidate_id;
        };

        // Url to thumb of profile photo
        $fields['candidate_personal_photo_thumb'] = function($model) {
            return substr_replace($model->candidate_personal_photo, "thumb-100/", 7, 0);
        };

        /**
         * Always Display Related Fields for Candidate model in this app
         * A Candidate is defined by all his relation to enable quick-loading
         * of candidate profiles on-click from the apps (without pinging server).
         */
        $fields = ArrayHelper::merge($fields, [
            'store',
            'company'
        ]);
        
        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'store',
            'company',
            'university',
            'country',
            'bank'
        ];
    }

    /**
     * Returns age of candidate
     * @return integer
     */
    public function getAge()
    {
        return floor((time() - strtotime($this->candidate_birth_date))/31556926);
    }

    /**
     * Moves the newly uploaded files from the temporary bucket to the permanent one
     * If their values have changed and their files exist in the temporary bucket.
     */
    private function _moveTemporaryFilesToPermanentBucket()
    {
        // For each file, move its file from temporary to permanent
        foreach($this->FILE_ATTRIBUTES as $attribute => $folderName){
            if($this->{$attribute} !== $this->getOldAttribute($attribute)){
                $fileName = $this->{$attribute};
                $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
                $targetPath = $folderName."/".$fileName;

                // Copy using S3ResourceManager Component
                Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

                // Generate a thumbnail of uploaded files
                $this->_generateThumbnail($fileName, $folderName);

                // Adjust filename in storage to use path within bucket
                $this->{$attribute} = $targetPath;
            }
        }
    }

    /**
     * Generate thumbnail for provided filename and store in corresponding folder in bucket
     * @param  string $fileName
     * @param  string $folderName
     */
    private function _generateThumbnail($fileName, $folderName, $size = 100)
    {
        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($fileName);

        // Create temporary file to store image in
        $tmpFile = sys_get_temp_dir() . '/' . $fileName;         
        $tmpHandle = fopen($tmpFile, 'w+');
        //tempnam(sys_get_temp_dir(), "TEMP");
        //rename($tmpFile, $fileName);
        //$tmpFile = $fileName;

        // Resize to $size x $size
        $thumbnail = new \Imagine\Gd\Imagine();
        $thumbnail = $thumbnail->open($fileUrl);//'https://bawes-public.s3.amazonaws.com/'.$fileName
        $thumbnail->resize($thumbnail->getSize()->widen($size));
        $thumbnail->save($tmpFile);

        // Save thumbnail to S3
        Yii::$app->resourceManager->save(
            null, //file upload object
            "$folderName/thumb-$size/$fileName", // name
            [], //options
            $tmpFile, // source file
            mime_content_type($tmpFile)
        );

        fclose($tmpHandle);
        @unlink($tmpFile);
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) 
            return false; 

        // Move uploaded files to permanent bucket
        $this->_moveTemporaryFilesToPermanentBucket();

        if (!$this->candidate_uid) {
            $this->candidate_uid = $this->generateUid();
        } 
        
        $banks = Bank::find()->all();

        foreach($banks as $bank) {
            if($bank->bank_iban_code && strpos(strtolower($this->candidate_iban), strtolower($bank->bank_iban_code)) > -1) {
                $this->bank_id = $bank->bank_id;
                break;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function generateUid()
    {
        $randomString = Yii::$app->getSecurity()->generateRandomString(20);

        if(!$this->findOne(['candidate_uid' => $randomString]))
            return $randomString;
        else
            return $this->generateUniqueRandomString();
    }

    /**
     * Return Employer ID in C00231 format where 231 is
     * candidate id
     */
    public function getEmployeeId()
    {
        $prefix = 'C';

        $digit_missing = 5 - strlen($this->candidate_id);

        if($digit_missing > 0) {
            $prefix .= str_repeat("0", $digit_missing);
        }

        return $prefix . $this->candidate_id;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\common\models\University")
    {
        return $this->hasOne($modelClass::className(), ['university_id' => 'university_id'])->andWhere(['{{%university}}.deleted'=>0]);

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\common\models\Bank")
    {
        return $this->hasOne($modelClass::className(), ['bank_id' => 'bank_id'])->andWhere(['{{%bank}}.deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id'])->andWhere(['{{%store}}.deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])->via('store')->andWhere(['{{%company}}.deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCard($modelClass = "\common\models\CandidateIdCard")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCards($modelClass = "\common\models\CandidateIdCard")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\CandidateToken")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup()
    {
        $this->setPassword($this->candidate_password_hash);
        $this->generateAuthKey();

        if($this->save()) {
            Yii::info("[New Candidate Account Created] ".$this->candidate_email, __METHOD__);

            return $this;
        }

        return false;
    }

    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['candidate_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
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
    public static function findByEmail($email)
    {
        return static::findOne(['candidate_email' => $email,'deleted'=>0]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {

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
    public static function isPasswordResetTokenValid($token)
    {
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
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->candidate_auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->candidate_password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->candidate_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey()
    {
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

    /**
     * Send candidate list having birthday today
     * to admin
     * @return null
     */
    public static function birthdayAlert()
    {
        $candidates = Candidate::find()
            ->where('MONTH(candidate_birth_date) = MONTH(NOW()) AND DAY(candidate_birth_date) = DAY(NOW())')
            ->all();

        if(!$candidates)
            return null;

        Yii::$app->mailer->compose("candidateBirthday",
            [
                "candidates" => $candidates,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Candidate having birthday today!')
            ->send();
    }

    /**
     * @return null
     */
    public static function ageAlert()
    {
        $candidates = Candidate::find()
            ->where('DATEDIFF(NOW(), candidate_birth_date)/365 >= 22')
            ->all();

        if(!$candidates)
            return null;

        Yii::$app->mailer->compose("candidateInvalidAge",
            [
                "candidates" => $candidates,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Candidate hits age 22!')
            ->send();
    }

    /**
     * @return null
     */
    public static function civilIdExpire()
    {
        $candidates = Candidate::find()
            ->where('candidate_civil_expiry_date < DATE(NOW())')
            ->all();

        if(!$candidates)
            return null;

        Yii::$app->mailer->compose("candidateIdExpire",
            [
                "candidates" => $candidates,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Candidate having invalid civil ID')
            ->send();
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
     * @param string $modelClass
     * @return mixed
     */
    public function getPaidTransferCandidate($modelClass= "\common\models\TransferCandidate")
    {
        $status = [
            Transfer::STATUS_TRANSFER_COMPLETE,
            Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
        ];

        return $modelClass::find()
            ->leftJoin('transfer','transfer.transfer_id=transfer_candidate.transfer_id')
            ->andWhere('{{%transfer}}.transfer_status IN('.implode(',', $status).')')
            ->filterCandidate($this->candidate_id)
            ->all();
    }   

    /**
     * @inheritdoc
     * @return query\CandidateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateQuery(get_called_class());
    }

    /**
     * @return array
     */
    public function getAccountStatistic() {

        $totalHours = 0;
        $totalPaid = 0;
        $totalBonus = 0;

        foreach ($this->transferCandidate as $transfer) {

            $totalHours += $transfer->hours;

            if (
                $transfer->invoice &&
                $transfer->invoice->invoice_status == 'paid'
            ) {
                $totalPaid += ($transfer->hours * $transfer->candidate_hourly_rate);
                $totalBonus += $transfer->bonus - $transfer->bonus_commission;
            }
        }
        
        return [
            'hours' => $totalHours,
            'paid' => $totalPaid,
            'bonus' => $totalBonus,
        ];
    }


    /**
     * Sends an email requesting a user to verify his email address
     * @return boolean whether the email was sent
     */
    public function sendVerificationEmail() {

        $this->generateAuthKey();

        //Update candidate last email limit timestamp
        $this->limit_email = new Expression('NOW()');
        $this->save(false);

        $email = $this->candidate_email;

        return Yii::$app->mailer->compose([
            'html' => 'candidate/verify-email-html',
            'text' => 'candidate/verify-email-text',
        ], [
            'candidate' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($email)
            ->setSubject('Please confirm your email address')
            ->send();
    }
}
