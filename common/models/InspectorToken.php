<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "inspector_token".
 *
 * @property integer $token_uuid
 * @property integer $inspector_uuid
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
class InspectorToken extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_EXPIRED = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'inspector_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['inspector_uuid', 'token_value', 'token_status'], 'required'],
            [['token_value', 'token_device', 'token_device_id'], 'string', 'max' => 255],
            //[['admin_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['admin_id' => 'admin_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'token_uuid',
                ],
                'value' => function() {
                    if(!$this->token_uuid)
                        $this->token_uuid = 'insp_token_'. Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->token_uuid;
                }
            ],
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
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['token_value']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'token_uuid' => Yii::t('app','Token ID'),
            'inspector_uuid' => Yii::t('app','Inspector ID'),
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
    public function getInspector($modelClass = '\common\models\Inspector')
    {
        return $this->hasOne($modelClass::className(), ['inspector_uuid' => 'inspector_uuid']);
    }
}
