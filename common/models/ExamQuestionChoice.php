<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "exam_question_choice".
 *
 * @property string $choice_uuid
 * @property string $question_uuid
 * @property string $choice_value_en
 * @property string $choice_value_ar
 * @property int $choice_sort_order
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property ExamQuestion $questionUu
 */
class ExamQuestionChoice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exam_question_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['choice_value_en'], 'required'],//'choice_uuid',
            [['choice_sort_order', 'is_deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['choice_uuid', 'question_uuid'], 'string', 'max' => 60],
            [['choice_value_en', 'choice_value_ar'], 'string', 'max' => 255],
            [['choice_uuid'], 'unique'],
            [['question_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => ExamQuestion::className(), 'targetAttribute' => ['question_uuid' => 'question_uuid']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'choice_uuid',
                ],
                'value' => function() {
                    if(!$this->choice_uuid)
                        $this->choice_uuid = 'choice_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->choice_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => "updated_at",
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
            'choice_uuid' => Yii::t('app', 'Choice Uuid'),
            'question_uuid' => Yii::t('app', 'Question Uuid'),
            'choice_value_en' => Yii::t('app', 'Choice Value En'),
            'choice_value_ar' => Yii::t('app', 'Choice Value Ar'),
            'choice_sort_order' => Yii::t('app', 'Choice Sort Order'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion($modelClass = "\common\models\ExamQuestion")
    {
        return $this->hasOne($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }
}
