<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "daily_standup_answer".
 *
 * @property string $answer_uuid
 * @property int $staff_id
 * @property string $question_uuid
 * @property string $question
 * @property string $answer
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DailyStandupQuestion $questionUu
 * @property Staff $staff
 */
class DailyStandupAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daily_standup_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           // [['answer_uuid', 'created_at', 'updated_at'], 'required'],
            [['staff_id'], 'integer'],
            [['answer'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['answer_uuid', 'question_uuid'], 'string', 'max' => 60],
            [['question'], 'string', 'max' => 255],
            [['answer_uuid'], 'unique'],
            [['question_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => DailyStandupQuestion::className(), 'targetAttribute' => ['question_uuid' => 'question_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'answer_uuid',
                ],
                'value' => function() {
                    if (!$this->answer_uuid)
                        $this->answer_uuid = 'answer_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->answer_uuid;
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
            'answer_uuid' => Yii::t('app', 'Answer Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'question_uuid' => Yii::t('app', 'Question Uuid'),
            'question' => Yii::t('app', 'Question'),
            'answer' => Yii::t('app', 'Answer'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    public function extraFields()
    {
        return [
            'question',
            'staff',
        ];
    }

    /**
     * @inheritdoc
     * @return query\DailyStandupAnswerQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\DailyStandupAnswerQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion($modelClass = "\common\models\DailyStandupQuestion")
    {
        return $this->hasOne($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
