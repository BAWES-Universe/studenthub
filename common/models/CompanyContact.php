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
 * @property string $role Owner,HR,Finance,Other
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
    const ROLE_OWNER = 'Owner';
    const ROLE_HR = 'HR';
    const ROLE_FINANCE = 'Finance';
    const ROLE_OTHER = 'Other';

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
            [['created_at', 'updated_at'], 'safe'],
            [['contact_uuid', 'created_by', 'updated_by'], 'string', 'max' => 60],

            ['candidate_status', 'default', 'value' => self::ROLE_OWNER],
            ['role', 'in', 'range' => [self::ROLE_OWNER, self::ROLE_HR, self::ROLE_FINANCE, self::ROLE_OTHER]],

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
            'role' => Yii::t('app', 'Role'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
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
}
