<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "company_contact".
 *
 * @property string $company_contact_uuid
 * @property string $contact_uuid
 * @property int $company_id
 * @property string $contact_position
 * @property boolean $allow_access
 * @property string $created_at
 * @property string $updated_at
 * @property string $created_by
 * @property string $updated_by
 *
 * @property Company $company
 * @property Contact $contact
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
            [['company_id'], 'integer'],
            ['contact_position', 'string', 'max' => 100],
            [['created_at', 'updated_at'], 'safe'],
            ['company_id', 'unique', 'targetAttribute' => ['company_id', 'contact_uuid']],
            //['contact_uuid', 'unique', 'targetAttribute' => ['contact_uuid', 'company_id']],
            [['contact_uuid', 'created_by', 'updated_by'], 'string', 'max' => 60],
            ['allow_access', 'boolean'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Contact::className(), 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'company_contact_uuid',
                ],
                'value' => function() {
                    if(!$this->company_contact_uuid)
                        $this->company_contact_uuid = 'company_contact_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->company_contact_uuid;
                }
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
                'value' => function() {
                    if(isset(Yii::$app->user->identity->agent_uuid))
                        return Yii::$app->user->identity->agent_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
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
            'company_contact_uuid' => Yii::t('app', 'Company Contact Uuid'),
            'contact_uuid' => Yii::t('app', 'Contact Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'contact_position' => Yii::t('app', 'Contact Position'),
            'allow_access' => Yii::t('app', 'Allow access'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    public function extraFields()
    {
        return [
            'contact',
            'company',
            'contactEmails',
            'contactPhones'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\common\models\ContactEmail")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid'])
            ->via('contact');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\common\models\ContactPhone")
    {
        return $this->hasMany($modelClass::className(), ['contact_uuid' => 'contact_uuid'])
            ->via('contact');
    }
}
