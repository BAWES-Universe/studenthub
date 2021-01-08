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
 * @property string $contact_name
 * @property string $contact_position

 * @property string $contact_email
 * @property string $contact_password_hash
 * @property string $contact_auth_key
 * @property string $contact_receive_email
 * @property string $contact_receive_notification

 * @property string $contact_created_at
 * @property string $contact_updated_at
 *
 * @property Company $company
 * @property CompanyContactEmail[] $companyContactEmails
 * @property CompanyContactPhone[] $companyContactPhones
 */
class Contact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contact_name', 'contact_position'], 'required'],
            [['contact_created_datetime', 'contact_updated_datetime'], 'safe'],
            [['contact_uuid'], 'string', 'max' => 60],
            [['contact_name', 'contact_position', 'contact_password_reset_token',], 'string', 'max' => 255],
            [['contact_uuid'], 'unique'],
            [['contact_password_reset_token'], 'unique'],
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
                'createdAtAttribute' => 'contact_created_at',
                'updatedAtAttribute' => 'contact_updated_at',
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
            'contact_receive_email' => Yii::t('app','Receive Email?'),
            'contact_receive_notification' => Yii::t('app','Receive Notification?'),
            'contact_auth_key' => Yii::t('app','Auth Key'),
            'contact_password_hash' => Yii::t('app','Password'),
            'contact_password_reset_token' => Yii::t('app','Password Reset Token'),
            'contact_created_datetime' => Yii::t('app', 'Contact Created Datetime'),
            'contact_updated_datetime' => Yii::t('app', 'Contact Updated Datetime'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted'],
            $fields['contact_password_hash'],
            $fields['contact_password_reset_token'],
            $fields['contact_auth_key']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'companies',
            'requests',
            'contactEmails',
            'contactPhones',
            'notes',
            'contactStats'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\common\models\CompanyContact")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\common\models\Company")
    {
        return $this->hasMany($modelClass::className(), ['company_id' => 'company_id'])
            ->via('companyContacts');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\common\models\ContactEmail")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\common\models\ContactPhone")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\common\models\Request")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid'])
            ->orderBy('note_updated_datetime DESC');
    }

    /**
     * @return array
     */
    public function getContactStats() {
        return [
            'contactEmails' => $this->getContactEmails()->count(),
            'contactPhones' => $this->getContactPhones()->count(),
            'requests' => $this->getRequests()->count(),
            'notes' => $this->getNotes()->count(),
            'lastNotes' => $this->getNotes()->orderBy('note_updated_datetime DESC')->one(),
        ];
    }
}
