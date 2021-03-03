<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "admin_token".
 *
 * @property integer $token_id
 * @property integer $admin_id
 * @property string $token_value
 * @property string $token_device
 * @property string $token_device_id
 * @property integer $token_status
 * @property string $token_last_used_datetime
 * @property string $token_expiry_datetime
 * @property string $token_created_datetime
 *
 * @property Admin $admin
 */
class AdminToken extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_EXPIRED = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['admin_id', 'token_value', 'token_status'], 'required'],
            [['token_value', 'token_device', 'token_device_id'], 'string', 'max' => 255],
            //[['admin_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::className(), 'targetAttribute' => ['admin_id' => 'admin_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['token_value']);

        return $fields;
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'token_created_datetime',
                'updatedAtAttribute' => false,
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
            'token_id' => Yii::t('app','Token ID'),
            'admin_id' => Yii::t('app','Admin ID'),
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
    public function getAdmin($modelClass = "\common\models\Admin")
    {
        return $this->hasOne($modelClass::className(), ['admin_id' => 'admin_id']);
    }
}
