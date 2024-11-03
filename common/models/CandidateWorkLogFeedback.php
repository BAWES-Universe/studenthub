<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_work_log_feedback".
 *
 * @property string $cwlf_uuid
 * @property int $candidate_id
 * @property int $store_id
 * @property int $company_id
 * @property string $date
 * @property string $candidate_working_hour_uuid
 * @property int $status
 * @property string $note
 * @property string $reason
 * @property int $is_public
 * @property int $rating
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Company $company
 * @property Store $store
 * @property Contact $createdBy
 * @property CandidateWorkingHour $candidateWorkingHour
 */
class CandidateWorkLogFeedback extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_work_log_feedback';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'store_id', "status", "date"], 'required'],//'company_id',
            [['candidate_id', 'store_id', 'company_id', 'status', 'is_public', 'rating'], 'integer'],
            [['date', 'created_at', 'updated_at'], 'safe'],
            [['note'], 'string'],
            [['cwlf_uuid', "candidate_working_hour_uuid"], 'string', 'max' => 60],
            [['reason'], 'string', 'max' => 255],
            //[['cwlf_uuid'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Contact::className(), 'targetAttribute' => ['contact_uuid' => 'created_by']],
            [['candidate_working_hour_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingHour::className(), 'targetAttribute' => ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cwlf_uuid',
                ],
                'value' => function() {
                    if (!$this->cwlf_uuid)
                        $this->cwlf_uuid = 'cwlf_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cwlf_uuid;
                }
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => null
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
            'cwlf_uuid' => Yii::t('app', 'Cwlf Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'date' => Yii::t('app', 'Date'),
            "candidate_working_hour_uuid" => Yii::t('app', 'Candidate Working Hour ID'),
            'status' => Yii::t('app', 'Status'),
            'note' => Yii::t('app', 'Note'),
            'reason' => Yii::t('app', 'Reason'),
            'is_public' => Yii::t('app', 'Is Public'),
            'rating' => Yii::t('app', 'Rating'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            "createdBy"
        ]);
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->candidate_working_hour_uuid) {

            CandidateWorkingHour::updateAll([
                "status" => $this->status,
            ], [
                "candidate_working_hour_uuid" => $this->candidate_working_hour_uuid
                /*
                "candidate_id" => $this->candidate_id,
                "store_id" => $this->store_id,
                "date" => $this->date,*/
            ]);

            //todo: update date status or ignore if status will be replaced with "health indicator"

            $this->notifyCandidate($this->candidate_working_hour_uuid);

        } else {

            /*
             * status at CandidateWorkingDate level will be replaced with health
             * -------------------------------------------------------------
             * CandidateWorkingDate::updateAll([
                "status" => $this->status,
            ], [
                "candidate_id" => $this->candidate_id,
                "store_id" => $this->store_id,
                "date" => $this->date,
            ]);*/

            //update all sessions

            $hours = CandidateWorkingHour::find()
                ->andWhere([
                    "candidate_id" => $this->candidate_id,
                    "store_id" => $this->store_id,
                    "date" => $this->date,
                    "status" => CandidateWorkingHour::STATUS_PENDING
                ])
                ->all();

            foreach ($hours as $hour) {
                $this->notifyCandidate($hour->candidate_working_hour_uuid);
            }

            CandidateWorkingHour::updateAll([
                "status" => $this->status,
            ], [
                "candidate_id" => $this->candidate_id,
                "store_id" => $this->store_id,
                "date" => $this->date,
            ]);

        }

        //todo: update status for selected sessions only

        return true;
    }

    /**
     * @return boolean
     */
    public function notifyCandidate($candidate_working_hour_uuid) {

        /*$date = CandidateWorkingDate::find()->andWhere([
            "candidate_id" => $this->candidate_id,
            "store_id" => $this->store_id,
            "date" => $this->date,
        ])->one();

        if (!$date) {
            Yii::error("No working date on trying to notify work log feedback" . print_r([
                    "candidate_id" => $this->candidate_id,
                    "store_id" => $this->store_id,
                    "date" => $this->date,
                ], true), __METHOD__);
            return false;
        }*/

        $model = new CandidateNotification();
        $model->cwlf_uuid = $this->cwlf_uuid;
        $model->candidate_id = $this->candidate_id;
        // $model->candidate_working_date_uuid = $date->cwd_uuid;
        $model->candidate_working_hour_uuid = $candidate_working_hour_uuid;
        $model->company_id = $this->company_id;
        $model->store_id = $this->store_id;
        $model->type = $this->status == self::STATUS_APPROVED ?
            CandidateNotification::TYPE_WORK_SESSION_APPROVED: CandidateNotification::TYPE_WORK_SESSION_REJECTED;
        if (!$model->save()) {
            Yii::error("Error saving notification: " . print_r($model->errors, true));
        }

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingHour($className = '\common\models\CandidateWorkingHour')
    {
        return $this->hasOne($className::className(), ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($className = '\common\models\Candidate')
    {
        return $this->hasOne($className::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\common\models\Company')
    {
        return $this->hasOne($className::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\common\models\Store')
    {
        return $this->hasOne($className::className(), ['store_id' => 'store_id']);
    }

    /**
     * @param $className
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($className = '\common\models\Contact')
    {
        return $this->hasOne($className::className(), ['contact_uuid' => 'created_by']);
    }
}
