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
 * @property string $created_at
 * @property string $updated_at
 *
 * @property InterviewEvaluation $interviewEvaluationUu
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
            [['version'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ienv_uuid', 'interview_evaluation_uuid'], 'string', 'max' => 60],
            [['ienv_uuid'], 'unique'],
            [['interview_evaluation_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => InterviewEvaluation::className(), 'targetAttribute' => ['interview_evaluation_uuid' => 'interview_evaluation_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ienv_uuid',
                ],
                'value' => function() {
                    if (!$this->ienv_uuid)
                        $this->ienv_uuid = 'ienv_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->ienv_uuid;
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
            'ienv_uuid' => Yii::t('app', 'Ienv Uuid'),
            'interview_evaluation_uuid' => Yii::t('app', 'Interview Evaluation Uuid'),
            'version' => Yii::t('app', 'Version'),
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
            "interviewEvaluationNotes"
        ];
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
