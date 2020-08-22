<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "note".
 *
 * @property string $note_uuid
 * @property integer $company_id
 * @property integer $staff_id
 * @property string $note_text
 * @property string $note_created_datetime
 * @property string $note_updated_datetime
 * 
 * @property Company[] $company
 * @property staff[] $Staff
 */
class Note extends \yii\db\ActiveRecord
{ 
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'note';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id','note_text'], 'required'],
            [['note_created_datetime', 'note_updated_datetime','staff_id'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'note_uuid',
                ],
                'value' => function() {
                    if (!$this->note_uuid)
                        $this->note_uuid = 'note_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->note_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'note_created_datetime',
                'updatedAtAttribute' => 'note_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'staff_id',
                'updatedByAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'note_uuid' => Yii::t('candidate', 'ID'),
            'company_id' => Yii::t('candidate', 'Company ID'),
            'staff_id' => Yii::t('candidate', 'Staff ID'),
            'note_text' => Yii::t('candidate', 'Note'),
            'note_created_datetime' => Yii::t('candidate', 'Created At'),
            'note_updated_datetime' => Yii::t('candidate', 'Updated At'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'staff'
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
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
