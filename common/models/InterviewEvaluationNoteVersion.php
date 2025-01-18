<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "interview_evaluation_note_version".
 *
 * @property string $ienv_uuid
 * @property string $interview_evaluation_uuid
 * @property int $version
 * @property int $staff_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property InterviewEvaluation $interviewEvaluation
 * @property Staff $staff
 */
class InterviewEvaluationNoteVersion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'interview_evaluation_note_version';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['interview_evaluation_uuid'], 'required'],
            [['version', 'staff_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ienv_uuid', 'interview_evaluation_uuid'], 'string', 'max' => 60],
            [['ienv_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['interview_evaluation_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => InterviewEvaluation::class, 'targetAttribute' => ['interview_evaluation_uuid' => 'interview_evaluation_uuid']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ienv_uuid',
                ],
                'value' => function() {
                    if (!$this->ienv_uuid)
                        $this->ienv_uuid = 'ienv_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->ienv_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
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
            'ienv_uuid' => Yii::t('app', 'Ienv Uuid'),
            'interview_evaluation_uuid' => Yii::t('app', 'Interview Evaluation Uuid'),
            'version' => Yii::t('app', 'Version'),
            "staff_id" => Yii::t('app', 'Staff ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return [
            "interviewEvaluationNotes",
            "staff"
        ];
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
    public function getInterviewEvaluation()
    {
        return $this->hasOne(InterviewEvaluation::className(), ['interview_evaluation_uuid' => 'interview_evaluation_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInterviewEvaluationNotes()
    {
        return $this->hasMany(InterviewEvaluationNote::className(), ['ienv_uuid' => 'ienv_uuid']);
    }
}
