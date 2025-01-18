<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_eval_ques".
 *
 * @property string $ceq_uuid candidate_evaluation_question_uuid
 * @property string $question
 * @property string $created_at
 * @property string $updated_at
 *
 * @property CandidateEvalDeptQues[] $candidateEvalDeptQues
 * @property CandidateEvaluation[] $candidateEvaluations
 */
class CandidateEvalQues extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_eval_ques';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['question'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['ceq_uuid'], 'string', 'max' => 60],
            [['question'], 'string', 'max' => 225],
            [['ceq_uuid'], 'unique'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ceq_uuid',
                ],
                'value' => function() {
                    if(!$this->ceq_uuid)
                        $this->ceq_uuid = new Expression('UUID()');

                    return $this->ceq_uuid;
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ceq_uuid' => 'Ceq Uuid',
            'question' => 'Question',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function extraFields()
    {
        return [
            'candidateEvalDeptQues'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEvalDeptQues()
    {
        return $this->hasMany(CandidateEvalDeptQues::className(), ['ceq_uuid' => 'ceq_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEvaluations()
    {
        return $this->hasMany(CandidateEvaluation::className(), ['ceq_uuid' => 'ceq_uuid']);
    }
}
