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
 * @property string $candidate_gender
 * @property string $candidate_objective
 * @property string $candidate_personal_photo
 * @property string $candidate_email
 * @property string $candidate_new_email
 * @property string $candidate_email_verification
 * @property string $candidate_limit_email
 * @property string $candidate_phone
 * @property string $candidate_address_line1
 * @property string $candidate_birth_date
 * @property string $candidate_civil_id
 * @property string $candidate_civil_expiry_date
 * @property string $candidate_civil_photo_front
 * @property string $candidate_civil_photo_back
 * @property string $candidate_driving_license
 * @property string $candidate_resume
 * @property float $candidate_hourly_rate
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property string $candidate_language_pref 
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
    const STATUS_ACTIVE = 10;//default status
    
    //Email verification values for `candidate_email_verification`
    const EMAIL_VERIFIED = 1;
    const EMAIL_NOT_VERIFIED = 0;
    
    //Gender values for `gender`
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;
    
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
            [['university_id', 'country_id', 'candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_phone', 'candidate_birth_date', 'candidate_civil_id', 'candidate_civil_expiry_date', 'candidate_civil_photo_front', 'candidate_civil_photo_back', 'candidate_hourly_rate', 'candidate_personal_photo'], 'required'],
            [['candidate_password_hash'], 'required'],
            [['store_id', 'candidate_status', 'candidate_email_verification', 'approved', 'bank_id', 'candidate_driving_license'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_password_hash', 'candidate_password_reset_token', 'candidate_personal_photo'], 'string', 'max' => 255],
            [['candidate_iban', 'candidate_address_line1'], 'string', 'max' => 70],
            [['bank_account_name'], 'string', 'max' => 35],
            [['candidate_auth_key'], 'string', 'max' => 32],
            ['candidate_address_line1', 'default', 'value' => 'Kuwait'],
            [['candidate_uid'], 'string', 'max' => 20],
            [['candidate_email','candidate_phone'], 'unique'],
            [['candidate_email', 'candidate_new_email'], 'email'],
            //['approved', 'default', 'value'=> false],
            [['candidate_new_email'], 'validateNewEmail'],
            ['candidate_limit_email', 'safe'],
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
    
            ['candidate_gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER]],
                    
            [['candidate_objective'], 'string', 'max' => 100],
                    
            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['candidate_personal_photo'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a personal photo for the candidate"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
                    
            [
                ['candidate_resume'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => "Please upload resume",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
                    
            [
                ['candidate_civil_photo_front'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a civil id photo (front) for the candidate"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
            [
                ['candidate_civil_photo_back'], 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a civil id photo (back) for the candidate"),
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
 
        $scenarios['updateName'] = ['candidate_name'];
        
        $scenarios['updateNameAr'] = ['candidate_name_ar'];
        
        $scenarios['candidate_personal_photo'] = ['candidate_personal_photo'];
        
        $scenarios['updateCivilId'] = ['candidate_civil_id'];
        
        $scenarios["updateLanguagePref"] = ["candidate_language_pref"];
        
        $scenarios['updateEmail'] = ['candidate_email', 'candidate_new_email'];
        
        $scenarios['updateNationality'] = ['country_id'];
        
        $scenarios['updateDrivingLicense'] = ['candidate_driving_license'];
        
        $scenarios['updateObjective'] = ['candidate_objective'];
        
        $scenarios['updateGender'] = ['candidate_gender'];
        
        $scenarios['updateUniversity'] = ['university_id'];
        
        $scenarios['updateResume'] = ['candidate_resume'];
        
        $scenarios['updateBirthDate'] = ['candidate_birth_date'];
        
        $scenarios['signup'] = ['candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_phone', 'candidate_password_hash'];
        
        return $scenarios;
    }

    /**
     * Validate email in new_email field
     */
    public function validateNewEmail() {
        $count = self::find()
            ->andWhere(['!=', 'candidate_id', $this->candidate_id])
            ->andWhere([
                'or',
                ['candidate_new_email' => $this->candidate_new_email],
                ['candidate_email' => $this->candidate_new_email]
            ])
            ->count();

        if ($count) {
            $this->addError('candidate_email', Yii::t('app', 'Email already registered'));
        }
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
            $this->addError($attribute, Yii::t('candidate','We do not support transfers to this bank.'));
        } else if (!preg_match('/^[a-zA-Z0-9]{30}$/', $this->$attribute)) {
            $this->addError($attribute, Yii::t('candidate','Bank IBAN must contain exactly 30 digits.'));
        }
    }

    /**
     * Validate candidate hourly rate 
     */
    public function validateHourlyRate()
    {
        if($this->candidate_hourly_rate <= 0)
        {
            $this->addError('candidate_hourly_rate', Yii::t('candidate','Candidate hourly rate should be greater than 0.'));
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

            $this->addError('candidate_hourly_rate', Yii::t('candidate', "Candidate hourly rate should be less than or equal to {max}.", ['max' => $max]));
        }
    }
    
    /**
     * Validate Civil ID Expiry Date
     */
    public function validateCivilExpiry()
    {
        if(strtotime($this->candidate_civil_expiry_date) < strtotime(date('Y-m-d')))
        {
            $this->addError('candidate_civil_expiry_date', Yii::t('candidate','Candidate have expired civil id.'));
        }
    }

    /**
     * Validate candidate age if exceeds limit
     */
    public function validateAge()
    {
        if($this->age < 18 || $this->age > 24) {
            $this->addError('candidate_birth_date', Yii::t('candidate','Candidate age should be between 18 to 24.'));
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
            'candidate_id' => Yii::t('candidate','Candidate ID'),
            'store_id' => Yii::t('candidate','Store ID'),
            'bank_id' => Yii::t('candidate','Bank ID'),
            'bank_account_name' => Yii::t('candidate','Bank account name'),
            'candidate_iban' => Yii::t('candidate','IBAN'),
            'candidate_name' => Yii::t('candidate','Name [English]'),
            'candidate_name_ar' => Yii::t('candidate','Name [Arabic]'),
            'candidate_gender' => Yii::t('candidate','Gender'),
            'candidate_objective' => Yii::t('candidate','Objective'),
            'candidate_personal_photo' => Yii::t('candidate','Personal Photo'),
            'candidate_email' => Yii::t('candidate','Email'),
            'candidate_new_email' => Yii::t('candidate','New Email'),
            'candidate_email_verification' => Yii::t('candidate','Email Verification'),
            'candidate_limit_email' => Yii::t('candidate','Limit Email'),
            'candidate_phone' => Yii::t('candidate','Phone'),
            'candidate_address_line1' => Yii::t('candidate','Candidate Address'),
            'candidate_birth_date' => Yii::t('candidate','Birth Date'),
            'candidate_civil_id' => Yii::t('candidate','Civil ID'),
            'candidate_civil_expiry_date' => Yii::t('candidate','Civil Expiry Date'),
            'candidate_civil_photo_front' => Yii::t('candidate','Civil Photo Front'),
            'candidate_civil_photo_back' => Yii::t('candidate','Civil Photo Back'),
            'candidate_driving_license' => Yii::t('candidate','Driving License'),
            'candidate_resume' => Yii::t('candidate','Resume'),
            'candidate_hourly_rate' => Yii::t('candidate','Hourly Rate'),
            'candidate_auth_key' => Yii::t('candidate','Auth Key'),
            'candidate_password_hash' => Yii::t('candidate','Password'),
            'candidate_password_reset_token' => Yii::t('candidate','Password Reset Token'),
            'candidate_language_pref' => Yii::t('candidate','Language preference'),
            'candidate_status' => Yii::t('candidate','Status'),
            'candidate_created_at' => Yii::t('candidate','Created At'),
            'candidate_updated_at' => Yii::t('candidate','Updated At'),
            'employee_id' => Yii::t('candidate','Employee ID')
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
        
        if (
            $this->candidate_status == self::STATUS_ACTIVE && 
            //$this->approved &&
            !in_array(
                $this->scenario, [
                    'updateLanguagePref', //as not saving language preference in algolia  
                    'signup', //as will have incomplete profile
                    'updateEmail'//as not saving email in algolia 
                ]
            )
        ) { 
            return $this->updateAlgoliaIndex($insert);
        }  

        //on soft delete remove 
        
        if (isset($changedAttributes['deleted']) && $this->deleted) {
            Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $this->candidate_id);
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
        unset($fields['candidate_uid']);

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
            'bank',
            'candidateSkills',
            'candidateExperiences'
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
        
        $this->bank_id = null; 
            
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
        return $this->hasOne($modelClass::className(), ['university_id' => 'university_id'])
            ->andWhere(['{{%university}}.deleted'=>0]);

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
        return $this->hasOne($modelClass::className(), ['bank_id' => 'bank_id'])
            ->andWhere(['{{%bank}}.deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id'])
            ->andWhere(['{{%store}}.deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])
            ->via('store')
            ->andWhere(['{{%company}}.deleted'=>0]);
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
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateSkills($modelClass = "\common\models\CandidateSkill")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateExperiences($modelClass = "\common\models\CandidateExperience")
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
     * @inheritdoc
     */
    public static function findIdentityByUnVerifiedTokenToken($token, $type = null) {
        $token = CandidateToken::find()->where(['token_value' => $token])
                ->with('candidate')
                ->one();

        if ($token && $token->candidate && !$token->candidate->deleted) {
            return $token->candidate;
        }
    }
    
    /**
     * Verifies the candidate email
     */
    public static function verifyEmail($code) {
        //Code is his auth key, check if code is valid
        //        $candidate = Candidate::find()->where("auth_key like binary '{$code}'")->one(); // disable case sensistive
        // due to #169799637
        $candidate = Candidate::findOne(['candidate_auth_key' => $code]);

//        $candidate = Candidate::find()
//            ->where("auth_key like binary '{$code}'")
//            ->one();

        if ($candidate && $candidate->candidate_auth_key == $code) { //to cope with sql case insensitivity
            //If not verified
            if ($candidate->candidate_email_verification == Candidate::EMAIL_NOT_VERIFIED) {
                //Verify this candidates email
                $candidate->candidate_email_verification = Candidate::EMAIL_VERIFIED;
            }

            // new email address

            if (!empty($candidate->candidate_new_email)) {
                $candidate->candidate_email = $candidate->candidate_new_email;
                $candidate->candidate_new_email = null;
            }

            $candidate->candidate_auth_key = ''; //remove auth key
            $candidate->save(false);

            return $candidate;
        } else {
            return false;
        }
    }
    
    /**
     * delete resume
     * @return boolean
     */
    public function deleteResume() {
        
        try {

            Yii::$app->resourceManager->delete("candidate-resume/" . $this->candidate_resume);  

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to delete.'));

            return false;

        } catch (Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to delete.'));

            return false;
        }   
    }
    
    /**
     * save resume to permanent bucket
     * @return boolean
     */
    public function updateResume() {

        $fileName = $this->candidate_resume;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        $targetPath = "candidate-resume/" . $fileName;

        // Copy using S3ResourceManager Component

        try {

            Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;

        } catch (Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;
        }
        
        return $this->save();
    }
    
    /**
     * Update profile photo from temp s3 bucket
     * @return type
     */
    public function updateProfilePhoto() {

        try {
            $url = Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_personal_photo);

            $this->setProfileByUrl($url);

            $this->scenario = 'changeProfilePhoto';

            return $this->save();
        } catch (\Exception $e) {
            
            Yii::error($e->getMessage(), 'candidate');
                    
            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }
    }

    /**
     * delete old profile photo from cloudinary
     * @return boolean
     */
    public function deleteProfilePhotoFromCloudinary() {
        
        try {
            
            Yii::$app->cloudinaryManager->delete("candidate-photo/" . $this->candidate_personal_photo);
        
        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            //$this->addError('profile_photo', Yii::t('app', 'Please try again.'));

            return false;
            
        } catch (Exception $e) {
            
            Yii::error($e->getMessage(), 'candidate');
            
            //$this->addError('profile_photo', Yii::t('app', 'Image not available to save.'));
            
            return false;
        }
    }
    
    /**
     * Set profile photo by url
     * @param string $url
     */
    public function setProfileByUrl($url) {

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic
        
        if ($this->candidate_personal_photo) {
            $this->deleteProfilePhotoFromCloudinary();
        }

        try {
            $result = Yii::$app->cloudinaryManager->upload(
                $url, [
                    'public_id' => "candidate-photo/" . $filename
                ]
            );

            if ($result)
                $this->candidate_personal_photo = basename($result['url']);
            
        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Please try again.'));

            return false;
            
        } catch (Exception $e) {
            
            Yii::error($e->getMessage(), 'candidate');
            
            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));
            
            return false;
        }
    }
    
    /**
     * Sends an email requesting a user to verify his email address
     * @return boolean whether the email was sent
     */
    public function sendVerificationEmail() {

        $this->generateAuthKey();

        //Update candidate last email limit timestamp
        $this->candidate_limit_email = new Expression('NOW()');
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
    
    /**
     * is candidate profile complete?
     * @return boolean
     */
    public function isProfileCompleted() {
        return $this->isInCompleteProfile() ? false : true;
    }

    /**
     * Checks is candidate have incomplete profile 
     * @return void|string
     */
    public function isInCompleteProfile() {
        if (!$this->candidate_uid) {
            return 'candidate_uid';
        }

        if (!$this->store) {
            return 'store_id';
        }
        
        if (!$this->bank) {
            return 'bank_id';
        }
        
        if (!$this->university) {
            return 'university_id';
        }
        
        if (!$this->country) {
            return 'country_id';
        }
        
        if (!$this->bank_account_name) {
            return 'bank_account_name';
        }
        
        if (!$this->candidate_iban) {
            return 'candidate_iban';
        }
        
        if (!$this->candidate_name) {
            return 'name';
        }
        
        if (!$this->candidate_name_ar) {
            return 'name_ar';
        }

        if (!in_array($this->candidate_gender, [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER])) {
            return 'gender';
        }
        
        if (!$this->candidate_objective) {
            return 'objective';
        }
        
        if (!$this->candidate_personal_photo) {
            return 'personal_photo';
        }
        
        if (!$this->candidate_email) {
            return 'email';
        }
        
        if (!$this->candidate_phone) {
            return 'phone';
        }
        
        if (!$this->candidate_address_line1) {
            return 'address_line1';
        }
        
        if (!$this->candidate_birth_date) {
            return 'birth_date';
        }
        
        if (!$this->candidate_civil_id) {
            return 'civil_id';
        } 
        
        if (!$this->candidate_civil_expiry_date) {
            return 'civil_expiry_date';
        } 
        
        if (!$this->candidate_civil_photo_front) {
            return 'civil_photo_front';
        } 
        
        if (!$this->candidate_civil_photo_back) {
            return 'civil_photo_back';
        } 
        
        if (!$this->candidate_driving_license) {
            return 'driving_license';
        }
        
        if (!$this->candidate_resume) {
            return 'resume';
        }

        if (!$this->candidate_hourly_rate) {
            return 'hourly_rate';
        }

        if ($this->getCandidateExperiences()->count() == 0) {
            return 'experience';
        }
        
        if ($this->getCandidateSkills()->count() == 0) {
            return 'skill';
        }
    } 
    
    /**
     * Update/Insert data on algolia index
     * @param bool $insert
     */
    public function updateAlgoliaIndex($insert = false) {
        
        $data = $this->prepareAlgoliaData($insert);

        //if profile incomplete

        if (!$data) {
            return false;
        }

        if ($insert) { // candidate registered
            Yii::$app->algolia->add(Yii::$app->params['algolia_candidate_index'], $data);
        } else { // candidate data updated
            Yii::$app->algolia->partialUpdate(Yii::$app->params['algolia_candidate_index'], $data);
        }
    }

    /**
     * Return array of job detail to update in algolia index
     */
    public function prepareAlgoliaData($insert = false) {

        if (
            $this->deleted || 
            !$this->candidate_email_verification || 
            $this->candidate_status != self::STATUS_ACTIVE
        ) {
            return false;
        }

        $isProfileCompleted = $this->isProfileCompleted();
 
        if (!$isProfileCompleted) {

            //delete from algolia 

            Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $this->candidate_id);

            return false;
        }

        $data = [
            'objectID' => $this->candidate_id,
            'candidate_id' => $this->candidate_id,
            'bank_account_name' => $this->bank_account_name,
            'candidate_iban' => $this->candidate_iban,
            'candidate_name' => $this->candidate_name,
            'candidate_name_ar' => $this->candidate_name_ar,
            'candidate_objective' => $this->candidate_objective,
            'candidate_personal_photo' => $this->candidate_personal_photo,
            'candidate_birth_date' => $this->candidate_birth_date,
            'candidate_driving_license' => $this->candidate_driving_license,
            'university' => [
                'university_id' => $this->university_id,
                'university_name_en' => $this->university->university_name_en,
                'university_name_ar' => $this->university->university_name_ar
            ],
            'country' => [
                'country_id' => $this->country_id,
                'country_name_en' => $this->country->country_name_en,
                'country_name_ar' => $this->country->country_name_ar
            ],
        ];
                  
        if($this->store) {
            $data['store'] = [
                'store_name' => $this->store->store_name,
                'store_total_candidate' => $this->store->store_total_candidates,
                'company' => [
                    'company_name' => $this->store->company->company_name
                ]
            ];
        }
        
        if($this->bank) {
            $data['bank'] = [
                'bank_id' => $this->bank_id,
                'bank_name' => $this->bank->bank_name
            ];
        }   
                
        //to make gender label visible to filter instead of 1,0 

        if ($this->candidate_gender == self::GENDER_FEMALE) {
            $data['candidate_gender'] = 'Female';
        } elseif ($this->candidate_gender == self::GENDER_MALE) {
            $data['candidate_gender'] = 'Male';
        } else {
            $data['candidate_gender'] = 'Other';
        }

        if ($insert) {
            $data['candidate_created_at'] = date('Y-m-d H:i:s');
            $data['candidate_updated_at'] = date('Y-m-d H:i:s');
            $data['candidate_created_at_timestamp'] = time();
            $data['candidate_updated_at_timestamp'] = time();
        } else {
            $data['candidate_created_at'] = $this->candidate_created_at;
            //could be `new Expression('NOW()')` on update 
            $data['candidate_updated_at'] = is_string($this->candidate_updated_at) ? $this->candidate_updated_at : date('Y-m-d H:i:s');
            $data['candidate_created_at_timestamp'] = strtotime($this->candidate_created_at);
            $data['candidate_updated_at_timestamp'] = strtotime($data['candidate_updated_at']);
        }

        //candidate_experience

        $data['candidateExperiences'] = [];

        foreach ($this->getCandidateExperiences()->all() as $experience) {
            $data['candidateExperiences'][] = [
                'experience' => $experience->experience
            ];
        }

        //candidate_skill

        $data['candidateSkills'] = [];

        foreach ($this->getCandidateSkills()->select('skill')->all() as $candidateSkill) {
            $data['candidateSkills'][] = [
                'skill' => $candidateSkill->skill
            ];
        }

        return $data;
    }

    /**
     * Synch with algolia
     * @return type
     */
    public static function synchWithAlgolia() {
        //delete all objects

        Yii::$app->algolia->clearObjects(Yii::$app->params['algolia_candidate_index']);

        //call api in batch

        $query = self::find();
        /* ->joinWith([
          'city',
          //'country',
          'nationality',
          'candidateEducations',
          'candidateSkills',
          'candidateLanguages',
          'candidateExperiences',
          'candidateConclusions'
          ]); */

        $total = $query->count();

        //send 100 in each request 

        Console::startProgress(0, $total);

        $n = 0;

        foreach ($query->batch(100) as $candidates) {

            $data = [];

            foreach ($candidates as $candidate) {
                $algoliaData = $candidate->prepareAlgoliaData();

                if ($algoliaData)
                    $data[] = $algoliaData;
            }

            if ($data)
                Yii::$app->algolia->updates(Yii::$app->params['algolia_candidate_index'], $data);

            $n += sizeof($data);

            Console::updateProgress($n, $total);
        }

        return $total;
    }
}
