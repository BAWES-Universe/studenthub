<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "interview_evaluation".
 *
 * @property string $interview_evaluation_uuid
 * @property string $request_uuid
 * @property int $company_id
 * @property int $staff_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 * @property Request $requestUu
 * @property Staff $staff
 * @property Note[] $notes
 */
class InterviewEvaluation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'interview_evaluation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid', 'company_id'], 'required'],//'interview_evaluation_uuid',
            [['company_id', 'staff_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['interview_evaluation_uuid', 'request_uuid'], 'string', 'max' => 60],
            [['interview_evaluation_uuid'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'interview_evaluation_uuid',
                ],
                'value' => function() {
                    if (!$this->interview_evaluation_uuid)
                        $this->interview_evaluation_uuid = 'interview_evaluation_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->interview_evaluation_uuid;
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
            'interview_evaluation_uuid' => Yii::t('app', 'Interview Evaluation Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'staff_id' => Yii::t('app', 'Staff ID'),
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
            "staff",
            "request",
            "company",
            "notes",
            "interviewEvaluationNoteVersions",
            "latestInterviewEvaluationNoteVersions",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['interview_evaluation_uuid' => 'interview_evaluation_uuid']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getLatestInterviewEvaluationNoteVersions($modelClass = "\common\models\InterviewEvaluationNoteVersion")
    {
        return $this->hasOne($modelClass::className(), ['interview_evaluation_uuid' => 'interview_evaluation_uuid'])
            ->orderBy("interview_evaluation_note_version.created_at DESC");
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInterviewEvaluationNoteVersions($modelClass = "\common\models\InterviewEvaluationNoteVersion")
    {
        return $this->hasMany($modelClass::className(), ['interview_evaluation_uuid' => 'interview_evaluation_uuid']);
    }
}
