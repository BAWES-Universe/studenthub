<?php

namespace common\models;


use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;


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
 * @property string $candidate_video
 * @property string $candidate_video_job_id
 * @property string $candidate_video_processed
 * @property string $candidate_email
 * @property string $candidate_new_email
 * @property string $candidate_email_verification
 * @property string $candidate_limit_email
 * @property string $candidate_phone
 * @property string $candidate_address_line1
 * @property string $candidate_area_uuid
 * @property number $candidate_latitude
 * @property number $candidate_longitude
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
 * @property string $candidate_job_search_status
 * @property integer $candidate_committed
 * @property string $candidate_preferred_time
 * @property integer $candidate_status
 * @property integer $approved
 * @property integer $candidate_mom_kuwaiti
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
 * @property Note[] $notes
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

    const ACTIVELY_LOOKING_FOR_JOB = 1;
    const NOT_LOOKING_FOR_JOB = 0;

    const COMMITTED = 1;
    const NOT_COMMITTED = 0;

    //Gender values for `gender`
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;

    public $pendingProfile = [];
    
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
            //'candidate_hourly_rate',
            [['university_id', 'country_id', 'candidate_email', 'candidate_phone', 'candidate_birth_date', 'candidate_civil_id', 'candidate_civil_expiry_date', 'candidate_civil_photo_front', 'candidate_civil_photo_back', 'candidate_personal_photo'], 'required'],
            [['candidate_name','candidate_name_ar'], 'trim'],
            [['candidate_password_hash'], 'required'],
            [['store_id', 'candidate_status', 'candidate_email_verification', 'approved', 'bank_id', 'candidate_driving_license','candidate_mom_kuwaiti'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_password_hash', 'candidate_password_reset_token', 'candidate_personal_photo', 'candidate_video', 'candidate_video_job_id'], 'string', 'max' => 255],
            [['candidate_iban', 'candidate_address_line1'], 'string', 'max' => 70],
            [['bank_account_name'], 'string', 'max' => 35],
            [['candidate_auth_key'], 'string', 'max' => 32],
            ['candidate_address_line1', 'default', 'value' => 'Kuwait'],
            [['candidate_uid'], 'string', 'max' => 20],
            [['candidate_phone'], 'unique'],
            ['candidate_video_processed', 'boolean'],
            [['candidate_email', 'candidate_new_email'], 'email'],
            //['approved', 'default', 'value'=> false],
            [['candidate_new_email', 'candidate_email'], 'validateEmail'],
            [['candidate_new_email'], 'validateNewEmail'],
            ['candidate_limit_email', 'safe'],
            ['candidate_language_pref', 'in', 'range' => ['en', 'ar']],
            [['candidate_civil_id'], 'unique'],
            ['candidate_pending_profile', 'string'],
            [
                ['candidate_civil_id'],
                'number',
                'numberPattern' => '/^\d{12}$/',
                'message' => Yii::t('app', "Civil id must be 12 digit number")
            ],[
                ['candidate_phone'],
                'number',
                'numberPattern' => '/^\d{8}$/',
                'message' => Yii::t('app', "Phone must be 8 digit number")
            ],
            [['bank_account_name', 'candidate_iban'], 'trim'],
            [['bank_account_name', 'candidate_iban'],
                'match',
                'pattern' => '/^[0-9a-zA-Z\s]+$/',
                'message' => Yii::t('app', "Special characters not allowed")
            ],
            [
                ['bank_account_name', 'candidate_name', 'candidate_name_ar'], 'validateFullName'
            ],
            ['candidate_iban', 'validateIban'],
            ['candidate_hourly_rate', 'validateHourlyRate'],
            [['candidate_civil_expiry_date'], 'validateCivilExpiry'],
            [['candidate_password_reset_token'], 'unique'],
            ['candidate_status', 'default', 'value' => self::STATUS_PENDING],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::className(), 'targetAttribute' => ['country_id' => 'country_id']],

            [['university_id'], 'exist', 'skipOnError' => true, 'targetClass' => University::className(), 'targetAttribute' => ['university_id' => 'university_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::className(), 'targetAttribute' => ['bank_id' => 'bank_id']],

            ['candidate_gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER]],

            ['candidate_job_search_status', 'in', 'range' => [self::NOT_LOOKING_FOR_JOB, self::ACTIVELY_LOOKING_FOR_JOB]],

            ['candidate_committed', 'in', 'range' => [self::COMMITTED, self::NOT_COMMITTED]],

            [['candidate_objective', 'candidate_preferred_time'], 'string', 'max' => 100],

            [['candidate_latitude', 'candidate_longitude'], 'number'],

            [['candidate_area_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Area::className(), 'targetAttribute' => ['candidate_area_uuid' => 'area_uuid']],

            /**
             *  Amazon S3 Temporary Bucket, validate that uploaded files exist if their values have been changed.
             */
            [
                ['candidate_personal_photo'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a personal photo for the candidate"),
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'on' => 'tmpProfilePhoto',
                'when' => function($model, $attribute) {
                    return $model->{$attribute} !== $model->getOldAttribute($attribute);
                }
            ],
                    
            [
                ['candidate_video'],
                '\common\components\S3FileExistValidator',
                'filePath' => '',
                'message' => Yii::t('candidate',"Please upload a video for the candidate"),
                'maxDuration' => '30',//in seconds
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'on' => 'tmpVideo',
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

        $scenarios['updateJobSearchStatus'] = ['candidate_job_search_status'];

        $scenarios['updateCommitted'] = ['candidate_committed'];

        $scenarios['updateEmail'] = ['candidate_email', 'candidate_new_email'];

        $scenarios['updateCandidateEmail'] = ['candidate_email'];

        $scenarios['changeProfilePhoto'] = ['profile_photo'];
        
        $scenarios['changeVideo'] = ['candidate_video', 'candidate_video_job_id', 'candidate_video_processed'];

        $scenarios['tmpVideo'] = ['candidate_video'];

        $scenarios['tmpProfilePhoto'] = ['profile_photo'];

        $scenarios['updateCivilPhotoBack'] = ['candidate_civil_photo_back'];
        
        $scenarios['updateCivilPhotoFront'] = ['candidate_civil_photo_front'];
        
        $scenarios['updateNationality'] = ['country_id'];

        $scenarios['updateDrivingLicense'] = ['candidate_driving_license'];

        $scenarios['updateKuwaitiNational'] = ['candidate_mom_kuwaiti'];

        $scenarios['updateObjective'] = ['candidate_objective'];

        $scenarios['updateGender'] = ['candidate_gender'];

        $scenarios['updateUniversity'] = ['university_id'];

        $scenarios['updateResume'] = ['candidate_resume'];

        $scenarios['updateCivilExpiryDate'] = ['candidate_civil_expiry_date'];

        $scenarios['updateCivilExpiryDateAndCivilID'] = ['candidate_civil_expiry_date', 'candidate_civil_id'];

        $scenarios['updateBirthDate'] = ['candidate_birth_date'];

        $scenarios['changePassword'] = ['candidate_email_verification', 'candidate_password_hash', 'candidate_password_reset_token'];

        $scenarios['signup'] = ['candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_phone', 'candidate_password_hash', 'candidate_language_pref'];

        $scenarios['updateBankDetail'] = ['bank_account_name', 'candidate_iban'];

        $scenarios['candidatePhone'] = ['candidate_phone'];

        $scenarios['candidatePreferredTime'] = ['candidate_preferred_time'];

        $scenarios['statusChange'] = ['approved'];

        $scenarios['updateHourRate'] = ['candidate_hourly_rate'];

        $scenarios['updateLocation'] = ['candidate_latitude', 'candidate_longitude', 'candidate_area_uuid'];

        $scenarios['updatePendingProfile'] = ['candidate_pending_profile'];

        return $scenarios;
    }

    /**
     * Validate name field contain full name
     */
    public function validateFullName($attribute, $params, $validator) {

        $message = '';

        if($attribute == 'candidate_name') {
            $message = Yii::t('app', 'Please specify your full name');
        } else if($attribute == 'candidate_name_ar') {
            $message = Yii::t('app', 'Please specify your full arabic name');
        } else {
            $message = Yii::t('app', 'Please specify your full beneficiary name');
        }

        if(sizeof(explode (' ', $this->$attribute)) == 1) {
            $this->addError('candidate_name', Yii::t('app', $message));
        }
    }

    /**
     * new email can not be same as old
     * @param $attribute
     */
    public function validateNewEmail($attribute) {

        if ($this->candidate_new_email == $this->candidate_email) {
            $this->addError('candidate_new_email', Yii::t('app', 'Email already registered'));
        }
    }

    /**
     * Validate email in new_email field
     */
    public function validateEmail($attribute) {

        $query = self::find()
            ->andWhere([
                'or',
                ['candidate_new_email' => $this->$attribute],
                ['candidate_email' => $this->$attribute]
            ]);

        if($this->candidate_id) {
            $query->andWhere(['!=', 'candidate_id', $this->candidate_id]);
        }

        if ($query->exists()) {
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
            'candidate_video' => Yii::t('candidate', 'Video'),
            'candidate_video_job_id' => Yii::t('candidate', 'Video Job ID'),
            'candidate_video_processed' => Yii::t('candidate', 'Video Processed?'),
            'candidate_email' => Yii::t('candidate','Email'),
            'candidate_new_email' => Yii::t('candidate','New Email'),
            'candidate_email_verification' => Yii::t('candidate','Email Verification'),
            'candidate_limit_email' => Yii::t('candidate','Limit Email'),
            'candidate_phone' => Yii::t('candidate','Phone'),
            'candidate_address_line1' => Yii::t('candidate','Candidate Address'),
            'candidate_latitude' => Yii::t('app', 'Latitude'),
            'candidate_longitude' => Yii::t('app', 'Longitude'),
            'candidate_area_uuid' => Yii::t('app', 'Area'),
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
            'candidate_job_search_status' => Yii::t('candidate', 'Job search status'),
            'candidate_committed' => Yii::t('candidate', 'Committed'),
            'candidate_preferred_time' => Yii::t('candidate', 'Preferred time'),
            'candidate_status' => Yii::t('candidate','Status'),
            'candidate_created_at' => Yii::t('candidate','Created At'),
            'candidate_updated_at' => Yii::t('candidate','Updated At'),
            'employee_id' => Yii::t('candidate','Employee ID'),
            'candidate_mom_kuwaiti' => Yii::t('candidate','Candidate Mom Kuwaiti')
        ];
    }

    public function afterSave($insert, $changedAttributes) {

        parent::afterSave($insert, $changedAttributes);

        if($insert)
        {
            Store::updateAllCounters(['store_total_candidates' => 1], ['store_id' => $this->store_id]);
            Company::updateCandidate($this->store_id, 1);
        }
        else if (array_key_exists('store_id', $changedAttributes))
        {
            Store::updateAllCounters(['store_total_candidates' => 1], ['store_id' => $this->store_id]);
            Store::updateAllCounters(['store_total_candidates' => -1], ['store_id' => $changedAttributes['store_id']]);
            Company::updateCandidate($this->store_id, 1);
            Company::updateCandidate($changedAttributes['store_id'], -1);
        }
        /*else if (array_key_exists('candidate_hourly_rate', $changedAttributes))
        {
            //recalculate transfer total

            $transferCandidatesQuery = TransferCandidate::find()
                ->andWhere ([
                    'paid' => 0,
                    'candidate_id' => $this->candidate_id
                ])
                ->select('transfer_id');

            $transfers = Transfer::find()
                ->andWhere (['transfer_status' => Transfer::STATUS_INITIATED])
                ->andWhere (['in', 'transfer_id', $transferCandidatesQuery])
                ->all();

            $transaction = Yii::$app->db->beginTransaction ();

            foreach($transfers as $transfer) {

                $total = 0;

                foreach ($transfer->transferCandidates as $transferCandidate)
                {
                    if($transferCandidate->candidate_id == $this->candidate_id)
                    {
                        $transferCandidate->candidate_hourly_rate = $this->candidate_hourly_rate;
                        $transferCandidate->candidate_total = $transferCandidate->bonus - $transferCandidate->bonus_commission + ($transferCandidate->hours * $transferCandidate->candidate_hourly_rate) + $transferCandidate->transfer_cost;
                        if(!$transferCandidate->save()) {
                            $transaction->rollBack ();
                            Yii::error ($transferCandidate->getErrors ());
                            throw new \yii\web\BadRequestHttpException('Error updating hourly rate for transfer candidate #' . $transferCandidate->tc_id);
                        }
                    }

                    if ((int)$transferCandidate['hours'] > 0 || $transferCandidate['bonus'] > 0)
                    {
                        //total amount we will pay to bank
                        $total += $transferCandidate['bonus'] - $transferCandidate['bonus_commission']
                            + ($transferCandidate['hours'] * $transferCandidate->candidate_hourly_rate)
                            + $transferCandidate['transfer_cost'];
                    }
                }

                $transfer->total = $total;

                if(!$transfer->save()) {
                    $transaction->rollBack ();
                    Yii::error ($transfer->getErrors ());
                    throw new \yii\web\BadRequestHttpException('Error updating total for transfer #' . $transfer->transfer_id);
                }
            }

            $transaction->commit ();
        }*/
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

        if(!$insert && array_key_exists('candidate_password_hash', $changedAttributes)) {
            $this->sendPasswordUpdatedEmail();
        }

        if (
            //$this->candidate_status == self::STATUS_ACTIVE &&
            $this->candidate_job_search_status === self::ACTIVELY_LOOKING_FOR_JOB &&
            //$this->approved &&
            !in_array(
                $this->scenario, [
                    'updateLanguagePref', //as not saving language preference in algolia
                    'signup', //as will have incomplete profile
                    'updateEmail',//as not saving email in algolia
                    'updatePendingProfile'
                ]
            )
        ) {
            return $this->updateAlgoliaIndex($insert);
        }

        //on soft delete remove or job status updated to not looking for job

        if (
            (
                isset($changedAttributes['candidate_job_search_status']) &&
                $this->candidate_job_search_status === self::NOT_LOOKING_FOR_JOB
            ) ||
            (
                isset($changedAttributes['deleted']) &&
                $this->deleted
            )
        ) {
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

        $fields['isProfileCompleted'] = function($model) {
            return !$model->candidate_pending_profile ||
                strlen ($model->candidate_pending_profile) == 0;
            //return $model->isProfileCompleted();
        };

        $fields['pendingField'] = function($model) {
            return $model->candidate_pending_profile && strlen ($model->candidate_pending_profile) > 0 ?
                explode (',', $model->candidate_pending_profile): null;
            //return ($model->pendingProfile) ? array_keys($model->pendingProfile) : null;
        };

        unset(
            $fields['deleted'],
            $fields['candidate_uid'],
            $fields['candidate_password_hash'],
            $fields['candidate_password_reset_token']
        );

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
            'nationality',
            'country',
            'area',
            'bank',
            'candidateSkills',
            'candidateExperiences',
            'candidateIdCard',
            'notes',
            'workHistory',
            'acceptanceRatio',
            'rejectionRatio'
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

        // Move uploaded files to permanent bucket // as we are only going to use cloudinary
//        $this->_moveTemporaryFilesToPermanentBucket();

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

        //update profile status

        $this->isInCompleteProfile();

        $this->candidate_pending_profile = implode(',', array_keys($this->pendingProfile));

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
    public function getNationality($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id'])
            ->via('area');
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
    public function getArea($modelClass = "\common\models\Area")
    {
        return $this->hasOne($modelClass::className(), ['area_uuid' => 'candidate_area_uuid']);
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
    public function getTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id'])
            ->via('transferCandidate');
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
    public function signup($byStaff = false)
    {
        $this->setPassword($this->candidate_password_hash);
        $this->generateAuthKey();

        if(!$this->save()) {
            return false;
        }

        if($byStaff) {
            
            $this->sendPasswordResetEmail();

            Yii::info("[New Student Account Created By ".Yii::$app->user->identity->staff_name . "] Name: ".$this->candidate_name. ", Phone: ".$this->candidate_phone.", Email: ".$this->candidate_email, __METHOD__);

        } else {
            
            $this->sendVerificationEmail();
        
            Yii::info("[New Student Registration] ".$this->candidate_name. " has signed up. Phone: ".$this->candidate_phone.", Email: ".$this->candidate_email, __METHOD__);
        }

        return $this;
    }

    /**
     * notify candidate for password update
     */
    public function sendPasswordUpdatedEmail()
    {
        Yii::$app->mailer->compose("candidate/password-updated-html",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->candidate_email,
                "name" => $this->candidate_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject('Your password reset was a success')
            ->send();
    }

    /**
     * Send link in email to reset password
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public function sendPasswordResetEmail()
    {
        $this->generatePasswordResetToken();
        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['candidateAppUrl'] . 'update-password/' . $this->candidate_password_reset_token;
        $name = explode(' ',$this->candidate_name);
        return Yii::$app->mailer->compose("candidate/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->candidate_email,
                "name" => (isset($name[0])) ? $name[0] : $this->candidate_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject('Reset your StudentHub password')
            ->send();
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
        $token = CandidateToken::find()->andWhere(['token_value' => $token])->with('candidate')->one();
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
        return static::findOne(['candidate_email' => $email, 'deleted' => 0]);
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
            'deleted' => 0
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
            ->andWhere('MONTH(candidate_birth_date) = MONTH(NOW()) AND DAY(candidate_birth_date) = DAY(NOW())')
            ->andWhere(['candidate_email_verification' => 1])
            ->all();

        if(!$candidates)
            return null;

        $allStaff = Staff::findAll(['deleted'=>'0']);

        $allStaffEmails = ArrayHelper::map($allStaff,'staff_email','staff_name');

        foreach($candidates as $candidate)
        {
            Yii::$app->mailer->compose("birthday",
                [
                    "candidate" => $candidate,
                    "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                    "birthday_img" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/birthday.gif', 'https'),
                ])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                ->setTo($candidate->candidate_email)
                ->setBcc($allStaffEmails)
                ->setSubject('Happy Birthday from StudentHub')
                ->send();
        }

        return count($candidates);
    }

    /**
     * @return null
     */
    public static function civilIdExpire()
    {
        $candidates = Candidate::find()
            ->andWhere('YEAR(candidate_civil_expiry_date) = YEAR(NOW()) AND MONTH(candidate_civil_expiry_date) = MONTH(NOW()) AND DAY(candidate_civil_expiry_date) = DAY(NOW())')
            ->all();

        if(!$candidates)
            return null;

        foreach($candidates as $candidate) {

            $f_name = $candidate->candidate_name? $candidate->candidate_name: $candidate->candidate_name_ar;

            $name = explode(' ', $f_name)[0];

            $url = '';

            $isProfileCompleted = $candidate->isProfileCompleted();

            if (!$isProfileCompleted) {
                $url = Yii::$app->params['candidateAppUrl'];
            } else {
                $url = Yii::$app->params['candidateAppUrl'] . 'view/profile';
            }

            Yii::$app->mailer->compose("civil-expired",
                [
                    'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                    'url' => $url,
                    'name' => $name
                ])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                ->setTo($candidate->candidate_email)
                ->setSubject('Please update your civil id')
                ->send();
        }
    }

    /**
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;

        //remove unique fields, so can create new account with same details

        $this->candidate_civil_id = 'deleted at ' . time() . '-' . $this->candidate_civil_id;
        $this->candidate_password_reset_token = null;

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
            ->orderBy('{{%transfer_candidate}}.tc_id DESC')
            ->all();
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
        $token = CandidateToken::find()
            ->andWhere(['token_value' => $token])
            ->with('candidate')
            ->one();

        if ($token && $token->candidate && !$token->candidate->deleted) {
            return $token->candidate;
        }
    }

    /**
     * Verifies the candidate email
     */
    public static function verifyEmail($email, $code) {
       
        $candidate = Candidate::find()
            ->andWhere([
                    'OR',
                    ['candidate_new_email' => $email],
                    ['candidate_email' => $email]
            ])
            ->one();

        if(!$candidate) {
            return [
                'success' => false,
                'message' =>Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
            ];
        }

        if ($candidate->candidate_auth_key && $code && $candidate->candidate_auth_key == $code) { //to cope with sql case insensitivity
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
            return [
                'success' => true,
                'data' => $candidate
            ];
        } else {
            return [
                'success' => false,
                'message' =>Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
            ];
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

        } catch (\Exception $e) {

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

        } catch (\Exception $e) {

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
    public function updateVideo() {

        $this->scenario = 'tmpVideo';

        if(!$this->validate()) {
            return false;
        }

        //add video upload log

        $videoLog = new CandidateVideoLog;
        $videoLog->candidate_id = $this->candidate_id;
        $videoLog->ip_address = Yii::$app->getRequest()->getUserIP();

        if(!$videoLog->save()) {

            $this->addError('candidate_video_log', $videoLog->errors);

            return false;
        }

        $output = Yii::$app->security->generateRandomString();

        $source = Yii::$app->temporaryBucketResourceManager->bucket . '/' . $this->candidate_video;

        try {

            $extension = pathinfo($this->candidate_video, PATHINFO_EXTENSION);

            if($extension != 'mp4') {

                $response = Yii::$app->mediaConvert->processVideo ($source, $output);

                $this->candidate_video_job_id = $response['Job']['Id'];

                $this->candidate_video_processed = false;

            } else {

                $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

                $file_s3_path = 'candidate-video/' . $output . '_1.mp4';

                Yii::$app->resourceManager->copy($this->candidate_video, $file_s3_path, $sourceBucket);

                $this->candidate_video_processed = true;

                //log to slack

                $name = $this->candidate_name? $this->candidate_name: $this->candidate_name_ar;

                $url =  Yii::$app->resourceManager->getUrl($file_s3_path);

                Yii::info("[Video recording uploaded by ".$name."] Watch it on " . $url, __METHOD__);

            }

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_video', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_video', Yii::t('app', 'Video not available to save.'));

            return false;
        }

        //notify admin for abuse

        $totalUploads = CandidateVideoLog::find()
            ->andWhere([
                'candidate_id' => $this->candidate_id,
                'ip_address' => Yii::$app->getRequest()->getUserIP()
            ])
            ->andWhere(new \yii\db\Expression("created_at >= DATE_SUB(NOW(),INTERVAL 1 MONTH)"))//last 1 month
            ->count();

        if($totalUploads > 3)
        {
            $candidate = $this->candidate_name? $this->candidate_name: $this->candidate_name_ar;

            Yii::warning("[Candidate video uploads] ".$totalUploads." video uploaded by " . $candidate ." in last 1 month", 'candidate');
        }

        //generate video thumbnail

        $tmpVideo = Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_video);

        $this->_generateVideoThumbnail($tmpVideo, $output);

        //save video
        
        $this->candidate_video = $output . '_1';//first converted file

        $this->scenario = 'changeVideo';

        return $this->save();
    }

    /**
     * generate video thumbnail by video url
     * @param $source
     * @param $output
     */
    public function _generateVideoThumbnail($source, $output)
    {
        $fileName = $output . '_1.jpg';

        // Create temporary file to store image in
        $tmpFile = sys_get_temp_dir() . '/' . $fileName;
        $tmpHandle = fopen($tmpFile, 'w+');

        $ffmpegPath = exec('which ffmpeg');//'/usr/local/bin/ffmpeg'

        exec($ffmpegPath . ' -y -i "'.$source.'" -ss 00:00:01.000 -vframes 1 ' . $tmpFile . ' 2>&1');

        // Save thumbnail to S3
        Yii::$app->resourceManager->save(
            null, //file upload object
            "candidate-video/" . $fileName, // name
            [], //options
            $tmpFile, // source file
            'image/jpeg'
        );

        fclose($tmpHandle);
        @unlink($tmpFile);
    }

    /**
     * Return file base name
     * @param string $fileName
     * @return string file name without extension
     */
    public function _getBaseName($fileName) {
        $pathInfo = pathinfo('_' . $fileName, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * Update profile photo from temp s3 bucket
     * @return type
     */
    public function updateProfilePhoto() {

        //validation for temp S3 bucket file 
        
        $this->scenario = 'tmpProfilePhoto';

        if(!$this->validate()) {
            return false;
        }
        
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
     * delete old video
     * @return boolean
     */
    public function deleteVideo() {

        try {

            //video

            Yii::$app->resourceManager->delete("candidate-video/" . $this->candidate_video . '.mp4');

            //video thumbnail

            Yii::$app->resourceManager->delete("candidate-video/" . $this->candidate_video . '.jpg');

            return true;
        }
        catch (\Aws\S3\Exception\S3Exception $e)
        {
            Yii::error($e->getMessage(), 'file');

            $this->addError('candidate_video', Yii::t('app', 'Please try again.'));

            return false;
        }
        catch (\Exception $e)
        {
            Yii::error($e->getMessage(), 'file');

            $this->addError('candidate_video', Yii::t('app', 'Video not available to delete.'));

            return false;
        }
    }

    /**
     * delete old profile photo from cloudinary
     * @return boolean
     */
    public function deleteProfilePhotoFromCloudinary() {

        try {

            $path = (YII_ENV == 'prod') ? "candidate-photo/" : "dev/candidate-photo/";
            
            Yii::$app->cloudinaryManager->delete($path . $this->candidate_personal_photo);

            return true;

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            //$this->addError('profile_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

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
            
            $path = (YII_ENV == 'prod') ?  "candidate-photo/" : "dev/candidate-photo/";

            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' => $path . $filename,
                    "eager" => [
                        [
                            //id card thumbnail
                            "width" => 319, "height" => 319, "crop" => "thumb", "gravity" => "face",
                        ],
                        [
                            //profile pic in apps
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->candidate_personal_photo = basename($result['url']);
                return true;
            }

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete file from aws
     * @param string $type
     * @param string $side
     * @return false
     */
    public function deleteFile($type = 'resume', $side = 'front') {

        try {
            if (isset($this->oldPrimaryKey)) {
                
                $file = null; 
                
                if ($type == 'resume' && isset($this->oldAttributes['candidate_resume'])) {
                    $file = "candidate-resume/" . $this->oldAttributes['candidate_resume'];
                } else if ($type == 'civil-id' && $side == 'front' && isset($this->oldAttributes['candidate_civil_photo_front'])) {
                    $file = "candidate-civil-id/" . $this->oldAttributes['candidate_civil_photo_front'];
                } else if ($type == 'civil-id' && $side == 'back' && isset($this->oldAttributes['candidate_civil_photo_back'])) {
                    $file = "candidate-civil-id/" . $this->oldAttributes['candidate_civil_photo_back'];
                }
                
                if ($file) {
                    Yii::$app->resourceManager->delete($file);
                }
            }

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'file not available to delete.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'file not available to delete.'));

            return false;
        }
    }

    /**
     * @return bool
     */
    public function updateCivilId($side = 'front') {

        $idSide = ($side == 'front') ? 'candidate_civil_photo_front' : 'candidate_civil_photo_back';

        if (!empty($this->oldAttributes[$idSide])) {
            $this->deleteFile('civil-id', $side);
        }

        $fileName = $this->$idSide;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $targetPath = "photos/" . $fileName;

        // Copy using S3ResourceManager Component
        
        try {

            return Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError($idSide, Yii::t('app', 'file not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError($idSide, Yii::t('app', 'file not available to save.'));

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

        if ($this->candidate_new_email) {
            $email = $this->candidate_new_email;
        } else {
            $email = $this->candidate_email;
        }

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
     * merge source account to target
     * @param $from
     * @param $to
     */
    public static function merge($from, $to) {

        //move

        TransferCandidate::updateAll (['candidate_id' => $to], ['candidate_id' => $from]);

        CandidateWorkHistory::updateAll(['candidate_id' => $to], ['candidate_id' => $from]);

        Note::updateAll(['candidate_id' => $to], ['candidate_id' => $from]);
        Suggestion::updateAll(['candidate_id' => $to], ['candidate_id' => $from]);

        //delete source candidate

//        Candidate::updateAll(['deleted' => 1], ['candidate_id' => $from]);

        CandidateExperience::updateAll(['deleted' => 1], ['candidate_id' => $from]);

        CandidateIdCard::updateAll(['deleted' => 1], ['candidate_id' => $from]);

        CandidateSkill::updateAll(['deleted' => 1], ['candidate_id' => $from]);

        //logout from source devices for old account

        CandidateToken::deleteAll(['candidate_id' => $from]);

        $candidate = Candidate::findOne(['candidate_id'=>$from]);
        $candidate->softDelete();
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
            $this->pendingProfile['uid'] = true;
        }

        if (!$this->university_id) {
            $this->pendingProfile['university'] = true;
        }

        if (!$this->country_id) {
            $this->pendingProfile['country'] = true;
        }

        if (!$this->candidate_name) {
            $this->pendingProfile['name'] = true;
        }

        if (!$this->candidate_name_ar) {
            $this->pendingProfile['Name Arabic'] = true;
        }

        if (!in_array($this->candidate_gender, [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER])) {
            $this->pendingProfile['gender'] = true;
        }

        if (!$this->candidate_objective) {
            $this->pendingProfile['objective'] = true;
        }

        if (!$this->candidate_personal_photo) {
            $this->pendingProfile['personal photo'] = true;
        }

        if (!$this->candidate_email) {
            $this->pendingProfile['email'] = true;
        }

        if (!$this->candidate_phone) {
            $this->pendingProfile['phone'] = true;
        }

        if (!$this->candidate_birth_date) {
            $this->pendingProfile['birth date'] = true;
        }

        if (!$this->candidate_civil_id) {
            $this->pendingProfile['civil id'] = true;
        }

        if (!$this->candidate_civil_expiry_date) {
            $this->pendingProfile['civil expiry date'] = true;
        }

        if (!$this->candidate_civil_photo_front) {
            $this->pendingProfile['civil photo front'] = true;
        }

        if (!$this->candidate_civil_photo_back) {
            $this->pendingProfile['civil photo back'] = true;
        }

        if (!$this->candidate_driving_license) {
            $this->pendingProfile['driving license'] = false;
        }

        if (!$this->candidate_latitude && !$this->candidate_longitude && !$this->candidate_area_uuid) {
            $this->pendingProfile['location'] = false;
        }

        if (
            $this->area && $this->nationality &&
            $this->area->country &&
            $this->area->country->country_nationality_name_en == 'Kuwaiti' &&
            $this->nationality->country_nationality_name_en != 'Kuwaiti' &&
            !$this->candidate_mom_kuwaiti
        ) {
            #https://www.pivotaltracker.com/story/show/175607833
            $this->pendingProfile['candidate_mom_kuwaiti'] = false;
        }

//        if (!$this->candidate_resume) {
//            return 'resume';
//        }

//        if (!$this->candidate_hourly_rate) {
//            $this->pendingProfile['hourly rate'] = false;
//        }

        if ($this->getCandidateExperiences()->count() == 0) {
            $this->pendingProfile['experience'] = false;
        }

        if ($this->getCandidateSkills()->count() == 0) {
            $this->pendingProfile['skill'] = false;
        }

        if (count($this->pendingProfile) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks is candidate have incomplete profile
     * creating seperatly so that we can avoid pending field
     * candidate_mom_kuwaiti check as its required but not
     * mandatory for algolia upload
     * @return void|string
     */
    public function isInCompleteProfileForAlgolia() {

        if (!$this->candidate_uid) {
            $this->pendingProfile['uid'] = true;
        }

        if (!$this->university_id) {
            $this->pendingProfile['university'] = true;
        }

        if (!$this->country_id) {
            $this->pendingProfile['country'] = true;
        }

        if (!$this->candidate_name) {
            $this->pendingProfile['name'] = true;
        }

        if (!$this->candidate_name_ar) {
            $this->pendingProfile['Name Arabic'] = true;
        }

        if (!in_array($this->candidate_gender, [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER])) {
            $this->pendingProfile['gender'] = true;
        }

        if (!$this->candidate_objective) {
            $this->pendingProfile['objective'] = true;
        }

        if (!$this->candidate_personal_photo) {
            $this->pendingProfile['personal photo'] = true;
        }

        if (!$this->candidate_email) {
            $this->pendingProfile['email'] = true;
        }

        if (!$this->candidate_phone) {
            $this->pendingProfile['phone'] = true;
        }

        if (!$this->candidate_birth_date) {
            $this->pendingProfile['birth date'] = true;
        }

        if (!$this->candidate_civil_id) {
            $this->pendingProfile['civil id'] = true;
        }

        if (!$this->candidate_civil_expiry_date) {
            $this->pendingProfile['civil expiry date'] = true;
        }

        if (!$this->candidate_civil_photo_front) {
            $this->pendingProfile['civil photo front'] = true;
        }

        if (!$this->candidate_civil_photo_back) {
            $this->pendingProfile['civil photo back'] = true;
        }

        if (!$this->candidate_driving_license) {
            $this->pendingProfile['driving license'] = false;
        }

        if (!$this->candidate_latitude && !$this->candidate_longitude && !$this->candidate_area_uuid) {
            $this->pendingProfile['location'] = false;
        }

        if ($this->getCandidateExperiences()->count() == 0) {
            $this->pendingProfile['experience'] = false;
        }

        if ($this->getCandidateSkills()->count() == 0) {
            $this->pendingProfile['skill'] = false;
        }

        if (count($this->pendingProfile) > 0) {
            return true;
        } else {
            return false;
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
            !$this->candidate_email_verification
           // $this->candidate_status != self::STATUS_ACTIVE
        ) {
            //delete
            return false;
        }

        $isInCompleteProfile = $this->isInCompleteProfileForAlgolia();

        /**
         * delete from algolia when profile incomplete
         */
        if ($isInCompleteProfile) {
            Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $this->candidate_id);
            return false;
        }

        $data = [
            'objectID' => $this->candidate_id,
            'candidate_id' => $this->candidate_id,
            //'bank_account_name' => $this->bank_account_name,
            //'candidate_iban' => $this->candidate_iban,
            'candidate_name' => $this->candidate_name,
            'candidate_name_ar' => $this->candidate_name_ar,
            'candidate_objective' => $this->candidate_objective,
            'candidate_personal_photo' => $this->candidate_personal_photo,
            'candidate_video' => $this->candidate_video,
            'candidate_resume' => $this->candidate_resume,
            'have_video' => $this->candidate_video? 'Yes': 'No',
            'have_resume' => $this->candidate_resume? 'Yes': 'No',
            'candidate_committed' => $this->candidate_committed? 'Yes': 'No',
            'candidate_preferred_time' => $this->candidate_preferred_time,
            'candidate_email' => $this->candidate_email,
            'candidate_phone' => $this->candidate_phone,
            'candidate_birth_date' => $this->candidate_birth_date,
            'candidate_birth_timestamp' => strtotime($this->candidate_birth_date),
            'candidate_driving_license' => $this->candidate_driving_license,
            'candidate_language_pref' => $this->candidate_language_pref,
            'candidate_job_search_status' => $this->candidate_job_search_status,
            'approved' => $this->approved,
            'candidate_mom_kuwaiti' => $this->candidate_mom_kuwaiti,
            'candidate_email_verification' => true,   // using in candidate card
            'isProfileCompleted' => true,  // using in candidate card
        ];

        if($this->university) {

            $university_name = [];

            if($this->university->university_name_en) {
                $university_name[] = $this->university->university_name_en;
            }

            if($this->university->university_name_ar) {
                $university_name[] = $this->university->university_name_ar;
            }

            $data['university'] = [
                'university_id' => $this->university_id,
                'university_name_en' => $this->university->university_name_en,
                'university_name_ar' => $this->university->university_name_ar,
                'university_name' => $university_name
            ];
        }

        if($this->country) {
            $data['country'] = [
                'country_id' => $this->country_id,
                'country_name_en' => $this->nationality->country_name_en,
                'country_name_ar' => $this->nationality->country_name_ar
            ];
        }

        $data['assigned'] = 0;

        if($this->store && $this->store->company) {
            $data['store'] = [
                'store_name' => $this->store->store_name,
                'store_total_candidate' => $this->store->store_total_candidates,
                'company' => [
                    'company_name' => $this->store->company->company_name
                ]
            ];
            $data['assigned'] = 1;
        }

        if($this->bank) {
            $data['bank'] = [
                'bank_id' => $this->bank_id,
                'bank_name' => $this->bank->bank_name
            ];
        }

        //geo location

        if ($this->candidate_latitude && $this->candidate_longitude) {
            $data["_geoloc"] = [
                "lat" => (float) $this->candidate_latitude,
                "lng" => (float) $this->candidate_longitude,
            ];
        } elseif ($this->area && $this->area->area_latitude && $this->area->area_longitude) {
            $data["_geoloc"] = [
                "lat" => (float) $this->area->area_latitude,
                "lng" => (float) $this->area->area_longitude
            ];
        } else {
            $data["_geoloc"] = [
                "lat" => 0,
                "lng" => 0
            ];
        }

        if ($this->area && $this->area->country) {

            $data['currentLocations']['en'] = [
                $this->area->country->country_name_en,
                $this->area->area_name_en . ', ' . $this->area->country->country_name_en,
            ];

            $data['currentLocations']['ar'] = [
                $this->area->country->country_name_ar,
                $this->area->area_name_ar . ', ' . $this->area->country->country_name_ar
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

    /**
     * return number of unpaid candidates, missing bank details
     * @return bool|int|string|null
     */
    public static function neededBankInfo()
    {
        return TransferCandidate::find()
            ->joinWith('candidate')
            ->filterUnpaid()
            ->andWhere(['{{%candidate}}.deleted'=>0])
            //->andWhere('{{%candidate}}.store_id > 0')
            //->groupBy('{{%transfer_candidate}}.candidate_id')
            ->select('{{%transfer_candidate}}.candidate_id')
            ->distinct()
            ->andWhere('{{%candidate}}.bank_id IS NULL')
            ->count();
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * notify candidate for password update
     */
    public function commitmentWarningEmail()
    {
        $f_name = $this->candidate_name ? $this->candidate_name : $this->candidate_name_ar;

        $name = explode(' ', $f_name)[0];

        Yii::$app->mailer->compose("candidate/commitment-warning",
            [
                "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                "name" => $name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject("We'll stop recommending your profile to companies")
            ->send();
    }

    /**
     * notify candidate kuwaiti mom Nationality update
     */
    public static function kuwaitiNationalityEmail()
    {
        $total = 0;
        $candidates = Candidate::find()
            ->verifiedProfile()
            ->candidateMomKuwaitiFieldIsNull()
            ->all();

        if (!$candidates)
            return null;

        foreach ($candidates as $candidate) {

            if (
                $candidate->area && $candidate->nationality &&
                $candidate->area->country &&
                $candidate->area->country->country_nationality_name_en == 'Kuwaiti' &&
                $candidate->nationality->country_nationality_name_en != 'Kuwaiti' &&
                !$candidate->candidate_mom_kuwaiti
            ) {

                $f_name = $candidate->candidate_name ? $candidate->candidate_name : $candidate->candidate_name_ar;

                $name = explode(' ', $f_name)[0];

                $url = '';

                $isProfileCompleted = $candidate->isProfileCompleted();

                if (!$isProfileCompleted) {
                    $url = Yii::$app->params['candidateAppUrl'];
                } else {
                    $url = Yii::$app->params['candidateAppUrl'] . 'view/profile';
                }
                Yii::$app->mailer->compose("candidate/kuwaiti-mom",
                    [
                        "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                        "name" => $name,
                        "url" => $url
                    ])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                    ->setTo($candidate->candidate_email)
                    ->setSubject("Jobs in restaurants, cafes, and cinemas")
                    ->send();
                $total++;
            }
        }
        return $total;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\common\models\Suggestion")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * get user accepted suggestion ratio
     * @return float|int
     */
    public function getAcceptanceRatio() {
        $total = $this->getSuggestion()->count();
        $accepted = $this->getSuggestion()->andWhere(['suggestion_status'=>Suggestion::TYPE_ACCEPTED])->count();

        return ($total && $accepted) ? round(($accepted/$total)  * 100): null;
    }
    /**
     * get user rejected suggestion ratio
     * @return float|int
     */
    public function getRejectionRatio() {
        $total = $this->getSuggestion()->count();
        $rejected = $this->getSuggestion()->andWhere(['suggestion_status'=>Suggestion::TYPE_REJECTED])->count();
        return ($total && $rejected) ? round(($rejected/$total) * 100): null;
    }

    public function getPendingField() {
        return ($this->pendingProfile) ? array_keys($this->pendingProfile) : null;
    }

    public function getIsProfileCompleted() {
        return $this->isInCompleteProfile() ? false : true;
    }
}
