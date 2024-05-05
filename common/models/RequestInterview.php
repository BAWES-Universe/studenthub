<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "request_interview".
 *
 * @property string $request_interview_uuid
 * @property string $application_uuid
 * @property string $request_uuid
 * @property string $fulltimer_uuid
 * @property int $candidate_id
 * @property string $interview_at
 * @property string $internal_note
 * @property int $status 0 - requested, 1 - scheduled, 2 - rejected
 * @property int $staff_id staff assigned to host interview
 * @property string $interview_note interview joining link / instruction
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property RequestApplication $application
 * @property Candidate $candidate
 * @property Contact $createdBy
 * @property Fulltimer $fulltimer
 * @property Request $request
 * @property Staff $staff
 */
class RequestInterview extends \yii\db\ActiveRecord
{
    const STATUS_REQUESTED = 0;
    const STATUS_SCHEDULED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_CANCELLED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_interview';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['application_uuid', 'request_uuid'], 'required'],//'request_interview_uuid',
            [['candidate_id', 'status', 'staff_id'], 'integer'],
            [['interview_at', 'created_at', 'updated_at'], 'safe'],
            [['internal_note', 'interview_note'], 'string'],
            [['request_interview_uuid', 'application_uuid', 'request_uuid', 'fulltimer_uuid', 'created_by'], 'string', 'max' => 60],
            [['request_interview_uuid'], 'unique'],
            [['application_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => RequestApplication::className(), 'targetAttribute' => ['application_uuid' => 'application_uuid']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Contact::className(), 'targetAttribute' => ['created_by' => 'contact_uuid']],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'request_interview_uuid',
                ],
                'value' => function() {
                    if (!$this->request_interview_uuid)
                        $this->request_interview_uuid = 'request_interview_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->request_interview_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => null
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'request_interview_uuid' => 'Request Interview Uuid',
            'application_uuid' => 'Application Uuid',
            'request_uuid' => 'Request Uuid',
            'fulltimer_uuid' => 'Fulltimer Uuid',
            'candidate_id' => 'Candidate ID',
            'interview_at' => 'Interview At',
            'internal_note' => 'Internal Note',
            'status' => 'Status',
            'staff_id' => 'Staff ID',
            'interview_note' => 'Interview Note',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getApplication($modelClass = "\common\models\RequestApplication")
    {
        return $this->hasOne($modelClass::className(), ['application_uuid' => 'application_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
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
}
