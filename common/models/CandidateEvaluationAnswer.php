<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "candidate_evaluation_answer".
 *
 * @property string $can_eval_uuid candidate_evaluation_uuid
 * @property string $ceq_uuid
 * @property string $question
 * @property string $answer
 * @property int $rating
 *
 * @property CandidateEvaluation $canEvalUu
 * @property CandidateEvalQues $ceqUu
 */
class CandidateEvaluationAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_evaluation_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ceq_uuid','question','answer'], 'required'],
            [['answer'], 'string'],
            [['can_eval_uuid', 'ceq_uuid'], 'string', 'max' => 60],
            [['question'], 'string', 'max' => 225],
            [['rating'], 'number', 'min' => 1, 'max' => 10],
            [['can_eval_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateEvaluation::className(), 'targetAttribute' => ['can_eval_uuid' => 'can_eval_uuid']],
            [['ceq_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateEvalQues::className(), 'targetAttribute' => ['ceq_uuid' => 'ceq_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'can_eval_uuid' => 'Can Eval Uuid',
            'ceq_uuid' => 'Ceq Uuid',
            'question' => 'Question',
            'answer' => 'Answer',
            'rating' => 'Rating',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCanEvalUu()
    {
        return $this->hasOne(CandidateEvaluation::className(), ['can_eval_uuid' => 'can_eval_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCeqUu()
    {
        return $this->hasOne(CandidateEvalQues::className(), ['ceq_uuid' => 'ceq_uuid']);
    }
}
