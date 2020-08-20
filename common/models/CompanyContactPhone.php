<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "company_contact_phone".
 *
 * @property string $phone_uuid
 * @property string $contact_uuid
 * @property string $phone_number
 * @property string $phone_created_datetime
 * @property string $phone_updated_datetime
 *
 * @property CompanyContact $contactUu
 */
class CompanyContactPhone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_contact_phone';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone_number', 'phone_created_datetime', 'phone_updated_datetime'], 'required'],
            [['phone_created_datetime', 'phone_updated_datetime'], 'safe'],
            [['phone_uuid', 'contact_uuid'], 'string', 'max' => 60],
            [['phone_number'], 'string', 'max' => 255],
            [['phone_uuid'], 'unique'],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyContact::className(), 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'phone_uuid',
                ],
                'value' => function() {
                    if(!$this->phone_uuid)
                        $this->phone_uuid = 'phone_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->phone_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'phone_created_datetime',
                'updatedAtAttribute' => 'phone_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone_uuid' => Yii::t('app', 'Phone ID'),
            'contact_uuid' => Yii::t('app', 'Contact ID'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'phone_created_datetime' => Yii::t('app', 'Phone Created Datetime'),
            'phone_updated_datetime' => Yii::t('app', 'Phone Updated Datetime'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\staff\models\CompanyContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
}
