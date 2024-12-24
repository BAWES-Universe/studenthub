<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_working_hour_appeal".
 *
 * @property string $appeal_uuid
 * @property string $candidate_working_hour_uuid
 * @property int $candidate_id
 * @property string $reason
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property CandidateWorkingHour $candidateWorkingHour
 * @property CandidateWorkingHourAppealUpdates[] $candidateWorkingHourAppealUpdates
 */
class CandidateWorkingHourAppeal extends \yii\db\ActiveRecord
{
    const STATUS_SUBMITTED = 10;
    const STATUS_AWAITING_REVIEW = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_RESOLVED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_working_hour_appeal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_working_hour_uuid', "reason"], 'required'],//'appeal_uuid',
            [['reason'], 'string'],
            [['status', 'candidate_id'], 'integer'],
            [["status"], "default", "value" => self::STATUS_SUBMITTED],
            [['created_at', 'updated_at'], 'safe'],
            [['appeal_uuid', 'candidate_working_hour_uuid'], 'string', 'max' => 60],
            [['appeal_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['candidate_working_hour_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingHour::className(), 'targetAttribute' => ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'appeal_uuid',
                ],
                'value' => function() {
                    if(!$this->appeal_uuid)
                        $this->appeal_uuid = new Expression("CONCAT('appeal_', UUID())");
                    //'appeal_update_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->appeal_uuid;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'appeal_uuid' => Yii::t('app', 'Appeal Uuid'),
            'candidate_working_hour_uuid' => Yii::t('app', 'Candidate Working Hour Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate'),
            'reason' => Yii::t('app', 'Reason'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return array_merge(['correctedHours', 'originalHour', 'updates', 'candidateWorkingDate'],
            parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * Corrected Hours
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingHour")
    {
        $hour= $this->getOriginalHour($modelClass)
            ->one();

        if ($hour)
            return $hour->getCandidateWorkingDate()->one();
    }

    /**
     * Corrected Hours
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectedHours($modelClass = "\common\models\CandidateWorkingHour")
    {
        return $this->hasOne($modelClass::className(), ['appeal_uuid' => 'appeal_uuid']);
    }

    /**
     * original session for which appeal was generated
     * @return \yii\db\ActiveQuery
     */
    public function getOriginalHour($modelClass = "\common\models\CandidateWorkingHour")
    {
        return $this->hasOne($modelClass::className(), ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdates($modelClass = "\common\models\CandidateWorkingHourAppealUpdates")
    {
        return $this->hasMany($modelClass::className(), ['appeal_uuid' => 'appeal_uuid']);
    }
}
