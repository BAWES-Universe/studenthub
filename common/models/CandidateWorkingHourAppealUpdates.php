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
 *
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
            [['appeal_uuid', "update", "detail"], 'required'],//'appeal_update_uuid',
            [['detail'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['appeal_update_uuid', 'appeal_uuid'], 'string', 'max' => 60],
            [['update'], 'string', 'max' => 255],
            [['appeal_update_uuid'], 'unique'],
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
     * @return \yii\db\ActiveQuery
     */
    public function getAppeal($modelClass = "\common\models\CandidateWorkingHourAppeal")
    {
        return $this->hasOne($modelClass::className(), ['appeal_uuid' => 'appeal_uuid']);
    }
}
