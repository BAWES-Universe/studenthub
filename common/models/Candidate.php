<?php

namespace common\models;


use Detection\MobileDetect;
use Yii;
use yii\db\Exception;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use Segment\Segment;

/**
 * This is the model class for table "candidate".
 *
 * @property integer $candidate_id
 * @property string $utm_uuid
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
 * @property string $candidate_intro
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
 * @property boolean $candidate_civil_need_verification
 * @property string $candidate_driving_license
 * @property string $candidate_resume
 * @property float $candidate_hourly_rate
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property string $candidate_language_pref
 * @property string $candidate_job_search_status
 * @property string $candidate_job_search_updated_at
 * @property integer $candidate_committed
 * @property string $candidate_preferred_time
 * @property integer $candidate_status
 * @property integer $approved
 * @property integer $candidate_mom_kuwaiti
 * @property string $candidate_pending_profile
 * @property number $is_incomplete_profile
 * @property string $profile_url
 * @property string $candidate_created_at
 * @property string $candidate_updated_at
 * @property integer $deleted
 * @property integer $is_duplicate
 * @property string $currency_code
 * @property Bank $bank
 * @property Country $country
 * @property Store $store
 * @property Company $company
 * @property University $university
 * @property CandidateIdCard[] $candidateIdCards
 * @property CandidateToken[] $accessTokens
 * @property TransferCandidate[] $TransferCandidate
 * @property Note[] $notes
 * @property Campaign $campaign
 * @property CandidateEducation[] $candidateEducations
 */
