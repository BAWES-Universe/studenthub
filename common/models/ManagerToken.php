<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "manager_token".
 *
 * @property string $token_uuid
 * @property string $store_manager_uuid
 * @property string $token_value
 * @property string $token_device
 * @property string $token_device_id
 * @property int $token_status
 * @property string $token_last_used_datetime
 * @property string $token_expiry_datetime
 * @property string $token_created_datetime
 *
 * @property StoreManager $storeManagerUu
 */
class ManagerToken extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_EXPIRED = 5;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'manager_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'token_value', 'token_status', 'token_created_datetime'], 'required'],
            //'token_uuid',
            [['token_status'], 'integer'],
            [['token_last_used_datetime', 'token_expiry_datetime', 'token_created_datetime'], 'safe'],
            [['token_uuid', 'store_manager_uuid'], 'string', 'max' => 60],
            [['token_value', 'token_device', 'token_device_id'], 'string', 'max' => 255],
            [['token_uuid'], 'unique'],
            [['store_manager_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => StoreManager::className(), 'targetAttribute' => ['store_manager_uuid' => 'store_manager_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'token_uuid' => Yii::t('app', 'Token Uuid'),
            'store_manager_uuid' => Yii::t('app', 'Store Manager Uuid'),
            'token_value' => Yii::t('app', 'Token Value'),
            'token_device' => Yii::t('app', 'Token Device'),
            'token_device_id' => Yii::t('app', 'Token Device ID'),
            'token_status' => Yii::t('app', 'Token Status'),
            'token_last_used_datetime' => Yii::t('app', 'Token Last Used Datetime'),
            'token_expiry_datetime' => Yii::t('app', 'Token Expiry Datetime'),
            'token_created_datetime' => Yii::t('app', 'Token Created Datetime'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManager()
    {
        return $this->hasOne(StoreManager::className(), ['store_manager_uuid' => 'store_manager_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStoreManager()
    {
        return $this->hasOne(StoreManager::className(), ['store_manager_uuid' => 'store_manager_uuid']);
    }
}
