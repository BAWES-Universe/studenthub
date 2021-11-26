<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "request_checklist".
 *
 * @property string $request_checklist_uuid
 * @property string $status_name
 * @property string $status_name_ar
 * @property int $is_require
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Note[] $notes
 */
class RequestChecklist extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_checklist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status_name'], 'required'],
            [['is_require'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['request_checklist_uuid'], 'string', 'max' => 60],
            [['status_name', 'status_name_ar'], 'string', 'max' => 100],
           // [['request_checklist_uuid'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'request_checklist_uuid',
                ],
                'value' => function() {
                    if (!$this->request_checklist_uuid)
                        $this->request_checklist_uuid = 'request_checklis_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->request_checklist_uuid;
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
            'request_checklist_uuid' => Yii::t('app', 'Request Checklist Uuid'),
            'status_name' => Yii::t('app', 'Status Name'),
            'status_name_ar' => Yii::t('app', 'Status Name Ar'),
            'is_require' => Yii::t('app', 'Is Require'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['request_checklist_uuid' => 'request_checklist_uuid']);
    }
}
