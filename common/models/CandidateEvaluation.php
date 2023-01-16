<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "candidate_evaluation".
 *
 * @property string $can_eval_uuid candidate_evaluation_uuid
 * @property int $candidate_id
 * @property int $dept_id 1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative, 
 * @property string $ceq_uuid candidate_evaluation_question_uuid
 * @property string $question
 * @property string $comment
 * @property int $rating
 * @property int $staff_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property CandidateEvalQues $ceqUu
 * @property Staff $staff
 */
class CandidateEvaluation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_evaluation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'dept_id', 'rating', 'staff_id'], 'integer'],
            [['comment'], 'string'],
            [['created_at', 'updated_at'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['can_eval_uuid', 'ceq_uuid'], 'string', 'max' => 60],
            [['question'], 'string', 'max' => 225],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['ceq_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateEvalQues::className(), 'targetAttribute' => ['ceq_uuid' => 'ceq_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'can_eval_uuid' => 'Can Eval Uuid',
            'candidate_id' => 'Candidate ID',
            'dept_id' => 'Dept ID',
            'ceq_uuid' => 'Ceq Uuid',
            'question' => 'Question',
            'comment' => 'Comment',
            'rating' => 'Rating',
            'staff_id' => 'Staff ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasOne(Candidate::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEvalQuestion()
    {
        return $this->hasOne(CandidateEvalQues::className(), ['ceq_uuid' => 'ceq_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }
}
