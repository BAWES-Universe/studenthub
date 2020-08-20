<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "company_contact_email".
 *
 * @property string $email_uuid
 * @property string $contact_uuid
 * @property string $email_address
 * @property string $email_created_datetime
 * @property string $email_updated_datetime
 *
 * @property CompanyContact $contactUu
 */
class CompanyContactEmail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_contact_email';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email_address', 'email_created_datetime', 'email_updated_datetime'], 'required'],
            [['email_created_datetime', 'email_updated_datetime'], 'safe'],
            [['email_uuid', 'contact_uuid'], 'string', 'max' => 60],
            [['email_address'], 'string', 'max' => 255],
            [['email_uuid'], 'unique'],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyContact::className(), 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'email_uuid',
                ],
                'value' => function() {
                    if(!$this->email_uuid)
                        $this->email_uuid = 'email_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->email_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'email_created_datetime',
                'updatedAtAttribute' => 'email_updated_datetime',
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
            'email_uuid' => Yii::t('app', 'Email ID'),
            'contact_uuid' => Yii::t('app', 'Contact ID'),
            'email_address' => Yii::t('app', 'Email Address'),
            'email_created_datetime' => Yii::t('app', 'Email Created Datetime'),
            'email_updated_datetime' => Yii::t('app', 'Email Updated Datetime'),
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
