<?php

namespace common\models;

use company\models\Request;
use common\helpers\DeviceDetector;
use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "staff".
 *
 * @property integer $staff_id
 * @property string $staff_name
 * @property string $staff_job_title
 * @property string $staff_email
 * @property string $staff_auth_key
 * @property string $staff_password_hash
 * @property string $staff_gmail_username
 * @property string $staff_gmail_password
 * @property string $staff_password_reset_token
 * @property number $staff_role
 * @property number $staff_salary
 * @property number $staff_salary_currency
 * @property string $staff_photo
 * @property integer $week_start_day
 * @property integer $work_days
 * @property integer $hours_per_day
 * @property integer $staff_status
 * @property integer $staff_notification
 * @property integer $staff_created_at
 * @property integer $staff_updated_at
 * @property integer $deleted
 *
 * @property StaffToken[] $accessTokens
 */
class Staff extends ActiveRecord implements IdentityInterface
{
    const ROlE_MANAGER = 1;
    const ROLE_ENGINEER = 2;
    const ROlE_SALE = 3;
    const ROlE_BD_PR = 4;
    const ROlE_MARKETING = 5;
    const ROlE_RECRUITER = 6;
    const ROlE_FINANCE = 7;
    const ROlE_HR = 8;
    const ROlE_CUSTOMER_CARE = 9;

    const STATUS_ACTIVE = 10;

