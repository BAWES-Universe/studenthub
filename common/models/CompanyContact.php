<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "company_contact".
 *
 * @property string $contact_uuid
 * @property int $company_id
 * @property string $contact_name
 * @property string $contact_position
 * @property string $contact_note
 * @property string $contact_created_datetime
 * @property string $contact_updated_datetime
 *
 * @property Company $company
 * @property CompanyContactEmail[] $companyContactEmails
 * @property CompanyContactPhone[] $companyContactPhones
 */
class CompanyContact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contact_name', 'contact_position', 'contact_created_datetime', 'contact_updated_datetime'], 'required'],
            [['company_id'], 'integer'],
            [['contact_note'], 'string'],
            [['contact_created_datetime', 'contact_updated_datetime'], 'safe'],
            [['contact_uuid'], 'string', 'max' => 60],
            [['contact_name', 'contact_position'], 'string', 'max' => 255],
            [['contact_uuid'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'contact_uuid',
                ],
                'value' => function() {
                    if(!$this->contact_uuid)
                        $this->contact_uuid = 'contact_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->contact_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'contact_created_datetime',
                'updatedAtAttribute' => 'contact_updated_datetime',
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
            'contact_uuid' => Yii::t('app', 'Contact ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'contact_name' => Yii::t('app', 'Contact Name'),
            'contact_position' => Yii::t('app', 'Contact Position'),
            'contact_note' => Yii::t('app', 'Contact Note'),
            'contact_created_datetime' => Yii::t('app', 'Contact Created Datetime'),
            'contact_updated_datetime' => Yii::t('app', 'Contact Updated Datetime'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactEmails($modelClass = "\staff\models\CompanyContactEmail")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactPhones($modelClass = "\staff\models\CompanyContactPhone")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
}
