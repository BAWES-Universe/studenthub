<?php

namespace common\models;

use company\models\Request;
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
 * @property string $staff_gmail_username
 * @property string $staff_gmail_password
 * @property string $staff_password_reset_token
 * @property number $staff_role
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
    const ROlE_CONSULTANT = 2;

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
            [['staff_role'], 'number'],
            [['staff_status','staff_notification'], 'integer'],
            [['staff_name', 'staff_email', 'staff_password_hash', 'staff_password_reset_token','staff_gmail_username','staff_gmail_password'], 'string', 'max' => 255],
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
            'staff_gmail_username' => Yii::t('app','Staff Gmail Username'),
            'staff_gmail_password' => Yii::t('app','Staff Gmail Password'),
            'staff_password_reset_token' => Yii::t('app','Staff Password Reset Token'),
            'staff_role' => Yii::t('app', 'Role'),
            'staff_status' => Yii::t('app','Staff Status'),
            'staff_notification' => Yii::t('app','Staff Notification'),
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

        unset(
            $fields['deleted'],
            $fields['staff_auth_key'],
            $fields['staff_password_hash'],
            $fields['staff_password_reset_token']
        );

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
            'totalInvitations' => function($model) {

                $start_date = Yii::$app->request->get('start_date');
                $end_date = Yii::$app->request->get('end_date');

                $query = $model->getInvitations();

                if($start_date) {
                    $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('".
                        date('Y-m-d', strtotime ($start_date)) ."')"));
                }

                if($end_date) {
                    $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('".
                        date('Y-m-d', strtotime ($end_date))."')"));
                }

                return (int) $query
                    ->count();
            },
            'timeForCompletedRequests' => function($model) {
                $start_date = Yii::$app->request->get('start_date');
                $end_date = Yii::$app->request->get('end_date');

                $query = $model->getRequests()
                    ->andWhere(['request_status' => Request::STATUS_DELIVERED]);

                if($start_date) {
                    $query->andWhere(new Expression("DATE(request_started_at) >= DATE('".
                        date('Y-m-d', strtotime ($start_date)) ."')"));
                }

                if($end_date) {
                    $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('".
                        date('Y-m-d', strtotime ($end_date))."')"));
                }

                return (int) $query
                    ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_delivered_at)'));
            },
            'timeForCancelledRequests' => function($model) {
                $start_date = Yii::$app->request->get('start_date');
                $end_date = Yii::$app->request->get('end_date');

                $query = $model->getRequests()
                    ->andWhere(['request_status' => Request::STATUS_CANCELLED]);

                if($start_date) {
                    $query->andWhere(new Expression("DATE(request_started_at) >= DATE('".
                        date('Y-m-d', strtotime ($start_date)) ."')"));
                }

                if($end_date) {
                    $query->andWhere(new Expression("DATE(request_cancelled_at) <= DATE('".
                        date('Y-m-d', strtotime ($end_date))."')"));
                }

                return (int) $query
                    ->sum(new Expression('TIMESTAMPDIFF(SECOND, request_started_at, request_cancelled_at)'));
            },
            /*'totalRequests' => function($model) {
                return $model->getRequests()->count();
            }*/
        ];
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
     * return total pending requests by staff
     * @return int
     */
    public function getTotalPendingRequests()
    {
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = $this->getRequests ()
            ->andWhere(['not in', 'request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_CANCELLED
            ]]);

        if($start_date) {
            $query->andWhere(new Expression("DATE(request_started_at) >= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }

        if($end_date) {
            $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('".
                date('Y-m-d', strtotime ($end_date))."')"));
        }

        return (int) $query
            ->count ();
    }

    /**
     * return total completed requests by staff
     * @return int
     */
    public function getTotalClosedRequests()
    {
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = $this->getRequests ()
            ->andWhere(['in', 'request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_CANCELLED
            ]]);

        if($start_date) {
            $query->andWhere(new Expression("DATE(request_started_at) >= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }

        if($end_date) {
            $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('".
                date('Y-m-d', strtotime ($end_date))."')"));
        }

        return (int) $query
            ->count ();
    }

    /**
     * return total completed requests by staff
     * @return int
     */
    public function getTotalCompletedRequests()
    {
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = $this->getRequests ()
            ->andWhere(['request_status' => Request::STATUS_DELIVERED]);

        if($start_date) {
            $query->andWhere(new Expression("DATE(request_started_at) >= DATE('".
                date('Y-m-d', strtotime ($start_date)) ."')"));
        }

        if($end_date) {
            $query->andWhere(new Expression("DATE(request_delivered_at) <= DATE('".
                date('Y-m-d', strtotime ($end_date))."')"));
        }

        return (int) $query
            ->count ();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation")
    {
        return $this->hasMany($modelClass::className(), ['invitation_created_by_staff' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\common\models\Request")
    {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['created_by' => 'staff_id']);
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
        $token = StaffToken::find()
            ->andWhere(['token_value' => $token])
            ->with('staff')
            ->one();

        if($token) {
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
            'deleted' => 0
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

        //remove unique fields, so can create new account with same details

        $this->staff_email = 'deleted at ' . time() . '-' . $this->staff_email;
        $this->staff_password_reset_token = null;

        if ($this->save(false)) {
            return StaffToken::deleteAll(['staff_id'=>$this->staff_id]);
        }
        return false;
    }

    /**
     * @inheritdoc
     * @return query\StaffQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\StaffQuery(get_called_class());
    }

    public static function encryptPass($string) {

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

    public static function decryptPass($string) {
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
}
