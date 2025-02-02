<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_token".
 *
 * @property integer $token_id
 * @property integer $staff_id
 * @property string $token_value
 * @property string $token_device
 * @property string $token_device_id
 * @property integer $token_status
 * @property string $token_last_used_datetime
 * @property string $token_expiry_datetime
 * @property string $ip_address
 * @property string $token_created_datetime
 *
 * @property Staff $staff
 */
class StaffToken extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_EXPIRED = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'staff_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['staff_id', 'token_value', 'token_status'], 'required'],
         //   [['ip_address'], 'string', 'max' => 45],
            [['ip_address', 'otp', 'total_attempt'], 'safe'],
            [['token_value', 'token_device', 'token_device_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['token_value']
        );

        return $fields;
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'token_created_datetime',
                'updatedAtAttribute' => 'token_last_used_datetime',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function afterFind() {
        $this->token_last_used_datetime =  new Expression('NOW()');
        $this->save(false);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'token_id' => Yii::t('app','Token ID'),
            'staff_id' => Yii::t('app','Staff ID'),
            'token_value' => Yii::t('app','Token Value'),
            'token_device' => Yii::t('app','Token Device'),
            'token_device_id' => Yii::t('app','Token Device ID'),
            'token_status' => Yii::t('app','Token Status'),
            'token_last_used_datetime' => Yii::t('app','Token Last Used Datetime'),
            'token_expiry_datetime' => Yii::t('app','Token Expiry Datetime'),
            'token_created_datetime' => Yii::t('app','Token Created Datetime'),
        ];
    }

    /**
     * Generates unique access token to be used as value
     * @return string
     */
    public static function generateUniqueTokenString(){
        $randomString = Yii::$app->getSecurity()->generateRandomString();
        if(!static::findOne(['token_value' => $randomString ])){
            return $randomString;
        }else return static::generateUniqueTokenString();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }
}
