<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "contact_invitation".
 *
 * @property string $contact_invitation_uuid
 * @property string $contact_uuid
 * @property string $company_id
 * @property string $email_to_invite
 * @property number $role
 * @property string $otp
 * @property string $created_at
 * @property string $updated_at
 *
 * @property contact $contact
 * @property Company $company
 */
class ContactInvitation extends \yii\db\ActiveRecord {

    //accepted field's values 
    const ACCEPTED_TRUE = 1;
    const ACCEPTED_FALSE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'contact_invitation';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['is_deleted'], 'integer'],
            [['accepted'], 'number'],
            [['contact_uuid', 'email_to_invite', 'company_id'], 'required'],
            [['created_at', 'updated_at', 'accepted'], 'safe'],
            [['email_to_invite','role'], 'string'],
            [
                ['contact_uuid'],
                'exist', 
                'skipOnError' => true, 
                'targetClass' => Contact::className(),
                'targetAttribute' => ['contact_uuid' => 'contact_uuid']
            ],
            [['company_id'], 'validateCompany'],
            [['email_to_invite'], 'validateEmail'],
            [['otp'], 'string', 'max' => 60],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'contact_invitation_uuid',
                ],
                'value' => function() {
                    if(!$this->contact_invitation_uuid)
                        $this->contact_invitation_uuid = 'cont_inv_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    
                    return $this->contact_invitation_uuid;
                }
            ],
        ];
    }

    /**
     * Agent should not able to send invitation for other employer he not own
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function validateCompany($attribute, $params, $validator) {
        $model = CompanyContact::findOne([
            'contact_uuid' => $this->contact_uuid,
            'company_id' => $this->company_id
        ]);

        if (!$model) {
            $this->addError($attribute, Yii::t('app', 'Company not found'));
        }
    }

    /**
     * validate invite email address
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function validateEmail($attribute, $params, $validator) {

        $error = false;
        $emails = explode(',', $this->email_to_invite);
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = true;
            }
        }

        if ($error) {
            $this->addError($attribute, Yii::t('app', 'Please enter valid email address(s)'));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'contact_invitation_uuid' => Yii::t('app','Contact Invitation UUID'),
            'contact_uuid' => Yii::t('app','Contact UUID'),
            'company_id' => Yii::t('app','Company ID'),
            'email_to_invite' => Yii::t('app','Email To Invite'),
            'role' =>Yii::t('app', 'Role'), 
            'otp' =>Yii::t('app', 'OTP'), 
            'accepted' => Yii::t('app','Accepted'),
            'created_at' => Yii::t('app','Created At'),
            'updated_at' => Yii::t('app','Updated At'),
        ];
    }
    
    /**
     * Validate this agents otp against supplied OTP 
     * @param  string $otp
     * @return boolean Whether OTP is valid or not
     */
    public function validateOtp($otp) {
        return $this->getOtp() === $otp;
    }

    /**
     * generate otp(one time password) to enable user to get register 
     * without email verification 
     */
    public function generateOtp() {
        $this->otp = $this->generateUniqueRandomString('otp');
    }
    
    /**
     * Generate unique string for a given attribute of given length
     * @param $attribute
     * @param int $length
     * @return string
     * @throws \yii\base\Exception
     */
    public function generateUniqueRandomString($attribute, $length = 32) {
        $randomString = Yii::$app->getSecurity()->generateRandomString($length);

        if (!$this->findOne([$attribute => $randomString]))
            return $randomString;
        else
            return $this->generateUniqueRandomString($attribute, $length);
    }

    /**
     * @inheritdoc
     */
    public function extraFields() {
        return [
            'contact',
            'company'
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['otp']);

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitedContact($modelClass = '\common\models\Contact')
    {
        return $this->hasOne($modelClass::className(), ['contact_email' => 'email_to_invite']);
    }
    
    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = '\common\models\Contact') {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = '\common\models\Company') {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return query\ContactInvitationQuery
     */
    public static function find() {
        return new query\ContactInvitationQuery(get_called_class());
    }
}