    const ACCESS_LIMITED = 1;
    const ACCESS_FULL = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'staff';
    }

    /**
     * @return float|int
     */
    public static function getTotalNoOfHours()
    {
        $timeForCompletedRequests = (int)Request::find()
            ->andWhere(new Expression('staff_id IS NOT NULL'))
            ->andWhere(['request_status' => Request::STATUS_DELIVERED])
            ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_delivered_at)'));

        $timeForCancelledRequests = (int)Request::find()
            ->andWhere(new Expression('staff_id IS NOT NULL'))
            ->andWhere(['request_status' => Request::STATUS_CANCELLED])
            ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_delivered_at)'));

        return ($timeForCancelledRequests + $timeForCompletedRequests) / 3600;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['staff_id' => $id]);
    }

    /**
     * Finds staff by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['staff_email' => $email, 'deleted' => 0, 'staff_status' => self::STATUS_ACTIVE]);
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
            'staff_password_reset_token' => $token,
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
        $timestamp = (int)end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     * @return query\StaffQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\StaffQuery(get_called_class());
    }

    /**
     * @param $string
     * @return false|string
     */
    public static function encryptPass($string)
    {

        // Store the cipher method
        $ciphering = "AES-128-CTR";

        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';

        // Store the encryption key
        $encryption_key = "GeeksforGeeks";

        // Use openssl_encrypt() function to encrypt the data
        return openssl_encrypt($string, $ciphering,
            $encryption_key, $options, $encryption_iv);
    }

    public static function decryptPass($string)
    {
        // Store the cipher method
        $ciphering = "AES-128-CTR";

        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Non-NULL Initialization Vector for decryption
        $decryption_iv = '1234567891011121';

        // Store the decryption key
        $decryption_key = "GeeksforGeeks";

        // Use openssl_decrypt() function to decrypt the data
        return openssl_decrypt($string, $ciphering,
            $decryption_key, $options, $decryption_iv);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['staff_name', 'staff_job_title', 'staff_email'], 'required'],
            [['staff_password_hash'], 'required', 'on' => 'newAccount'],
            [['staff_role', 'staff_hourly_rate', 'staff_salary'], 'number'],
            [['staff_status', 'staff_notification', 'week_start_day', 'work_days', 'hours_per_day'], 'integer'],
            [['staff_name', 'staff_email', 'staff_password_hash', 'staff_password_reset_token', 'staff_gmail_username', 'staff_gmail_password', 'staff_photo'], 'string', 'max' => 255],
            [['staff_auth_key', 'staff_salary_currency'], 'string', 'max' => 32],
            [['staff_email'], 'unique'],
            [['staff_email'], 'email'],
            [['enable_two_step_auth'], 'safe'],
            [['staff_password_reset_token'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'staff_created_at',
                'updatedAtAttribute' => 'staff_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (Yii::$app->request instanceof \yii\web\Request) {

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
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'staff_id' => Yii::t('app', 'Staff ID'),
            'staff_name' => Yii::t('app', 'Staff Name'),
            'staff_job_title' => Yii::t('app', 'Staff Job Title'),
            'staff_email' => Yii::t('app', 'Staff Email'),
            'staff_auth_key' => Yii::t('app', 'Staff Auth Key'),
            'staff_password_hash' => Yii::t('app', 'Password'),
            'staff_gmail_username' => Yii::t('app', 'Staff Gmail Username'),
            'staff_gmail_password' => Yii::t('app', 'Staff Gmail Password'),
            'staff_password_reset_token' => Yii::t('app', 'Staff Password Reset Token'),
            'staff_role' => Yii::t('app', 'Role'),
            'staff_salary' => Yii::t('app', 'Salary'),
            'staff_photo' => Yii::t('app', 'Staff Photo'),
            'staff_salary_currency' => Yii::t('app', 'Salary currency'),
            'week_start_day' => Yii::t('app', 'Week start day'),
            'work_days' => Yii::t('app', 'Work days'),
            'hours_per_day' => Yii::t('app', 'Hours per day'),
            'staff_status' => Yii::t('app', 'Staff Status'),
            'staff_notification' => Yii::t('app', 'Staff Notification'),
            'staff_created_at' => Yii::t('app', 'Staff Created At'),
            'staff_updated_at' => Yii::t('app', 'Staff Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['staff_auth_key'],
            $fields['staff_password_hash'],
            $fields['staff_password_reset_token']
        );

        $fields['staff_salary'] = function ($model) {
            // only for meet and khalid..
            // TODO will change to permission base
            $id = Yii::$app->user->getId();
            if ($id == 1 || $id == 7 || $id == 10) {
                return $model->staff_salary;
            }
            return 0.0;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'totalCompletedRequests',
            'totalClosedRequests',
            'totalPendingRequests',
            'staffNotifications',
            "totalAssigned",
            'totalInvitations',
            'timeForCompletedRequests',
            'timeForCancelledRequests',
            'totalCompletedStories',
            'totalStoryEmployees',
            'timeForCompletedStories',
            'permissions',
            'companies'
            /*'totalRequests' => function($model) {
                return $model->getRequests()->count();
            }*/
        ];
    }

    /**
     * @return int
     */
    public function getTimeForCancelledRequests($conditions = []) {

        $query = $this->getRequests()
            ->andWhere(['request_status' => \common\models\Request::STATUS_CANCELLED]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if(isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_started_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_cancelled_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int) $query
            ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_cancelled_at)'));
    }

    /**
     * @return int
     */
    public function getTimeForCompletedRequests($conditions = []) {

        $query = $this->getRequests()
            ->filterCompleted();
        //->andWhere(['request_status' => Request::STATUS_DELIVERED]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if(isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_started_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_delivered_at)'));
    }

    /**
     * @return int
     */
    public function getTotalInvitations($conditions = []) {

        $query = $this->getInvitations();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if(isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->count();
    }

    /**
     * @return mixed
     */
    public function getTotalCompletedStories($conditions = []) {

        $query = $this->getStories()
            ->filterCompleted();
        //->andWhere(['story_status' => Story::STATUS_DELIVERED]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if(isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(story_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return $query->count();
    }

    /**
     * @return int
     */
    public function getTotalStoryEmployees($conditions = []) {

        $query = $this->getStories()
            //->joinWith(['request'], 'left')
            //->andWhere(['story_status' => Story::STATUS_DELIVERED]);
            ->filterCompleted();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(story_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query->sum('story.number_of_employees');
    }

    /**
     * @return int
     */
    public function getTimeForCompletedStories($conditions = []) {

        $query = $this->getStoryActivities()
            ->joinWith(['story'], 'left')
            ->andWhere(['activity_status' => StoryActivity::STATUS_DELIVERED]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(story_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->sum('story_activity.activity_time_spent');
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void|null
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (YII_ENV != 'prod') {
            return null;
        }

        if ($insert) {
            Yii::$app->eventManager->track(
                'Staff Created v2',
                [
                    "staff_name" => $this->staff_name,
                    "staff_email" => $this->staff_email
                ]
            );
        } else {
            Yii::$app->eventManager->track(
                'Staff Updated v2',
                [
                    "staff_name" => $this->staff_name,
                    "staff_email" => $this->staff_email
                ]
            );
        }
    }

    /**
     * Set logo from S3 temp url
     * @param string $url
     */
    public function setLogo($staff_photo)
    {

        if (!Yii::$app->temporaryBucketResourceManager->fileExists($staff_photo)) {
            $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }

        $url = Yii::$app->temporaryBucketResourceManager->getUrl($staff_photo);

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->staff_photo) {
            $this->deleteLogoFromCloudinary();
        }

        try {
            $path = (YII_ENV == 'prod') ? "staff-photo/" : "dev/staff-photo/";
            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' => $path . $filename,
                    "eager" => [
                        [
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->staff_photo = basename($result['url']);
                return true;
            }

        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('staff_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old logo from cloudinary
     * @return boolean
     */
    public function deleteLogoFromCloudinary()
    {

        try {
            $path = (YII_ENV == 'prod') ? "staff-photo/" : "dev/staff-photo/";
            $response = Yii::$app->cloudinaryManager->delete($path . $this->staff_photo);
            if ($response && $response['result'] == 'not found') {
                $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));
                return false;
            }
        } catch (\Cloudinary\Exception\Error $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * return total pending requests by staff
     * @return int
     */
    public function getTotalPendingRequests($conditions = [])
    {
        $query = $this->getRequests()
            ->andWhere(['not in', 'request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_CANCELLED
            ]]);

        if ($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_started_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\common\models\Request")
    {
        return $this->hasMany($modelClass::className(), ['request_created_by' => 'staff_id']);
    }

    /**
     * return total completed requests by staff
     * @return int
     */
    public function getTotalClosedRequests($conditions = [])
    {
        $query = $this->getRequests()
            ->andWhere(['in', 'request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_CANCELLED
            ]]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_started_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }


        return (int)$query
            ->count();
    }

    /**
     * return total completed requests by staff
     * @return int
     */
    public function getTotalCompletedRequests($conditions = [])
    {
        $query = $this->getRequests()
            ->andWhere(['request_status' => Request::STATUS_DELIVERED]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_started_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->count();
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\StaffToken")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }


    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStoryActivities($modelClass = "\common\models\StoryActivity")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaffSalaries($modelClass = "\common\models\StaffSalary")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    public function getCurrentStory()
    {
        return Story::find()->andWhere(['story.staff_id' => Yii::$app->user->getId(), 'story.story_status' => Story::STATUS_STARTED])
            ->joinWith(['request', 'company'])
            ->asArray()
            ->one();
    }

    /**
     * Signs user up.
     * @return static|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            
            if ($this->staff_password_hash) {
                $this->setPassword($this->staff_password_hash);
            }

            $this->generateAuthKey();
            $this->save(false);

            Yii::info("[New Staff Account Created] " . $this->staff_email, __METHOD__);

            return $this;
        }
        return null;
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->staff_password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key [1 time use token]
     */
    public function generateAuthKey()
    {
        $this->staff_auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @param string $authKey
     * @return bool
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @return string
     */
    public function getAuthKey()
    {
        return $this->staff_auth_key;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        if (!$this->staff_password_hash) {
            return null;
        }

        return Yii::$app->security->validatePassword($password, $this->staff_password_hash);
    }

    /**
     * Generate, save, and return an auth key for this account [1 time use token]
     * @return string
     */
    public function generateAuthKeyAndSave()
    {
        $this->generateAuthKey();
        $this->save(false);

        return $this->staff_auth_key;
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->staff_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->staff_password_reset_token = null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $authType = HttpBearerAuth::class, $type = StaffToken::STATUS_ACTIVE, $otp = null)
    {
        //\staff\models\
        $token = StaffToken::find()
            ->andWhere([
                'token_value' => $token,
                "token_status" => $type
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->with('staff')
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

        $token->token_status = StaffToken::STATUS_ACTIVE;//make inactive token to active on found with OTP
        $token->token_last_used_datetime = new Expression('NOW()');
        $token->save();

        //should not able to login, if email not verified but have valid token

        if ($token->staff) {//&& $token->staff->email_verification
            return $token->staff;
        }

        //invalid token

        $token->delete();
    }

    /**
     * Create an Access Token Record for this Staff
     * if the staff user already has one, it will return it instead
     * @return \common\models\StaffToken
     */
    public function getAccessToken($type = StaffToken::STATUS_ACTIVE)
    {
        /*$token = StaffToken::find()
            ->andWhere([
                'staff_id' => $this->staff_id,
                'token_status' => StaffToken::STATUS_ACTIVE
            ])
            ->andWhere(new Expression("token_expiry_datetime IS NULL OR 
                token_expiry_datetime > NOW()"))
            ->one();

        if ($token) {
            return $token;
        }*/

        $detect = new DeviceDetector();

        $device = "Desktop Device";

        if ($detect->isMobile()) {
            $device = "Mobile Device";
        } elseif ($detect->isTablet()) {
            $device = "Tablet Device";
        }

        // Create new inactive token
        $token = new StaffToken();
        $token->staff_id = $this->staff_id;
        $token->token_value = StaffToken::generateUniqueTokenString();
        $token->token_status = $type;
        $token->token_device = $device;
        $token->token_device_id = mb_strimwidth( $detect->getUserAgent(), 0, 250, "...");
        $token->token_expiry_datetime = date('Y-m-d H:i:s', strtotime("+1 month"));
       // $token->ip_address = isset(Yii::$app->params['user_ip_address']) ?
       //     Yii::$app->params['user_ip_address']: Yii::$app->request->getRemoteIP();
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
     * Send OTP mail to staff
     * @param \common\models\StaffToken $token
     * @return bool
     */
    public function sendOTPMail($token) {

        //generate OTP
        $token->otp = Yii::$app->security->generateRandomString(4);
        if (!$token->save()) {
            Yii::error("Error saving token : ". print_r($token->errors, true));
        }

        $ml = new MailLog();
        $ml->to = $this->staff_email;   
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'OTP for 2 step verification';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("staff/staff-otp", 
            [
                "model" => $this,
                "otp" => $token->otp,
                'logo_1' =>  Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->staff_email)
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
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        $this->staff_status = 0;
        //remove unique fields, so can create new account with same details

        $this->staff_email = 'deleted at ' . time() . '-' . $this->staff_email;
        $this->staff_password_reset_token = null;

        if ($this->save(false)) {
            return StaffToken::deleteAll(['staff_id' => $this->staff_id]);
        }
        return false;
    }

    /**
     * @return bool|int|string|null
     */
    public function getTotalAssigned($conditions = [])
    {
        $query = $this->getCandidateWorkHistories();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date', null);
            $end_date = Yii::$app->request->get('end_date', null);

            if ($start_date) {
                $query->andWhere(new Expression("DATE(start_date) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
            if ($end_date) {
                $query->andWhere(new Expression("DATE(start_date) <= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
        }

        return $query->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistories($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return int
     */
    public function getTotalRequests($conditions = [])
    {
        $query = $this->getRequests();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date', null);
            $end_date = Yii::$app->request->get('end_date', null);

            if ($start_date) {
                $query->andWhere(new Expression("DATE(request_created_datetime) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
            if ($end_date) {
                $query->andWhere(new Expression("DATE(request_created_datetime) <= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
        }

        return (int) $query->count();
    }

    /**
     * @return int
     */
    public function getTotalNotes($conditions = [])
    {
        $query = $this->getNotes();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date', null);
            $end_date = Yii::$app->request->get('end_date', null);

            if ($start_date) {
                $query->andWhere(new Expression("DATE(note_created_datetime) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
            if ($end_date) {
                $query->andWhere(new Expression("DATE(note_created_datetime) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int) $query->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['created_by' => 'staff_id']);
    }

    /**
     * @return int
     */
    public function getTotalStories($conditions = [])
    {
        $query = $this->getStories();

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date', null);
            $end_date = Yii::$app->request->get('end_date', null);

            if ($start_date) {
                $query->andWhere(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
            if ($end_date) {
                $query->andWhere(new Expression("DATE(story_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }
        }

        return (int) $query->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStories($modelClass = "\common\models\Story")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return int
     */
    public function getTotalAcceptedInvitations($conditions = [])
    {
        $query = $this->getInvitations()
            ->andWhere(['invitation_status' => 3]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation")
    {
        return $this->hasMany($modelClass::className(), ['invitation_created_by_staff' => 'staff_id']);
    }

    /**
     * @return int
     */
    public function getTotalRejectedInvitations($conditions = [])
    {
        $query = $this->getInvitations()
            ->andWhere(['invitation_status' => 2]);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int)$query
            ->count();
    }

    /**
     * @return int
     */
    public function getTotalSuggestions($conditions = [])
    {
        $query = $this->getNotes()
            ->andWhere(['note_type' => 'Suggested']);

        if($conditions) {
            $query->andWhere($conditions);
        }

        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {

            $start_date = Yii::$app->request->get('start_date');
            $end_date = Yii::$app->request->get('end_date');

            if ($start_date) {
                $query->andWhere(new Expression("DATE(note_created_datetime) >= DATE('" .
                    date('Y-m-d', strtotime($start_date)) . "')"));
            }

            if ($end_date) {
                $query->andWhere(new Expression("DATE(note_created_datetime) <= DATE('" .
                    date('Y-m-d', strtotime($end_date)) . "')"));
            }
        }

        return (int) $query
            ->count();
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\common\models\Suggestion")
    {
        return $this->hasMany($modelClass::className(), ['created_by' => 'staff_id'])
            ->via('notes');
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getDailyStandupAnswers($modelClass = "\common\models\DailyStandupAnswer")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getPermissions($modelClass = "\common\models\PermissionUser")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return array|ActiveRecord|null
     */
    public function getActiveSession()
    {
        return StaffWorkSession::find()
            ->andWhere([
                'staff_id' => Yii::$app->user->getId()
            ])
            ->andWhere(new Expression("DATE(created_at) = DATE('" . date('Y-m-d') . "') 
                AND total_minutes IS NULL"))
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaffNotifications($modelClass = "\common\models\StaffNotification")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