class Candidate extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const MOM_KUWAITI = 1;
    const MOM_NOT_KUWAITI = 2;

    // Candidate Status
    const STATUS_READY = 1;
    const STATUS_PENDING = 0;
    const STATUS_ACTIVE = 10;//default status

    //Email verification values for `candidate_email_verification`
    const EMAIL_VERIFIED = 1;
    const EMAIL_NOT_VERIFIED = 0;

    const ACTIVELY_LOOKING_FOR_JOB = 1;
    const NOT_LOOKING_OPEN_FOR_OFFER = 2;//Not looking but open to offers
    const NOT_LOOKING_FOR_JOB = 0;

    const COMMITTED = 1;
    const NOT_COMMITTED = 0;

    //Gender values for `gender`
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;

    const HAVE_DRIVING_LICENCE = 1;
    const NOT_HAVE_DRIVING_LICENCE = 2;

    /** Permanent S3 prefix for new candidate profile photo uploads. */
    public const PERSONAL_PHOTO_S3_PREFIX = 'candidate-profile-photos/';

    /** Legacy permanent S3 prefix (read/display only; not used for new uploads). */
    public const LEGACY_PERSONAL_PHOTO_S3_PREFIX = 'photos/';

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
            //'candidate_hourly_rate', 'candidate_civil_expiry_date','candidate_civil_id',
            [['candidate_birth_date'], "validateAge"],
            // 'candidate_phone',
            [['university_id', 'country_id', 'candidate_email', 'candidate_birth_date',
                'candidate_personal_photo', 'currency_code'], 'required'],
            [['candidate_civil_photo_front', 'candidate_civil_photo_back'], 'required', 'except' => ['staffUpdate']],
            [['candidate_name','candidate_name_ar'], 'trim'],
            [['candidate_password_hash'], 'required'],
            [['candidate_email_verification'], 'default', 'value' => self::EMAIL_NOT_VERIFIED],
            [['store_id', 'candidate_status', 'candidate_email_verification', 'approved', 'bank_id', 'candidate_driving_license','candidate_mom_kuwaiti'], 'integer'],
            [['candidate_name','candidate_name_ar', 'candidate_email', 'candidate_password_hash', 'candidate_password_reset_token', 'candidate_personal_photo', 'candidate_video', 'candidate_video_job_id'], 'string', 'max' => 255],
            [['candidate_iban', 'candidate_address_line1'], 'string', 'max' => 70],
            [['bank_account_name'], 'string', 'max' => 35],
            [['candidate_auth_key'], 'string', 'max' => 32],

            [['currency_code'], "string", "max" => 3],
            ['candidate_address_line1', 'default', 'value' => 'Kuwait'],
            [['candidate_uid'], 'string', 'max' => 20],
            [['candidate_video_processed', 'is_duplicate'], 'boolean'],
            [['candidate_email', 'candidate_new_email'], 'email'],
            //['approved', 'default', 'value'=> false],
    //candidate_job_search_updated_at
            [['enable_two_step_auth'], 'safe'],

            ['deleted', 'default', 'value'=> 0],

            [['candidate_new_email', 'candidate_email'], 'validateEmail'],
           // [['candidate_new_email'], 'validateNewEmail'],
            [['candidate_limit_email','profile_url'], 'safe'],
            ['candidate_language_pref', 'in', 'range' => ['en', 'ar']],

            //['candidate_phone', 'unique', 'comboNotUnique' => 'Phone no. already exist.', 'targetAttribute' => ['candidate_phone', 'deleted']],

            [
                ['candidate_civil_photo_back', 'candidate_civil_photo_front'],
                'validateCivilID', 'when' => function($model, $attribute) {
                return $model->{$attribute} !== $model->getOldAttribute($attribute);
            }],//, "on" => "updateCivilPhotoBack"

            [['candidate_civil_id'], 'validateCivilIdNumber'],
           /* ['candidate_civil_id', 'unique', 'comboNotUnique' => 'Civil Id already exist.', 'targetAttribute' => [
                'candidate_civil_id', 'deleted']],*/

           /* ['candidate_civil_photo_back', 'validateCivilID', 'when' => function($model, $attribute) {
                return $model->{$attribute} !== $model->getOldAttribute($attribute) &&
                    $this->nationality && $this->nationality->iso == "BH";
            }],//, "on" => "updateCivilPhotoBack"*/

            ['candidate_pending_profile', 'string'],
            [['is_incomplete_profile'], 'boolean'],

            /*[
                ['candidate_civil_id'],
                'number',
                'numberPattern' => '/^\d{12}$/',
                'message' => Yii::t('app', "Civil id must be 12 digit number")
            ],
            [
                ['candidate_phone'],
                'number',
                'numberPattern' => '/^\d{8}$/',
                'message' => Yii::t('app', "Phone must be 8 digit number")
            ],*/
            [['candidate_civil_id'], 'string', 'max' => 255],
            [['candidate_phone'], 'string', 'max' => 20],

            [['bank_account_name', 'candidate_iban'], 'trim'],
            [['bank_account_name', 'candidate_iban'],
                'match',
                'pattern' => '/^[0-9a-zA-Z\s]+$/',
                'message' => Yii::t('app', "Special characters not allowed")
            ],
            [
                ['bank_account_name', 'candidate_name', 'candidate_name_ar'], 'validateFullName'
            ],
            ['candidate_iban', 'validateIban', 'when' => function($model, $attribute) {
                return !$model->currency_code || $model->currency_code == "KWD";
            }],
            ['candidate_hourly_rate', 'validateHourlyRate'],
            [['candidate_civil_expiry_date'], 'validateCivilExpiry'],
            [['candidate_password_reset_token'], 'unique'],
            ['candidate_status', 'default', 'value' => self::STATUS_PENDING],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'country_id']],

            [['university_id'], 'exist', 'skipOnError' => true, 'targetClass' => University::class, 'targetAttribute' => ['university_id' => 'university_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::class, 'targetAttribute' => ['bank_id' => 'bank_id']],

            ['candidate_gender', 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER]],

            ['candidate_job_search_status', 'in', 'range' => [self::NOT_LOOKING_FOR_JOB, self::ACTIVELY_LOOKING_FOR_JOB, self::NOT_LOOKING_OPEN_FOR_OFFER]],

            ['candidate_committed', 'in', 'range' => [self::COMMITTED, self::NOT_COMMITTED]],

            [['candidate_objective', 'candidate_preferred_time'], 'string', 'max' => 100],

            [['candidate_intro'], 'string'],

            [['candidate_latitude', 'candidate_longitude'], 'number'],

            [['candidate_area_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Area::class, 'targetAttribute' => ['candidate_area_uuid' => 'area_uuid']],
            [['utm_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Campaign::class, 'targetAttribute' => ['utm_uuid' => 'utm_uuid']],

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

        $scenarios['deleteCandidate'] = ['deleted', 'is_duplicate'];

        $scenarios['staffUpdate'] = [
            'candidate_preferred_time', 'store_id', 'university_id', 'country_id',
            'bank_account_name', 'candidate_iban', 'candidate_name', 'candidate_name_ar',
            'candidate_personal_photo', 'candidate_email', 'candidate_phone',
            'candidate_civil_id', 'candidate_civil_photo_front', 'candidate_civil_photo_back',
            'currency_code', 'candidate_driving_license', 'candidate_gender',
            'candidate_objective', 'candidate_birth_date', 'candidate_civil_expiry_date',
            'candidate_resume', 'candidate_latitude', 'candidate_longitude',
            'candidate_area_uuid', 'candidate_mom_kuwaiti', 'is_incomplete_profile'
        ];

        $scenarios['updateName'] = ['candidate_name', 'is_incomplete_profile'];

        $scenarios['updateNameAr'] = ['candidate_name_ar', 'is_incomplete_profile'];

        $scenarios['candidate_personal_photo'] = ['candidate_personal_photo', 'is_incomplete_profile'];

        $scenarios['updateCivilId'] = ["candidate_civil_need_verification", 'candidate_civil_id', 'is_incomplete_profile', 'deleted'];

        $scenarios["updateLanguagePref"] = ["candidate_language_pref", 'is_incomplete_profile'];

        $scenarios['updateJobSearchStatus'] = ['candidate_job_search_status', 'is_incomplete_profile', 'candidate_job_search_updated_at'];

        $scenarios['updateCommitted'] = ['candidate_committed', 'is_incomplete_profile'];

        $scenarios['updateEmail'] = ['candidate_email', 'candidate_new_email', 'is_incomplete_profile'];

        $scenarios['verifyEmail'] = ['candidate_email', 'candidate_new_email', 'candidate_email_verification', 'is_incomplete_profile'];

        $scenarios['updateCandidateEmail'] = ['candidate_email', 'is_incomplete_profile'];

        $scenarios['changeProfilePhoto'] = ['profile_photo', 'is_incomplete_profile'];
        
        $scenarios['changeVideo'] = ['candidate_video', 'candidate_video_job_id', 'candidate_video_processed', 'is_incomplete_profile'];

        $scenarios['tmpVideo'] = ['candidate_video', 'is_incomplete_profile'];

        $scenarios['tmpProfilePhoto'] = ['profile_photo', 'is_incomplete_profile'];

        $scenarios['updateCivilPhotoBack'] = ['candidate_civil_photo_back', "candidate_civil_expiry_date",
            "candidate_civil_id", 'is_incomplete_profile'];

        $scenarios['updateCivilPhotoFront'] = ['candidate_civil_photo_front', "candidate_civil_expiry_date",
            "candidate_civil_id", 'is_incomplete_profile'];
        
        $scenarios['updateNationality'] = ['country_id', 'is_incomplete_profile'];

        $scenarios['updateDrivingLicense'] = ['candidate_driving_license', 'is_incomplete_profile'];

        $scenarios['updateKuwaitiNationality'] = ['country_id', 'candidate_mom_kuwaiti', 'is_incomplete_profile'];

        $scenarios['updateKuwaitiNational'] = ['candidate_mom_kuwaiti', 'is_incomplete_profile'];

        $scenarios['updateObjective'] = ['candidate_objective', 'is_incomplete_profile'];

        $scenarios['updateIntro'] = ['candidate_intro', 'is_incomplete_profile'];

        $scenarios['updateGender'] = ['candidate_gender', 'is_incomplete_profile'];

        $scenarios['updateUniversity'] = ['university_id', 'is_incomplete_profile'];

        $scenarios['updateResume'] = ['candidate_resume', 'is_incomplete_profile'];

        $scenarios['updateCivilExpiryDate'] = ["candidate_civil_need_verification", 'candidate_civil_expiry_date', 'is_incomplete_profile'];

        $scenarios['updateCivilExpiryDateAndCivilID'] = [
            "candidate_civil_need_verification", 'candidate_civil_expiry_date', 'candidate_civil_id', 'is_incomplete_profile'];

        $scenarios['updateBirthDate'] = ['candidate_birth_date', 'is_incomplete_profile'];

        $scenarios['changePassword'] = ['candidate_email_verification', 'candidate_password_hash', 'candidate_password_reset_token'];

        $scenarios['signupGoogle'] = ['utm_uuid', 'candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_email_verification', 'candidate_status', 'candidate_personal_photo', 'approved', 'deleted'];

        $scenarios['signupAuth0'] = ['utm_uuid', 'candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_password_hash', 'candidate_language_pref', 'deleted'];

        $scenarios['signup'] = ['utm_uuid', 'candidate_name', 'candidate_name_ar', 'candidate_email', 'candidate_phone', 'candidate_password_hash', 'candidate_language_pref', 'deleted'];

        $scenarios['updateBankDetail'] = ['bank_account_name', 'candidate_iban', 'is_incomplete_profile'];

        $scenarios['candidatePhone'] = ['candidate_phone', 'is_incomplete_profile'];

        $scenarios['candidatePreferredTime'] = ['candidate_preferred_time', 'is_incomplete_profile'];

        $scenarios['statusChange'] = ['approved', 'is_incomplete_profile'];

        $scenarios['updateHourRate'] = ['candidate_hourly_rate', 'is_incomplete_profile', "currency_code"];

        $scenarios['updateLocation'] = ['candidate_latitude', 'candidate_longitude', 'candidate_area_uuid', 'is_incomplete_profile'];

        $scenarios['updatePendingProfile'] = ['candidate_pending_profile', 'is_incomplete_profile'];

        $scenarios['updatePasswordToken'] = ['candidate_password_reset_token', 'candidate_limit_email', 'is_incomplete_profile'];

        $scenarios['updateProfileUrl'] = ['profile_url', 'is_incomplete_profile'];

        return $scenarios;
    }

    /**
     * @return void
     */
    public function validateCivilIdNumber() {

        //check if there is other candidate with same civil id + not deleted

        $exists = self::find()
            ->andWhere(["!=", "candidate_id" , $this->candidate_id])
            ->andWhere(["candidate_civil_id" => $this->candidate_civil_id, 'deleted' => false])
            ->exists();

        if ($exists) {
            $this->addError('candidate_civil_id', Yii::t('app', "Civil ID number already in use with other account!"));
        }
    }

    /**
     * @return void
     */
    public function validateCivilID() {

        //&& $this->nationality &&
        //            $this->nationality->iso == "KW"

        //avoid partial data validation, checking expiry when full ID available

        if (!$this->candidate_civil_photo_front || !$this->candidate_civil_photo_back) {
            return true;
        }

        $foundDate = false;

        $response = Yii::$app->idExpiryDateExtractor
            ->extractExpiryDate("photos/" . $this->candidate_civil_photo_front);

        if ($response['operation'] == "success") {

            if(sizeof($response['matches']) > 0) {
                $date = array_pop($response['matches']);
                $dateTime = strtotime(str_replace("/", "-", $date));

                //as dates will be in different format

                if (!empty($date) && $dateTime < time()) {
                    $this->addError('candidate_civil_photo_front', Yii::t('app', "Invalid Civil ID (Expired)"));
                } else if ($dateTime > 0) {
                    $foundDate = true;
                    $this->candidate_civil_expiry_date = date("Y-m-d", $dateTime);
                }
            }

            if(sizeof($response['ids']) > 0) {
                $this->candidate_civil_id = $response['ids'][0];
            }

        } else {
            //    $this->addError('candidate_civil_photo_front', Yii::t('app', "Error on reading card"));
        }

        // no need to check back if date found in first photo

        if (!$foundDate) {

            $response = Yii::$app->idExpiryDateExtractor
                ->extractExpiryDate("photos/" . $this->candidate_civil_photo_back);

            if ($response['operation'] == "success" && sizeof($response['matches']) > 0) {

                if(sizeof($response['matches']) > 0) {

                    $date = array_pop($response['matches']);
                    $dateTime = strtotime(str_replace("/", "-", $date));

                    //as dates will be in different format

                    if (!empty($date) && $dateTime < time()) {
                    //if (empty($date) || $dateTime < time()) {
                        $this->addError('candidate_civil_photo_front', Yii::t('app', "Invalid Civil ID (Expired)"));
                    } else if ($dateTime > 0) {
                        $foundDate = true;
                        $this->candidate_civil_expiry_date = date("Y-m-d", $dateTime);
                    }
                }

                //as civil id
                /*if(sizeof($response['ids']) > 0) {
                    $this->candidate_civil_id = array_pop($response['ids']);
                }*/

            } else {
                //    $this->addError('candidate_civil_photo_back', Yii::t('app', "Error on reading card"));
            }
        }

        if($this->candidate_civil_expiry_date && $this->candidate_civil_id) {
            $this->candidate_civil_need_verification = false;
        }

        //if not got expiry even after both photos got uploaded

        /*if(!$foundDate)//$this->candidate_civil_expiry_date
        {
            $this->addError('candidate_civil_photo_front', Yii::t('app', "Invalid Civil ID"));
        }*/
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

        if($this->$attribute && sizeof(explode (' ', $this->$attribute)) == 1) {
            $this->addError('candidate_name', Yii::t('app', $message));
        }
    }

    /**
     * new email can not be same as old
     * @param $attribute
     */
    public function validateNewEmail($attribute) {

        if ($this->candidate_new_email && $this->candidate_new_email != $this->candidate_email) {
            $this->addError('candidate_new_email', Yii::t('app', 'New email can not be same as old email'));
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
            ])
            ->andWhere(['candidate.deleted' => 0]);

        if($this->candidate_id) {
            $query->andWhere(['!=', 'candidate_id', $this->candidate_id]);
        }

        if ($query->exists()) {
            $this->addError('candidate_email', Yii::t('app',
                'We found account with same email, Please login with same email!'));
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
        if($this->candidate_civil_expiry_date && strtotime($this->candidate_civil_expiry_date) < strtotime(date('Y-m-d')))
        {
            $this->addError('candidate_civil_expiry_date', Yii::t('candidate','Candidate have expired civil id.'));
        }
    }

    /**
     * Validate candidate age if exceeds limit
     */
    public function validateAge()
    {
        if($this->age < 16 || $this->age > 25) {
            $this->addError('candidate_birth_date', Yii::t('candidate','Candidate age should be between 16 to 25.'));
        }
    }

    /**
     * @return int
     */
    public function getAvgTimeToViewInvitations() {
        return $this->getInvitations()
            ->average("invitation_seen_in");
    }

    /**
     * @return array
     */
    public function getInvitationStats() {
        $total = $this->getInvitations()
            ->andWhere( new Expression("invitation_seen_in IS NOT NULL"))
            ->count();

        $totalApp = $this->getInvitations()
            ->andWhere(['invitation_seen_via' => "app"])
            ->andWhere( new Expression("invitation_seen_in IS NOT NULL"))
            ->count();

        $totalEmail = $this->getInvitations()
            ->andWhere(['invitation_seen_via' => "email"])
            ->andWhere( new Expression("invitation_seen_in IS NOT NULL"))
            ->count();


        return [
            "total" => $total,
            "totalApp" => $totalApp,
            "totalEmail" => $totalEmail,
            "totalAppPercentage" => $total > 0? $totalApp * 100 / $total: null,
            "totalEmailPercentage" => $total > 0? $totalEmail * 100 / $total: null,
        ];
    }

    /**
     * @param $candidate_gender
     * @return string
     */
    public static function getGenderText($candidate_gender) {
        switch ($candidate_gender) {
            case self::GENDER_MALE:
                return "Male";
            case self::GENDER_FEMALE:
                return "Female";
            default:
                return "Other";
        }
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
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
            'candidate_intro' => Yii::t('candidate', 'Introduction'),
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
            "candidate_civil_need_verification"=> Yii::t('candidate','Civil Need Verification'),
            'candidate_driving_license' => Yii::t('candidate','Driving License'),
            'candidate_resume' => Yii::t('candidate','Resume'),
            'candidate_hourly_rate' => Yii::t('candidate','Hourly Rate'),
            'candidate_auth_key' => Yii::t('candidate','Auth Key'),
            'candidate_password_hash' => Yii::t('candidate','Password'),
            'candidate_password_reset_token' => Yii::t('candidate','Password Reset Token'),
            'candidate_language_pref' => Yii::t('candidate','Language preference'),
            'candidate_job_search_status' => Yii::t('candidate', 'Job search status'),
            'candidate_job_search_updated_at'=> Yii::t('candidate', 'Job search status updated at'),
            'candidate_committed' => Yii::t('candidate', 'Committed'),
            'candidate_preferred_time' => Yii::t('candidate', 'Preferred time'),
            'candidate_status' => Yii::t('candidate','Status'),
            'candidate_created_at' => Yii::t('candidate','Created At'),
            'candidate_updated_at' => Yii::t('candidate','Updated At'),
            'employee_id' => Yii::t('candidate','Employee ID'),
            'candidate_mom_kuwaiti' => Yii::t('candidate','Candidate Mom Kuwaiti'),
            'profile_url' => Yii::t('candidate','Profile Url'),
            "currency_code" => Yii::t('app','Currency Code'),
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @return bool|void
     */
    public function afterSave($insert, $changedAttributes) {

        parent::afterSave($insert, $changedAttributes);

        if($insert && $this->store_id)
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

            $userTableSchema = Yii::$app->walletDb->schema->getTableSchema('user');

            if($userTableSchema)
                $this->updateWalletBankDetail();
        }

        if(!$insert && array_key_exists('candidate_password_hash', $changedAttributes)) {
            $this->sendPasswordUpdatedEmail();
        }

        if (
            //$this->candidate_status == self::STATUS_ACTIVE &&
            in_array(
                $this->candidate_job_search_status,
                [self::NOT_LOOKING_OPEN_FOR_OFFER, self::ACTIVELY_LOOKING_FOR_JOB]
            )
            &&
            //$this->approved &&
            !in_array(
                $this->scenario, [
                    'updateLanguagePref', //as not saving language preference in algolia
                    'signup', //as will have incomplete profile
                    'updateEmail',//as not saving email in algolia
                    'updatePendingProfile',
                    'changePassword',
                    'updatePasswordToken',
                    "undoDelete"
                    //'verifyEmail'
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
            Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'],
                $this->candidate_id);
        }


        if(YII_ENV == 'prod') {

            $userId = Yii::$app->user->isGuest ? $this->candidate_id: Yii::$app->user->getId();

            if ($insert) {

                Yii::$app->eventManager->track(
                    'Candidate Profile Created',
                    [
                        'candidate_id' => $this->candidate_id,
                        'name' => $this->candidate_name,
                        'email' => $this->candidate_email,
                        'age' => $this->getAge(),
                        'gender' => self::getGenderText($this->candidate_gender),
                        "university" => $this->university? $this->university->university_name_en: null,
                        "country" => $this->country? $this->country->country_name_en: null
                    ],
                    null,
                    $userId);
            }
            else
            {
                Yii::$app->eventManager->track(
                    'Candidate Profile Updated',
                    [
                        'candidate_id' => $this->candidate_id,
                        'name' => $this->candidate_name,
                        'email' => $this->candidate_email,
                        'age' => $this->getAge(),
                        'gender' => self::getGenderText($this->candidate_gender),
                        "university" => $this->university? $this->university->university_name_en: null,
                        "country" => $this->country? $this->country->country_name_en: null
                    ],
                    null,
                    $userId);
            }

            if(
                !empty($this->getOldAttribute("candidate_pending_profile")) &&
                empty($this->candidate_pending_profile)
            ) {
                Yii::$app->eventManager->track(
                    'Candidate Profile Completed',
                    [
                        'candidate_id' => $this->candidate_id,
                        'name' => $this->candidate_name,
                        'email' => $this->candidate_email,
                        'age' => $this->getAge(),
                        'gender' => self::getGenderText($this->candidate_gender),
                        "university" => $this->university? $this->university->university_name_en: null,
                        "country" => $this->country? $this->country->country_name_en: null
                    ],
                    null,
                    $userId);
            }

        }

        if($insert && $this->campaign) {
            $this->campaign->no_of_signups++;
            $this->campaign->save(false);
        }

        return true;
    }

    /**
     * update bank details
     * @return void
     */
    public function updateWalletBankDetail() {

        $walletUser = WalletUser::findByEmail($this->candidate_email);

        if(!$walletUser) {
            return true;
        }

        \common\models\WalletTransfer::updateAll([
            'bank_uuid' => $walletUser->bank_uuid,
            'transfer_benef_name' => $walletUser->bank_account_name,
            'transfer_benef_iban' => $walletUser->iban
        ], [
            'transfer_status' => WalletTransfer::STATUS_INITIATED,
            'user_uuid' => $walletUser->user_uuid
        ]);

        //update bank details in wallet user db table

        $bank = null;

        if($this->bank)
        {
            $bank = WalletBank::findOne(['bank_iban_code' => $this->bank->bank_iban_code]);

            //add wallet bank if not

            if(!$bank) {
                $bank = new WalletBank;
                $bank->bank_name = $this->bank->bank_name;
                $bank->bank_iban_code = $this->bank->bank_iban_code;
                $bank->bank_swift_code = $this->bank->bank_swift_code;
                $bank->bank_address = $this->bank->bank_address;
                $bank->bank_transfer_type = $this->bank->bank_transfer_type;
                $bank->save();
            }
        }

        \common\models\WalletUser::updateAll([
            'bank_uuid' => $bank? $bank->bank_uuid: null,
            'bank_account_name' => $this->bank_account_name,
            'iban' => $this->candidate_iban
        ], [
            'user_uuid' => $walletUser->user_uuid
        ]);
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
            return $model->isProfileCompleted();
        };

        $fields['pendingField'] = function($model) {
            return ($model->pendingProfile) ? array_keys($model->pendingProfile) : null;
        };

        $fields['isWorking'] = function($model) {
            return $model->getIsWorking();
        };

        $fields['civilExpired'] = function ($model) {
            return $model->candidate_civil_expiry_date && (strtotime($model->candidate_civil_expiry_date) <
                strtotime(date('Y-m-d')));
        };

        unset(
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
            "candidateLinks",
            "transferCost",
            "invitationStats",
            "avgTimeToViewInvitations",
            'storeAssignmentRequest',
            "latestCandidateWorkHistory",
            'campaign',
            'store',
            'company',
            'university',
            'nationality',
            'country',
            'area',
            'bank',
            'candidateSkills',
            'candidateTags',
            'candidateEducations',
            'candidateExperiences',
            'candidateIdCard',
            'notes',
            'workHistory',
            'candidateWarnings',
            'acceptanceRatio',
            'rejectionRatio',
            'profit',
            'revenue',
            'candidateStats',
            "candidateWorkingHour",
            "candidateWorkingDates",
            "certificates",
            "currentContract",
            "currentContract.amount",
        ];
    }

    public function getTransferCost() {

        if (!$this->store_id) {
            return null;
        }

        //store level

        $assignment = $this->getWorkHistory()
            ->andWhere(new Expression("end_date IS NULL"))
            ->orderBy("id DESC")//latest assignment
            ->one();

        if (!$assignment) {
            return null;
        }

        if ($assignment->transfer_cost > 0) {
            return $assignment->transfer_cost;
        }

        // company level

        $company_id = empty($assignment->parent_company_id) ? $assignment->company_id:
            $assignment->parent_company_id;

        $model = TransferCost::find()
            ->andWhere([
                "candidate_id" => $this->candidate_id,
                "company_id" => $company_id
            ])
            ->one();

        if ($model && $model->transfer_cost > 0) {
            return $model->transfer_cost;
        }

        return Yii::$app->params['transfer_cost']; //default transfer cost
    }

    /**
     * Returns age of candidate
     * @return integer
     */
    public function getAge()
    {
        return $this->candidate_birth_date?
            floor((time() - strtotime($this->candidate_birth_date))/31556926): null;
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

        if ($this->candidate_iban) {
            $this->bank_id = null;

            $banks = Bank::find()->all();

            foreach($banks as $bank) {
                if($bank->bank_iban_code && strpos(strtolower($this->candidate_iban), strtolower($bank->bank_iban_code)) > -1) {
                    $this->bank_id = $bank->bank_id;
                    break;
                }
            }
        }

        //update profile status

        $this->is_incomplete_profile = $this->isInCompleteProfile();

        $this->candidate_pending_profile = implode(',', array_keys($this->pendingProfile));

        if(Yii::$app->request instanceof \yii\web\Request) {

            // Get initial IP address of requester
            $ip = Yii::$app->request->getRemoteIP();

            // Check if request is forwarded via load balancer or cloudfront on behalf of user
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

                // as "X-Forwarded-For" is usually a list of IP addresses that have routed
                if ($forwardedFor) {
                    $IParray = array_values(array_filter(explode(',', $forwardedFor)));

                    // Get the first ip from forwarded array to get original requester
                    if ($IParray) {
                        $ip = $IParray[0];
                    }
                }
            }

            $this->ip_address = $ip;

            if ($insert) {

                $count = self::find()
                    ->andWhere(['ip_address' => $this->ip_address])
                    ->andWhere("DATE(candidate_created_at) = DATE('".date('Y-m-d')."')")
                    ->count();

                if ($count > 10) {
                    Yii::error("too may candidate signup from same ip");
                    return $this->addError('ip_address', "Too many requests");
                }
            }
        }

        if(!$this->currency_code) {
            $this->currency_code = "KWD";
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
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStoreAssignmentRequest($modelClass = "\common\models\StoreAssignmentRequest")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->andWhere(['status' => StoreAssignmentRequest::STATUS_PENDING]);
            //->orderBy('created_at DESC');
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStoreAssignmentRequests($modelClass = "\common\models\StoreAssignmentRequest")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
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
    public function getCandidateNotifications($modelClass = "\common\models\CandidateNotification")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnreadCandidateNotifications($modelClass = "\common\models\CandidateNotification")
    {
        return $this->getCandidateNotifications($modelClass)
            ->andWhere(['is_new' => true]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCertificates($modelClass = "\common\models\CandidateCertificate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestApplications($modelClass = "\common\models\RequestApplication")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->orderBy('created_at DESC');
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
    public function getCandidateLinks($modelClass = "\common\models\CandidateLink")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
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
    public function getCampaign($modelClass = "\common\models\Campaign")
    {
        return $this->hasOne($modelClass::className(), ['utm_uuid' => 'utm_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCard($modelClass = "\common\models\CandidateIdCard")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->andwhere(['candidate_id_card.deleted' => 0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCards($modelClass = "\common\models\CandidateIdCard")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->andwhere(['candidate_id_card.deleted' => 0]);
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
    public function getContracts($modelClass = "\common\models\Contract")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentContract($modelClass = "\common\models\Contract")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            //->filterActive()
            ->andOnCondition(new Expression('contract.store_id = '.$this->store_id.' AND (
                contract.end_date is null OR contract.end_date >= CURDATE()
            )'));
           // ->andWhere(['store_id' => $this->store_id]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->andWhere(['candidate_work_history.store_id' => $this->store_id])
            ->andWhere(new Expression("candidate_work_history.end_date IS NULL"));
            //->andWhere(['{{%candidate_work_history}}.deleted'=>0]);
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

        if ($this->candidate_password_hash) {
            $this->setPassword($this->candidate_password_hash);
        }

        $this->generateAuthKey();

        if(!$this->save()) {
            return false;
        }

        if($byStaff) {
            
            $this->sendPasswordResetEmail();

            Yii::info("[New Student Account Created By ".Yii::$app->user->identity->staff_name . "] Name: ".$this->candidate_name. ", Phone: ".$this->candidate_phone.", Email: ".$this->candidate_email, __METHOD__);

        } else {

            if ($this->candidate_email_verification != self::EMAIL_VERIFIED) {
                $this->sendVerificationEmail();
            }
        
            Yii::info("[New Student Registration] ".$this->candidate_name. " has signed up. Phone: ".$this->candidate_phone.", Email: ".$this->candidate_email, __METHOD__);
        }

        return $this;
    }

    /**
     * notify candidate for password update
     */
    public function sendPasswordUpdatedEmail()
    {
        if(!$this->candidate_email_verification)
            return false;

        $ml = new MailLog();
        $ml->to = $this->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Your password reset was a success';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("candidate/password-updated-html",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->candidate_email,
                "name" => $this->candidate_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject('Your password reset was a success');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
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
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public function sendPasswordResetEmail()
    {
        //if(!$this->candidate_email_verification)
        //    return false;

        $this->setScenario('updatePasswordToken');
        $this->generatePasswordResetToken();

        //Update candidate last email limit timestamp
        $this->candidate_limit_email = new Expression('NOW()');

        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['candidateAppUrl'] . 'update-password/' . $this->candidate_password_reset_token;

        $name = explode(' ', $this->candidate_name);

        $ml = new MailLog();
        $ml->to = $this->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Reset your StudentHub password';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("candidate/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->candidate_email,
                "name" => (isset($name[0])) ? $name[0] : $this->candidate_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject('Reset your StudentHub password');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        } /*catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "password-reset-token");
        }*/
    }

    /**
     * Send link in sms to reset password
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public function sendPasswordResetSMS()
    {
        $this->setScenario('updatePasswordToken');
        $this->generatePasswordResetToken();
        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['candidateAppUrl'] . 'update-password/' . $this->candidate_password_reset_token;

        $name = explode(' ',$this->candidate_name);

        $message = "Hello {{name}}, Your StudentHub password reset link {{link}}";

        return Yii::$app->smsComponent->sendSms($this->candidate_phone, str_replace([
            "{{name}}", "{{link}}"
        ], [
            $name, $webUrl
        ], $message));
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
        if (!$this->candidate_password_hash) {
            return null;
        }

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
        $this->candidate_auth_key = Yii::$app->security->generateRandomString(4);
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
     * @inheritdoc
     */
    public static function findIdentityByUnVerifiedTokenToken($token, $type = null) {
        $token = CandidateToken::find()
            ->andWhere([
                'token_value' => $token,
                'token_status' => CandidateToken::STATUS_ACTIVE
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('candidate')
            ->one();

        if ($token && $token->candidate && !$token->candidate->deleted) {
            return $token->candidate;
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $authType = HttpBearerAuth::class, $type = CandidateToken::STATUS_ACTIVE, $otp = null)
    {
        $token = \candidate\models\CandidateToken::find()
            ->andWhere([
                'token_value' => $token,
                'token_status' => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('candidate')
            ->one();

        if (!$token) {
            return false;
        }

        if ($otp && $otp != $token->otp) {
            $token->total_attempt = $token->total_attempt + 1;

            if ($token->total_attempt > 3) {
                $token->delete();
                return false;
            }

            if (!$token->save()) {
                Yii::error($token->errors);
            }

            return false;
        }

        //update last used datetime

        $token->token_status = CandidateToken::STATUS_ACTIVE;//make inactive token to active on found with OTP
        $token->token_last_used_datetime = new Expression('NOW()');
        $token->save();

        //should not able to login, if email not verified but have valid token

        if ($token->candidate && $token->candidate->candidate_email_verification) {
            return $token->candidate;
        }

        //invalid token

        $token->delete();
    }

    /**
     * Create an Access Token Record for this Candidate
     * if the candidate already has one, it will return it instead
     * @return \common\models\CandidateToken
     */
    public function getAccessToken($type = CandidateToken::STATUS_ACTIVE){
        // Return existing inactive token if found
        /*$token = CandidateToken::find()
            ->andWhere([
                'candidate_id' => $this->candidate_id,
                'token_status' => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->one();

        if($token){
            return $token;
        }*/

        $detect = new MobileDetect();

        $device = "Desktop Device";

        if ($detect->isMobile()) {
            $device = "Mobile Device";
        } elseif ($detect->isTablet()) {
            $device = "Tablet Device";
        }

        // Create new inactive token
        $token = new CandidateToken();
        $token->candidate_id = $this->candidate_id;
        $token->token_value = CandidateToken::generateUniqueTokenString();
        $token->token_status = $type;
        $token->token_device = $device;
        $token->token_device_id = mb_strimwidth( $detect->getUserAgent(), 0, 250, "...");
        $token->token_expiry_datetime = date('Y-m-d H:i:s', strtotime("+1 month"));
        $token->ip_address = isset(Yii::$app->params['user_ip_address']) ?
            Yii::$app->params['user_ip_address']: Yii::$app->request->getRemoteIP();

        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));

        }

        //if 2 step auth enable, send OTP
        if ($type == AdminToken::STATUS_INACTIVE) {
            $this->sendOTPMail($token);
        }

        return $token;
    }

    /**
     * Send OTP mail to candidate
     * @param \common\models\CandidateToken $token
     * @return bool
     */
    public function sendOTPMail($token) {

        //generate OTP
        $token->otp = Yii::$app->security->generateRandomString(4);
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        $ml = new MailLog();
        $ml->to = $this->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'OTP for 2 step verification';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("candidate/candidate-otp",
            [
                "model" => $this,
                "otp" => $token->otp,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject('OTP for 2 step verification');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
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
     * Send candidate list having birthday today
     * to admin
     * @return null
     */
    public static function birthdayAlert()
    {
        $query = Candidate::find()
            ->notDeleted()
            ->andWhere('MONTH(candidate_birth_date) = MONTH(NOW()) AND DAY(candidate_birth_date) = DAY(NOW())')
            ->andWhere(['candidate_email_verification' => 1]);

        //$allStaff = Staff::findAll(['deleted' => false, 'staff_notification' => true]);

        //$allStaffEmails = ArrayHelper::map($allStaff,'staff_email','staff_name');

        $totalProcessed = 0;

        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {
                if (!$candidate->candidate_email_verification)
                    return false;

                $ml = new MailLog();
                $ml->to = $candidate->candidate_email;
                $ml->from = \Yii::$app->params['supportEmail'];
                $ml->subject = 'Happy Birthday from StudentHub';
                if (!$ml->save()) {
                    Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                }

                $mailer = Yii::$app->mailer->compose("birthday",
                    [
                        "candidate" => $candidate,
                        "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                        "birthday_img" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/birthday.gif', 'https'),
                    ])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                    ->setTo($candidate->candidate_email)
                    ->setSubject('Happy Birthday from StudentHub');

                if(\Yii::$app->params['elasticMailIpPool']) {
                    $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
                }
                
                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    // Handle email transport-specific exceptions
                    Yii::error( "Failed to send email: " . $e->getMessage());
                } catch (\Exception $e) {
                    // Handle any other exceptions
                    Yii::error( "An error occurred: " . $e->getMessage());
                }
            }

            $totalProcessed += sizeof($candidates);
        }

        return $totalProcessed;
    }

    /**
     * @return null
     */
    public static function civilIdExpire()
    {
        //todo: either assigned to work or looking for job / having complete profile

        $query = Candidate::find()
            ->andWhere('YEAR(candidate_civil_expiry_date) = YEAR(NOW()) AND 
                MONTH(candidate_civil_expiry_date) = MONTH(NOW()) AND 
                DAY(candidate_civil_expiry_date) = DAY(NOW())')
            ->andWhere(['candidate_email_verification' => 1])
            ->andWhere('candidate.store_id > 0'); //sending email to only assigned caniddate to avoid eamil account spam count issue

        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                if (!$candidate->candidate_email_verification)
                    return false;

                $f_name = $candidate->candidate_name ? $candidate->candidate_name : $candidate->candidate_name_ar;

                $name = explode(' ', $f_name)[0];

                $url = '';

                $isProfileCompleted = $candidate->isProfileCompleted();

                if (!$isProfileCompleted) {
                    $url = Yii::$app->params['candidateAppUrl'];
                } else {
                    $url = Yii::$app->params['candidateAppUrl'] . 'view/profile';
                }

                $ml = new MailLog();
                $ml->to = $candidate->candidate_email;
                $ml->from = \Yii::$app->params['supportEmail'];
                $ml->subject = 'Please update your civil id';
                if (!$ml->save()) {
                    Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                }

                $mailer = Yii::$app->mailer->compose("civil-expired",
                    [
                        'logo' => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                        'url' => $url,
                        'name' => $name
                    ])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                    ->setTo($candidate->candidate_email)
                    ->setSubject('Please update your civil id');

                if(\Yii::$app->params['elasticMailIpPool']) {
                    $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
                }
                
                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    // Handle email transport-specific exceptions
                    Yii::error( "Failed to send email: " . $e->getMessage());
                } catch (\Exception $e) {
                    // Handle any other exceptions
                    Yii::error( "An error occurred: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function undoDelete()
    {
        $this->setScenario("undoDelete");

        $this->deleted = 0;

        $id = $this->candidate_civil_id? explode("-", $this->candidate_civil_id): null;

        $this->candidate_civil_id = isset($id[1]) ? $id[1]: null;
        $this->candidate_password_reset_token = null;

        return $this->save(false);
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
        $totalMinutes = 0;
        $totalSeconds = 0;
        $totalPaid = 0;
        $totalBonus = 0;

        foreach ($this->transferCandidate as $transfer) {

            $totalHours += $transfer->hours;
            $totalMinutes += $transfer->minutes;
            $totalSeconds += $transfer->seconds;

            if (
                $transfer->invoice &&
                $transfer->invoice->invoice_status == 'paid'
            ) {
                $totalPaid += ($transfer->hours * $transfer->candidate_hourly_rate);
                $totalBonus += $transfer->bonus - $transfer->bonus_commission;
            }
        }

        //fix
        $totalMinutes += floor($totalSeconds / 60);
        $totalHours += floor($totalMinutes / 60);

        $totalMinutes = $totalMinutes % 60;
        $totalSeconds = $totalSeconds % 60;

        return [
            'hours' => $totalHours,
            'minutes' => $totalMinutes,
            'seconds' => $totalSeconds,
            'paid' => $totalPaid,
            'bonus' => $totalBonus,
        ];
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
            ->andWhere(['candidate.deleted' => 0])
            ->one();

        if(!$candidate) {
            return [
                'success' => false,
                'message' =>Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
            ];
        }

        $candidate->setScenario('verifyEmail');

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
                'message' => Yii::t('candidate','This email verification link is no longer valid, please login to send a new one')
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

        //todo: fix security risk by file name 
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
     * Update profile photo from temp s3 bucket (candidate app).
     * @return bool
     */
    public function updateProfilePhoto() {

        $this->scenario = 'tmpProfilePhoto';

        if (!$this->validate()) {
            return false;
        }

        if (!$this->updatePersonalPhoto()) {
            return false;
        }

        $this->scenario = 'changeProfilePhoto';

        return $this->save();
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

        } catch (\Cloudinary\Exception\Error $e) {

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

        } catch (\Cloudinary\Exception\Error $e) {

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
     * Whether the stored value is already a permanent-bucket profile photo key.
     *
     * @param string|null $stored
     * @return bool
     */
    public static function isPermanentPersonalPhotoKey($stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        $v = ltrim(trim((string)$stored), '/');

        return strpos($v, self::PERSONAL_PHOTO_S3_PREFIX) === 0
            || strpos($v, self::LEGACY_PERSONAL_PHOTO_S3_PREFIX) === 0;
    }

    /**
     * Whether the submitted value is a temporary upload key (not an existing permanent key).
     *
     * @param string|null $stored
     * @return bool
     */
    public static function isTemporaryPersonalPhotoUploadKey($stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        return !self::isPermanentPersonalPhotoKey($stored);
    }

    /**
     * Build the permanent S3 key for a new profile photo upload.
     * Always `candidate-profile-photos/<basename>` for temp keys.
     *
     * @param string|null $stored Raw submitted temp key or existing permanent key
     * @return string Normalized S3 key or empty string when empty after trim
     */
    public static function normalizePersonalPhotoPermanentS3Key($stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        $v = ltrim(trim((string)$stored), '/');

        if ($v === '') {
            return '';
        }

        if (self::isPermanentPersonalPhotoKey($v)) {
            return $v;
        }

        $basename = basename($v);

        return $basename === '' ? '' : (self::PERSONAL_PHOTO_S3_PREFIX . $basename);
    }

    /**
     * S3 keys to probe when resolving a stored profile photo value.
     *
     * @param string|null $stored
     * @return string[]
     */
    public static function personalPhotoS3KeysToProbe($stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }

        $v = ltrim(trim((string)$stored), '/');

        if ($v === '') {
            return [];
        }

        if (strpos($v, self::PERSONAL_PHOTO_S3_PREFIX) === 0) {
            return [$v];
        }

        if (strpos($v, self::LEGACY_PERSONAL_PHOTO_S3_PREFIX) === 0) {
            return [$v];
        }

        $basename = basename($v);

        if ($basename === '') {
            return [];
        }

        return [
            self::PERSONAL_PHOTO_S3_PREFIX . $basename,
            self::LEGACY_PERSONAL_PHOTO_S3_PREFIX . $basename,
        ];
    }

    /**
     * Resolved public URL for Staff/candidate profile photo display.
     * Fast path only: no S3 existence probes (safe for list serialization).
     *
     * @return string|null
     */
    public function getPersonalPhotoUrl(): ?string
    {
        $stored = ltrim(trim((string)$this->candidate_personal_photo), '/');

        if ($stored === '') {
            return null;
        }

        if (self::isPermanentPersonalPhotoKey($stored)) {
            return Yii::$app->resourceManager->getUrl($stored);
        }

        return self::buildLegacyCloudinaryPersonalPhotoUrl($stored, false);
    }

    /**
     * Server-side fetch for ID card rendering (embedded data URI for headless Chromium).
     *
     * @return string|null
     */
    public function getPersonalPhotoDataUriForIdCard(): ?string
    {
        $fetchUrl = $this->resolvePersonalPhotoFetchUrlForIdCard();

        if ($fetchUrl === null) {
            return null;
        }

        return self::fetchImageAsDataUri($fetchUrl);
    }

    /**
     * Stricter fetch URL resolver for ID card generation (may probe S3 for bare filenames).
     *
     * @return string|null
     */
    protected function resolvePersonalPhotoFetchUrlForIdCard(): ?string
    {
        $stored = trim((string)$this->candidate_personal_photo);

        if ($stored === '') {
            return null;
        }

        $storedNorm = ltrim($stored, '/');

        if (self::isPermanentPersonalPhotoKey($storedNorm)) {
            return Yii::$app->resourceManager->getUrl($storedNorm);
        }

        foreach (self::personalPhotoS3KeysToProbe($stored) as $s3Key) {
            if (Yii::$app->resourceManager->fileExists($s3Key)) {
                return Yii::$app->resourceManager->getUrl($s3Key);
            }
        }

        return self::buildLegacyCloudinaryPersonalPhotoUrl($stored, true);
    }

    /**
     * Copy a new profile photo from the temp bucket into permanent storage.
     *
     * @return bool
     */
    public function updatePersonalPhoto(): bool
    {
        $submitted = ltrim(trim((string)$this->candidate_personal_photo), '/');

        if ($submitted === '') {
            return true;
        }

        if (self::isPermanentPersonalPhotoKey($submitted)) {
            $existing = ltrim(trim((string)($this->oldAttributes['candidate_personal_photo'] ?? '')), '/');

            if ($existing !== '' && $submitted === $existing) {
                $this->candidate_personal_photo = $existing;
                return true;
            }

            $this->addError(
                'candidate_personal_photo',
                Yii::t('app', 'Your profile photo could not be uploaded. Please try again.')
            );
            return false;
        }

        if (!Yii::$app->temporaryBucketResourceManager->fileExists($submitted)) {
            $this->addError('candidate_personal_photo', Yii::t('app', 'Your profile photo could not be uploaded. Please try again.'));
            return false;
        }

        $targetPath = self::normalizePersonalPhotoPermanentS3Key($submitted);

        if ($targetPath === '') {
            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }

        try {
            Yii::$app->resourceManager->copy(
                $submitted,
                $targetPath,
                Yii::$app->temporaryBucketResourceManager->bucket
            );
        } catch (\Throwable $e) {
            Yii::error([
                'action'       => 'Candidate::updatePersonalPhoto',
                'candidate_id' => $this->candidate_id ?? null,
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.profile-photo');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }

        try {
            if (!Yii::$app->resourceManager->fileExists($targetPath)) {
                Yii::warning([
                    'action'       => 'Candidate::updatePersonalPhoto',
                    'candidate_id' => $this->candidate_id ?? null,
                    'reason'       => 'post-copy verification failed',
                ], 'candidate.profile-photo');
            }
        } catch (\Throwable $e) {
            Yii::warning([
                'action'       => 'Candidate::updatePersonalPhoto',
                'candidate_id' => $this->candidate_id ?? null,
                'reason'       => 'post-copy verification raised',
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.profile-photo');
        }

        $this->cleanupOldPersonalPhotoAfterUpload($targetPath);
        $this->candidate_personal_photo = $targetPath;

        return true;
    }

    /**
     * Best-effort delete of the stored profile photo object (S3 or legacy Cloudinary).
     *
     * @param string|null $storedValue
     * @return void
     */
    public function deletePersonalPhotoStorageObject(?string $storedValue = null): void
    {
        $stored = ltrim(trim((string)($storedValue ?? $this->candidate_personal_photo)), '/');

        if ($stored === '') {
            return;
        }

        if (self::isPermanentPersonalPhotoKey($stored)) {
            try {
                Yii::$app->resourceManager->delete($stored);
            } catch (\Throwable $e) {
                Yii::warning([
                    'action'       => 'Candidate::deletePersonalPhotoStorageObject',
                    'candidate_id' => $this->candidate_id ?? null,
                    'storage'      => 's3',
                    'reason'       => self::classifyS3DeleteThrowable($e),
                    'exception'    => get_class($e),
                    'message'      => $e->getMessage(),
                ], 'candidate.profile-photo');
            }
            return;
        }

        try {
            $this->deleteLegacyCloudinaryPersonalPhotoByValue($stored);
        } catch (\Throwable $e) {
            Yii::warning([
                'action'       => 'Candidate::deletePersonalPhotoStorageObject',
                'candidate_id' => $this->candidate_id ?? null,
                'storage'      => 'cloudinary',
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.profile-photo');
        }
    }

    /**
     * Best-effort removal of the previous profile photo after a successful upload.
     *
     * @param string $newPermanentKey
     * @return void
     */
    protected function cleanupOldPersonalPhotoAfterUpload(string $newPermanentKey): void
    {
        $oldStored = $this->oldAttributes['candidate_personal_photo'] ?? null;

        if ($oldStored === null || $oldStored === '') {
            return;
        }

        $oldNorm = ltrim(trim((string)$oldStored), '/');
        $newNorm = ltrim(trim($newPermanentKey), '/');

        if ($oldNorm === '' || $oldNorm === $newNorm) {
            return;
        }

        $this->deletePersonalPhotoStorageObject($oldNorm);
    }

    /**
     * Delete a legacy Cloudinary profile photo by its bare stored filename.
     *
     * @param string $storedValue
     * @return bool
     */
    public function deleteLegacyCloudinaryPersonalPhotoByValue(string $storedValue): bool
    {
        if ($storedValue === '' || self::isPermanentPersonalPhotoKey($storedValue)) {
            return true;
        }

        try {
            $path = (YII_ENV == 'prod') ? 'candidate-photo/' : 'dev/candidate-photo/';
            Yii::$app->cloudinaryManager->delete($path . $storedValue);

            return true;
        } catch (\Cloudinary\Exception\Error $e) {
            Yii::error($e->getMessage(), 'candidate');
            return false;
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'candidate');
            return false;
        }
    }

    /**
     * Build a legacy Cloudinary URL for bare profile photo filenames.
     *
     * @param string $bareFilename
     * @param bool $idCardSize
     * @return string|null
     */
    protected static function buildLegacyCloudinaryPersonalPhotoUrl(string $bareFilename, bool $idCardSize): ?string
    {
        $bareFilename = trim($bareFilename);

        if ($bareFilename === '' || self::isPermanentPersonalPhotoKey($bareFilename)) {
            return null;
        }

        $path = (YII_ENV == 'prod') ? 'candidate-photo/' : 'dev/candidate-photo/';
        $publicId = $path . $bareFilename;

        try {
            $url = Yii::$app->cloudinaryManager->getUrl($publicId);
            if (!empty($url)) {
                return $url;
            }
        } catch (\Throwable $e) {
            // fall through to constructed URL when Cloudinary is not configured
        }

        $cloudName = Yii::$app->cloudinaryManager->cloud_name ?? null;
        if ($cloudName === null || $cloudName === '') {
            return null;
        }

        $transform = $idCardSize
            ? 'w_319,h_319,c_thumb,g_face'
            : 'c_thumb,w_200,h_200,g_face,q_auto';

        return 'https://res.cloudinary.com/'
            . rawurlencode($cloudName)
            . '/image/upload/'
            . $transform
            . '/'
            . str_replace('%2F', '/', rawurlencode($publicId));
    }

    /**
     * Fetch remote image bytes and encode as a data URI.
     *
     * @param string $url
     * @return string|null
     */
    protected static function fetchImageAsDataUri(string $url): ?string
    {
        try {
            $http = new \GuzzleHttp\Client(['timeout' => 15]);
            $response = $http->get($url);
            $body = (string)$response->getBody();

            if ($body === '') {
                return null;
            }

            $mime = $response->getHeaderLine('Content-Type');
            if ($mime === '' || stripos($mime, 'image/') !== 0) {
                $mime = 'image/jpeg';
            }

            return 'data:' . $mime . ';base64,' . base64_encode($body);
        } catch (\Throwable $e) {
            Yii::warning([
                'action'    => 'Candidate::fetchImageAsDataUri',
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ], 'candidate.profile-photo');

            return null;
        }
    }

    /**
     * Normalize a Civil ID object key stored in the DB to the permanent-bucket path:
     * always `photos/<basename>` without duplicate `photos/photos/` prefixes.
     *
     * @param string|null $stored Raw DB value (filename only, photos/..., or candidate-civil-id/...)
     * @return string Normalized S3 key or empty string when empty after trim
     */
    public static function normalizeCivilIdPermanentS3Key($stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }

        $v = ltrim(trim((string)$stored), '/');

        if ($v === '') {
            return '';
        }

        if (strpos($v, 'photos/') === 0) {
            return $v;
        }

        if (strpos($v, 'candidate-civil-id/') === 0) {
            $rest = substr($v, strlen('candidate-civil-id/'));
            $basename = basename($rest);

            return $basename === '' ? '' : ('photos/' . $basename);
        }

        return 'photos/' . $v;
    }

    /**
     * Classify S3 delete failures for logging (missing object vs other).
     *
     * @param \Throwable $e
     * @return string `s3_object_missing` or `s3_delete_failed`
     */
    public static function classifyS3DeleteThrowable(\Throwable $e): string
    {
        if ($e instanceof \Aws\S3\Exception\S3Exception) {
            $code = $e->getAwsErrorCode();
            if ($code === 'NoSuchKey' || $code === 'NotFound') {
                return 's3_object_missing';
            }
        }

        $msg = $e->getMessage();
        if (stripos($msg, 'NoSuchKey') !== false || stripos($msg, 'not found') !== false) {
            return 's3_object_missing';
        }

        if (preg_match('/\b404\b/', $msg)) {
            return 's3_object_missing';
        }

        return 's3_delete_failed';
    }

    /**
     * delete file from aws
     * @param string $type
     * @param string $side
     * @return false
     */
    public function deleteFile($type = 'resume', $side = 'front') {

        $file = null;

        $errorAttribute = 'candidate_resume';
        if ($type === 'civil-id') {
            $errorAttribute = ($side === 'back')
                ? 'candidate_civil_photo_back'
                : 'candidate_civil_photo_front';
        }

        try {
            if (isset($this->oldPrimaryKey)) {

                if ($type == 'resume' && isset($this->oldAttributes['candidate_resume'])) {
                    $file = "candidate-resume/" . $this->oldAttributes['candidate_resume'];
                } else if ($type == 'civil-id' && $side == 'front' && isset($this->oldAttributes['candidate_civil_photo_front'])) {
                    $file = self::normalizeCivilIdPermanentS3Key($this->oldAttributes['candidate_civil_photo_front']);
                } else if ($type == 'civil-id' && $side == 'back' && isset($this->oldAttributes['candidate_civil_photo_back'])) {
                    $file = self::normalizeCivilIdPermanentS3Key($this->oldAttributes['candidate_civil_photo_back']);
                }

                if ($file) {
                    Yii::$app->resourceManager->delete($file);
                }
            }

            return true;

        } catch (\Throwable $e) {

            // Missing-object deletes (e.g. the legacy PressureDelete3Days lifecycle
            // already removed the object) must not break remove/replace flows.
            $reason = ($type === 'civil-id')
                ? self::classifyS3DeleteThrowable($e)
                : 's3_delete_failed';

            Yii::warning([
                'action'       => 'Candidate::deleteFile',
                'candidate_id' => $this->candidate_id ?? null,
                'type'         => $type,
                'side'         => $side,
                'reason'       => $reason,
                's3_key'       => $file,
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');

            if ($type === 'resume') {
                $this->addError($errorAttribute, Yii::t('app', 'file not available to delete.'));
                return false;
            }

            return true;
        }
    }

    /**
     * @return bool
     */
    public function updateCivilId($side = 'front') {

        $idSide = ($side == 'front') ? 'candidate_civil_photo_front' : 'candidate_civil_photo_back';

        $fileName = $this->$idSide;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;

        $targetPath = self::normalizeCivilIdPermanentS3Key($fileName);

        if ($targetPath === '') {
            $this->addError($idSide, Yii::t('app', 'file not available to save.'));
            return false;
        }

        // 1) Copy new file from temp bucket to permanent bucket FIRST.
        //    Only after a successful copy do we touch the old file.
        try {

            Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Throwable $e) {

            Yii::error([
                'action'       => 'Candidate::updateCivilId',
                'candidate_id' => $this->candidate_id ?? null,
                'side'         => $side,
                'source_key'   => $fileName,
                'source_bucket'=> $sourceBucket,
                'target_key'   => $targetPath,
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');

            $this->addError($idSide, Yii::t('app', 'file not available to save.'));

            return false;
        }

        // 2) Optional post-copy verification. Non-fatal: existing fileExists()
        //    performs an HTTP HEAD on the public URL, which is fine because
        //    copy() writes objects with ACL=public-read.
        try {
            if (!Yii::$app->resourceManager->fileExists($targetPath)) {
                Yii::warning([
                    'action'       => 'Candidate::updateCivilId',
                    'candidate_id' => $this->candidate_id ?? null,
                    'side'         => $side,
                    'target_key'   => $targetPath,
                    'reason'       => 'post-copy verification failed',
                ], 'candidate.civil-id');
            }
        } catch (\Throwable $e) {
            // verification is best-effort; never fail the upload because of it
            Yii::warning([
                'action'       => 'Candidate::updateCivilId',
                'candidate_id' => $this->candidate_id ?? null,
                'side'         => $side,
                'target_key'   => $targetPath,
                'reason'       => 'post-copy verification raised',
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');
        }

        // 3) Best-effort delete of the old permanent-bucket file. deleteFile()
        //    now logs and swallows missing-object failures for civil-id.
        $oldNorm = self::normalizeCivilIdPermanentS3Key($this->oldAttributes[$idSide] ?? '');
        $newNorm = self::normalizeCivilIdPermanentS3Key((string)$fileName);

        if ($oldNorm !== '' && $oldNorm !== $newNorm) {
            $this->deleteFile('civil-id', $side);
        }

        return true;
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

        $ml = new MailLog();
        $ml->to = $email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Please confirm your email address';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'candidate/verify-email-html',
            'text' => 'candidate/verify-email-text',
        ], [
            'candidate' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($email)
            ->setSubject('Please confirm your email address');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
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

        /*if (!$this->university_id) {
            $this->pendingProfile['university'] = true;
        }*/

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
            $this->pendingProfile['driving license'] = true;
        }

        if (!$this->candidate_latitude && !$this->candidate_longitude && !$this->candidate_area_uuid) {
            $this->pendingProfile['location'] = true;
        }

        if (
            $this->area && $this->nationality &&
            $this->area->country &&
            $this->area->country->country_nationality_name_en == 'Kuwaiti' &&
            $this->nationality->country_nationality_name_en != 'Kuwaiti' &&
            !$this->candidate_mom_kuwaiti
        ) {
            #https://www.pivotaltracker.com/story/show/175607833
            $this->pendingProfile['candidate_mom_kuwaiti'] = true;
        }

//        if (!$this->candidate_resume) {
//            return 'resume';
//        }

//        if (!$this->candidate_hourly_rate) {
//            $this->pendingProfile['hourly rate'] = false;
//        }


        if ($this->getCandidateEducations()->count() == 0) {
            $this->pendingProfile['education'] = true;
        }


        if ($this->getCandidateSkills()->count() == 0) {
            $this->pendingProfile['skill'] = true;
        }

        if (count($this->pendingProfile) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks is candidate have incomplete profile
     * creating separately so that we can avoid pending field
     * candidate_mom_kuwaiti check as its required but not
     * mandatory for algolia upload
     * @return void|string
     */
    public function isInCompleteProfileForAlgolia() {

        if (!$this->candidate_uid) {
            $this->pendingProfile['uid'] = true;
        }

        /*if (!$this->university_id) {
            $this->pendingProfile['university'] = true;
        }*/

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
            $this->pendingProfile['driving license'] = true;
        }

        if (!$this->candidate_latitude && !$this->candidate_longitude && !$this->candidate_area_uuid) {
            $this->pendingProfile['location'] = true;
        }

        if ($this->getCandidateEducations()->count() == 0) {
            $this->pendingProfile['education'] = true;
        }


        if ($this->getCandidateSkills()->count() == 0) {
            $this->pendingProfile['skill'] = true;
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

        if(empty(Yii::$app->params['algolia_candidate_index'])) {
            return false;
        }

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

        /*$isInCompleteProfile = $this->isInCompleteProfileForAlgolia();

        /**
         * delete from algolia when profile incomplete
         *
        if ($isInCompleteProfile) {
            Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $this->candidate_id);
            return false;
        }*/

        $data = [
            'objectID' => $this->candidate_id,
            'candidate_id' => $this->candidate_id,
            //'bank_account_name' => $this->bank_account_name,
            //'candidate_iban' => $this->candidate_iban,
            'candidate_name' => $this->candidate_name,
            'candidate_name_ar' => $this->candidate_name_ar,
            'candidate_objective' => $this->candidate_objective,
            //'candidate_intro' => $this->candidate_intro,
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
            'candidate_birth_timestamp' => $this->candidate_birth_date?
                strtotime($this->candidate_birth_date): null,
            'candidate_driving_license' => $this->candidate_driving_license,
            'candidate_language_pref' => $this->candidate_language_pref,
            'candidate_job_search_status' => $this->candidate_job_search_status,
            'approved' => $this->approved,
            'candidate_mom_kuwaiti' => $this->candidate_mom_kuwaiti,
            'candidate_email_verification' => true,   // using in candidate card
            "currency_code" => $this->currency_code,
            'isProfileCompleted' => !($this->is_incomplete_profile == 1),
                //$this->isInCompleteProfileForAlgolia()? false: true,  // using in candidate card
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

        if($this->nationality) {
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

            $candidateWorkHistory = $this->getCandidateWorkHistories()
                ->orderBy('start_date DESC')
                ->asArray()
                ->one();

            if($candidateWorkHistory) {
                $data['start_date_timestamp'] = $candidateWorkHistory['start_date']? strtotime($candidateWorkHistory['start_date']): null;
                $data['end_date_timestamp'] = $candidateWorkHistory['end_date']?strtotime($candidateWorkHistory['end_date']): null;
                //could be `new Expression('NOW()')` on update

                $data['candidateWorkHistory'] = $candidateWorkHistory;
            }
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
            $data['candidate_created_at_timestamp'] = $this->candidate_created_at?
                strtotime($this->candidate_created_at): null;
            $data['candidate_updated_at_timestamp'] = $data['candidate_updated_at']?
                strtotime($data['candidate_updated_at']): null;
        }

        //candidate_certificate

        $data['candidateCertificates'] = [];

        $candidateCertificates = $this->getCandidateCertificates()
            ->joinWith(['company', "exam"])
            ->all();

        foreach ($candidateCertificates as $candidateCertificate) {

            $arrCertificate = [
                "certificate_type" => $candidateCertificate->certificate_type,
                'created_at_timestamp' => $candidateCertificate['created_at']?
                        strtotime($candidateCertificate['created_at']): null,
            ];

            $arrCertificate['certificateName'] = [
                "en" => "Other",
                "ar" => "آخر"
            ];

            if ($candidateCertificate->certificate_type == CandidateCertificate::TYPE_EXAM) {
                $arrCertificate['certificateName'] = [
                    "en" => $candidateCertificate->exam->title_en,
                    "ar" => $candidateCertificate->exam->title_ar
                ];
            } else if ($candidateCertificate->certificate_type == CandidateCertificate::TYPE_EXPERIENCE) {
                $arrCertificate['certificateName'] = [
                   // "en" => "Experience",
                   // "ar" => "Experience",
                    "en" => "Worked @ " .$candidateCertificate->company->company_common_name_en,
                    "ar" =>"عملت @ " .$candidateCertificate->company->company_common_name_ar,
                ];
            }

            $data['candidateCertificates'][] = $arrCertificate;
        }

        unset($candidateCertificates);

        //candidate_educations

        $data['candidateEducations'] = [];

        $candidateEducations = $this->getCandidateEducations()
            ->joinWith(['university', 'degree', 'major'])
            ->all();

        foreach ($candidateEducations as $education) {

            $arrEducation = [
                "graduation_year" => $education->graduation_year,
                "is_currently_studying" => $education->is_currently_studying
            ];

            if($education->university) {
                $arrEducation["university"] = [
                    "university_name_en" => $education->university->university_name_en,
                    "university_name_ar" => $education->university->university_name_ar,
                ];
            }

            if($education->degree) {

                $arrEducation["degree"] = [
                    "degree_name_en" => $education->degree->degree_name_en,
                    "degree_name_ar" => $education->degree->degree_name_ar,
                ];

                /*if ($education->degree->degreeGroup) {
                    $arrEducation["degreeGroup"] = [
                        "degree_group_name_en" => $education->degreeGroup->degree_group_name_en,
                        "degree_group_name_ar" => $education->degreeGroup->degree_group_name_ar,
                    ];
                }*/
            }

            if($education->major) {
                $arrEducation["major"] = [
                    "major_name_en" => $education->major->major_name_en,
                    "major_name_ar" => $education->major->major_name_ar,
                ];
            }

            $data['candidateEducations'][] = $arrEducation;
        }

        unset($candidateEducations);

        //candidate_experience

        $data['candidateExperiences'] = [];

        foreach ($this->getCandidateExperiences()->all() as $experience) {
            $data['candidateExperiences'][] = [
                'experience' => $experience->experience,
                'employer' => $experience->employer,
                'start_year' => $experience->start_year,
                'end_year' => $experience->end_year,
            ];
        }

        //candidate_skill

        $data['candidateSkills'] = [];

        foreach ($this->getCandidateSkills()->select('skill')->all() as $candidateSkill) {
            $data['candidateSkills'][] = [
                'skill' => $candidateSkill->skill
            ];
        }

        //candidate_tag

        $data['candidateTags'] = [];

        foreach ($this->getCandidateTags()->select('tag')->all() as $candidateTag) {
            $data['candidateTags'][] = [
                'tag' => $candidateTag->tag
            ];
        }

        $data['candidateIdCard'] = $this->getCandidateIdCard()
            ->asArray()
            ->one();

        if ($data['candidateIdCard']) {
            if (
                $data['candidateIdCard']['expiry_date'] &&
                strtotime($data['candidateIdCard']['expiry_date']) < strtotime(date('Y-m-d'))
            ) {
                $data['candidateIdCard']['status'] = "Expired";
            } else {
                $data['candidateIdCard']['status'] = "Not Expired";
            }
        }

        return $data;
    }

    /**
     * Synch with algolia
     * @return type
     */
    public static function synchWithAlgolia($type = "all") {

        //delete all objects
        //Yii::$app->algolia->clearObjects(Yii::$app->params['algolia_candidate_index']);

        //call api in batch

        $query = self::find()
            ->andWhere(['candidate.deleted' => 0]);

        switch ($type) {
            case "civilIdExpired" :
                $query->civilIdExpired();
                break;
            case "civilIdExpiredToday":
                $query->civilIdExpiredToday();
                break;
            default:
                // Code to be executed if the expression doesn't match any of the cases
                break;
        }

            /*->joinWith([
                "candidateCertificates"
                ], "true", "inner join");*

            ->joinWith([
                "store",
                "store.company"
            ], "true");

            ->joinWith([
                //'city',
                //'country',
                'nationality',
                'candidateEducations',
                'candidateSkills',
                //'candidateLanguages',
                'candidateExperiences',
               // 'candidateConclusions',
                'candidateCertificates'
            ]);*/

        $total = $query->count();

        //send 100 in each request

        Console::startProgress(0, $total);

        $n = 0;

        foreach ($query->batch(100) as $candidates) {

            $data = [];

            foreach ($candidates as $candidate) {
                $algoliaData = $candidate->prepareAlgoliaData();

                if ($algoliaData) {
                    $data[] = $algoliaData;
                    gc_collect_cycles();
                    unset($algoliaData);
                }

                //echo (memory_get_usage()/ 1000) . "KB \n";
            }

            if ($data) {
                Yii::$app->algolia->updates(Yii::$app->params['algolia_candidate_index'], $data);
            }

            $n += sizeof($data);

            unset($data);
            unset($candidates);
            gc_collect_cycles();

            Console::updateProgress($n, $total);

            //sleep(0.01);
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
    public function getCandidateTags($modelClass = "\common\models\CandidateTag")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateCertificates($modelClass = "\common\models\CandidateCertificate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
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
        if(!$this->candidate_email_verification)
            return false;

        $f_name = $this->candidate_name ? $this->candidate_name : $this->candidate_name_ar;

        $name = explode(' ', $f_name)[0];

        $ml = new MailLog();
        $ml->to = $this->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "We'll stop recommending your profile to companies";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("candidate/commitment-warning",
            [
                "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                "name" => $name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->candidate_email)
            ->setSubject("We'll stop recommending your profile to companies");

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
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
     * notify candidate kuwaiti mom Nationality update
     */
    public static function kuwaitiNationalityEmail()
    {
        $total = 0;

        $query = Candidate::find()
            ->andWhere(['candidate_email_verification' => true])
            ->verifiedProfile()
            ->candidateMomKuwaitiFieldIsNull();
            //->all();

        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                //if (!$candidate->candidate_email_verification)
                //    return false;

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

                    $ml = new MailLog();
                    $ml->to = $candidate->candidate_email;
                    $ml->from = \Yii::$app->params['supportEmail'];
                    $ml->subject = "Jobs in restaurants, cafes, and cinemas";
                    if (!$ml->save()) {
                        Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                    }

                    $mailer = Yii::$app->mailer->compose("candidate/kuwaiti-mom",
                        [
                            "logo" => Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                            "name" => $name,
                            "url" => $url
                        ])
                        ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
                        ->setTo($candidate->candidate_email)
                        ->setSubject("Jobs in restaurants, cafes, and cinemas");

                if(\Yii::$app->params['elasticMailIpPool']) {
                    $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
                }

                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                        // Handle email transport-specific exceptions
                        Yii::error( "Failed to send email: " . $e->getMessage());
                    } catch (\Exception $e) {
                        // Handle any other exceptions
                        Yii::error( "An error occurred: " . $e->getMessage());
                    }

                    //$total++;
                }
            }

            $total += sizeof($candidates);
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistories($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getLatestCandidateWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->andWhere(new Expression("end_date IS NULL"));
           // ->orderBy("start_date DESC");
    }

    /**
     * Revenue
     * @return string
     */
    public function getRevenue()
    {
        return (double) $this->getTransferCandidates ()
            ->filterPaymentReceived()
            ->sum ('transfer_candidate.company_total');
    }

    /**
     * Revenue
     * @return string
     */
    public function getProfit()
    {
        return (double) $this->getTransferCandidates ()
            ->filterPaymentReceived()
            ->sum ('transfer_candidate.company_total - transfer_candidate.candidate_total');
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

    /**
     * @return int[]|string[]|null
     */
    public function getPendingField() {
        return ($this->pendingProfile) ? array_keys($this->pendingProfile) : null;
    }

    /**
     * @return bool
     */
    public function getIsProfileCompleted() {
        return $this->isInCompleteProfile() ? false : true;
    }

    /**
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getIsWorking() {
        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere('end_time is null')
            ->one();

        if ($model) {
            return $model;
        }

        return null;
    }

    /**
     * @return false|void
     */
    public static function notifyCivilIDExpiring() {

        $query = Candidate::find()
            ->andWhere('DATE(candidate_civil_expiry_date) < DATE(NOW() + INTERVAL 25 DAY)')
            ->andWhere(['candidate_email_verification' => 1])
            ->andWhere('{{%candidate}}.store_id > 0');//to active students only, to filter old account with deleted/ invalid email

        $subject = "Civil ID is expiring";

        if(YII_ENV != 'prod') {
            $subject = '[Fake] [Ignore] ' . $subject;
        }

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $mailer = Yii::$app->mailer->compose("candidate/civil-id-expiring",
            [
                'logo' => Yii::$app->urlManagerStaff->createUrl(
                    '../images/logo.png'
                )
            ])
            ->setFrom([Yii::$app->params['finance_transfer'] => Yii::$app->params['appName']])
            ->setSubject($subject);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                if(!$candidate->candidate_email_verification)
                    return false;

                $ml = new MailLog();
                $ml->to = $candidate->candidate_email;
                $ml->from = \Yii::$app->params['finance_transfer'];
                $ml->subject = $subject;
                if (!$ml->save()) {
                    Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                }

                $mailer
                    ->setTo($candidate->candidate_email);

                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    // Handle email transport-specific exceptions
                    Yii::error( "Failed to send email: " . $e->getMessage());
                } catch (\Exception $e) {
                    // Handle any other exceptions
                    Yii::error( "An error occurred: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @return false|void
     */
    public static function notifyMissingBankInfo () {

        $query = self::find()
            ->andWhere(['candidate_email_verification' => 1])
            ->andWhere('{{%candidate}}.store_id > 0 && {{%candidate}}.bank_id IS NULL');

        $subject = "Bank information is missing";

        if(YII_ENV != 'prod') {
            $subject = '[Fake] [Ignore] ' . $subject;
        }

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $mailer = Yii::$app->mailer->compose("candidate/request-bank-information",
            [
                'logo' => Yii::$app->urlManagerStaff->createUrl(
                    '../images/logo.png'
                )
            ])
            ->setFrom([Yii::$app->params['finance_transfer'] => Yii::$app->params['appName']])
            ->setSubject($subject);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }
        
        foreach ($query->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                if(!$candidate->candidate_email_verification)
                    return false;

                $ml = new MailLog();
                $ml->to = $candidate->candidate_email;
                $ml->from = \Yii::$app->params['finance_transfer'];
                $ml->subject = $subject;
                if (!$ml->save()) {
                    Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
                }

                $mailer
                    ->setTo($candidate->candidate_email);

                try {
                    $mailer->send();
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    // Handle email transport-specific exceptions
                    Yii::error( "Failed to send email: " . $e->getMessage());
                } catch (\Exception $e) {
                    // Handle any other exceptions
                    Yii::error( "An error occurred: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWarnings($modelClass = "\common\models\CandidateWarning")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingHour($modelClass = "\common\models\CandidateWorkingHour")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingDates($modelClass = "\common\models\CandidateWorkingDate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getLatestCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingDate") {
        return self::getCandidateWorkingDates ($modelClass)
            ->one();
    }

    /**
     * @param $modelClass
     * @return bool|int|string|null
     */
    public function getTotalCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingDate") {
        return (int) self::getCandidateWorkingDates ($modelClass)
            ->count();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateStats($modelClass = "\common\models\CandidateStats")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEducations($modelClass = "\common\models\CandidateEducation")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterview($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->orderBy("created_at DESC");
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestInterviews($modelClass = "\common\models\RequestInterview")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id'])
            ->orderBy("created_at DESC");
    }

    /**
     * @param $console
     * @param $query
     * @return void
     */
    public static function updateCivilExpiry($console, $query) {

        $count = 0;

        $total = $query->count();

        Console::startProgress(0, $total);

        foreach ($query->batch(100) as $candidates) {
            foreach ($candidates as $candidate) {

                $count++;
                Console::updateProgress($count, $total);

                $candidate->setScenario("updateCivilExpiryDate");
                $candidate->validateCivilID();

                //if correct date was added

                /*if($dateTime == strtotime($candidate->candidate_civil_expiry_date)) {
                    continue;
                }*/

                if(
                    $candidate->candidate_civil_expiry_date &&
                    $candidate->save()
                ) {
                    //echo $candidate->candidate_civil_expiry_date ." for #" . $candidate->candidate_id . " \n";
                    //$console->stdout($candidate->candidate_civil_expiry_date ." for #" . $candidate->candidate_id . " \n");
                } else {
                    //echo "Got error for #" . $candidate->candidate_id . " \n";
                    $console->stdout("Got error for #" . $candidate->candidate_id . " \n",
                        Console::FG_RED);
                }
            }
        }
    }
}
