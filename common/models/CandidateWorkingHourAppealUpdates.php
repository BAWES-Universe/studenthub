<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_working_hour_appeal_updates".
 *
 * @property string $appeal_update_uuid
 * @property string $appeal_uuid
 * @property string $update
 * @property string $detail
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 * @property CandidateWorkingHourAppeal $appealUu
 */
class CandidateWorkingHourAppealUpdates extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_working_hour_appeal_updates';
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by'
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'appeal_update_uuid',
                ],
                'value' => function() {
                    if(!$this->appeal_update_uuid)
                        $this->appeal_update_uuid = new Expression("CONCAT('appeal_update_', UUID())");
                            //'appeal_update_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->appeal_update_uuid;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['appeal_uuid',  "detail"], 'required'],//'appeal_update_uuid',"update",
            [['detail'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['appeal_update_uuid', 'appeal_uuid'], 'string', 'max' => 60],
            [['update'], 'string', 'max' => 255],
            [['appeal_update_uuid'], 'unique'],
            [['is_new'], "boolean"],
            [['is_new'], 'default', 'value'=> true],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['created_by' => 'staff_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['updated_by' => 'staff_id' ]],
            [['appeal_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingHourAppeal::className(), 'targetAttribute' => ['appeal_uuid' => 'appeal_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'appeal_update_uuid' => Yii::t('app', 'Appeal Update Uuid'),
            'appeal_uuid' => Yii::t('app', 'Appeal Uuid'),
            'update' => Yii::t('app', 'Update'),
            'detail' => Yii::t('app', 'Detail'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(['createdBy', 'updatedBy', 'appeal'], parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAppeal($modelClass = "\common\models\CandidateWorkingHourAppeal")
    {
        return $this->hasOne($modelClass::className(), ['appeal_uuid' => 'appeal_uuid']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'updated_by']);
    }
}
