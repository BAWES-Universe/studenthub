<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_evaluation".
 *
 * @property string $can_eval_uuid candidate_evaluation_uuid
 * @property int $candidate_id
 * @property int $dept_id 1-Sales Associate,2-IT,3-Call Centre Agent, 4-Social Media, 5-Outdoor Sales Representative, 
 * @property string $start_date
 * @property string $end_date
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
            [['candidate_id', 'dept_id','start_date','end_date'], 'required'],
            [['created_at', 'updated_at','start_date','end_date'], 'safe'],
            [['can_eval_uuid'], 'string', 'max' => 60],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()')
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'staff_id',
                'updatedByAttribute' => false,
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'can_eval_uuid',
                ],
                'value' => function ($event) {
                    if (!$this->can_eval_uuid)
                        $this->can_eval_uuid = new Expression('UUID()');

                    return $this->can_eval_uuid;
                },
            ]

        ];
    }

    /**
     * @return array|string[]
     */
    public function extraFields()
    {
        return [
            'candidate',
            'staff',
            'department',
            'questionAnswer'
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
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
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
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionAnswer()
    {
        return $this->hasMany(CandidateEvaluationAnswer::className(), ['can_eval_uuid' => 'can_eval_uuid']);
    }

    /**
     * @return string
     */
    public function getDepartment() {
        return CandidateEvalDeptQues::getDepartmentDetail($this->dept_id);
    }
}
