<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "job_interest".
 *
 * @property string $job_interest_uuid
 * @property int $candidate_id
 * @property string $job_uuid
 * @property string $status
 * @property string $notes
 * @property string $seen_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Invitation[] $invitations
 * @property Candidate $candidate
 * @property Job $job
 */
class JobInterest extends \yii\db\ActiveRecord
{
    const STATUS_INTERESTED = "INTERESTED";//default status
    const STATUS_SHORTLISTED = "SHORTLISTED";
    const STATUS_REJECTED = "REJECTED";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'job_interest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'job_uuid'], 'required'],
            [['candidate_id'], 'integer'],
            [['notes'], 'string'],
            [['created_at', 'updated_at', "seen_at"], 'safe'],
            [['job_interest_uuid', 'job_uuid'], 'string', 'max' => 60],
            [['status'], 'string', 'max' => 255],
            ['status', 'default', 'value' => self::STATUS_INTERESTED],
            [['job_interest_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['job_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Job::class, 'targetAttribute' => ['job_uuid' => 'job_uuid']],
        ];
    }

    /**
     * @return array
     */
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
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'job_interest_uuid',
                ],
                'value' => function ($event) {
                    if (!$this->job_interest_uuid)
                        $this->job_interest_uuid = new Expression('UUID()');

                    return $this->job_interest_uuid;
                },
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'job_interest_uuid' => Yii::t('app', 'Job Interest Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'job_uuid' => Yii::t('app', 'Job Uuid'),
            'status' => Yii::t('app', 'Status'),
            'notes' => Yii::t('app', 'Notes'),
            "seen_at" => Yii::t('app', 'Seen At'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return array_merge(['invitations', 'candidate', 'job'], parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\common\models\Invitation")
    {
        return $this->hasMany($modelClass::className(), ['job_interest_uuid' => 'job_interest_uuid']);
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
    public function getJob($modelClass = "\common\models\Job")
    {
        return $this->hasOne($modelClass::className(), ['job_uuid' => 'job_uuid']);
    }
}
