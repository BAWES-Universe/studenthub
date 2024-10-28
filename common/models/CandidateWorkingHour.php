<?php

namespace common\models;

use candidate\models\Candidate;
use candidate\models\Store;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "candidate_working_hour".
 *
 * @property string $candidate_working_hour_uuid
 * @property int $candidate_id
 * @property int $store_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string $total_time
 * @property string $start_location_lat
 * @property string $start_location_long
 * @property string $end_location_lat
 * @property string $end_location_long
 * @property string $note
 * @property int $status
 * @property string $via
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Store $store
 */
class CandidateWorkingHour extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_working_hour';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store_id','candidate_id'], 'required'],
            [['candidate_id', 'store_id','total_time', 'status'], 'integer'],
            [['date', 'start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['start_location_lat', 'start_location_long', 'end_location_lat', 'end_location_long'], 'number'],
            [['note', 'via'], 'string'],
            [['candidate_working_hour_uuid'], 'string', 'max' => 60],
            [['candidate_working_hour_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => false, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['store_id'], 'exist', 'skipOnError' => false, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'candidate_working_hour_uuid',
                ],
                'value' => function() {
                    if (!$this->candidate_working_hour_uuid)
                        $this->candidate_working_hour_uuid = 'can_wrk_hr_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->candidate_working_hour_uuid;
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
     * @param $insert
     * @param $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $date = CandidateWorkingDate::find()->andWhere([
            "candidate_id" => $this->candidate_id,
            "store_id" => $this->store_id,
            "date" => $this->date,
        ])->one();

        //calculate via

        /*$sessionsInDay = self::find()
            ->andWhere([
                "candidate_id" => $this->candidate_id,
                "store_id" => $this->store_id,
                "date" => $this->date,
            ])
            ->all();*/

        //$arrVia = array_unique(ArrayHelper::getColumn($sessionsInDay, "via"));

        //$via = implode(", ", $arrVia);

        //start + end + manually added

        if ($insert) {

            if (!$date) {//this would be first session in a day
                $date = new CandidateWorkingDate;
                $date->store_id = $this->store_id;
                $date->company_id = $this->store->company_id;
                $date->candidate_id = $this->candidate_id;
                $date->date = $this->date;
                $date->start_time = $this->start_time;
                $date->end_time = $this->end_time;//can have end_time in manual input
                $date->total_time = $this->total_time;
                //$date->via = $via;
               // $this->status = $this->status;
                if(!$date->save()) {
                    Yii::error($date->errors);
                }

            } else { //if sub-sequent sessions

                if ($this->end_time) { //for manual input
                    $total_time = CandidateWorkingHour::find()->andWhere([
                            "candidate_id" => $this->candidate_id,
                            "store_id" => $this->store_id,
                            "date" => $this->date,
                        ])
                        ->sum("total_time");

                    CandidateWorkingDate::updateAll([
                        "status" => $this->status,
                        "total_time" => $total_time,
                        "end_time" => $this->end_time,
                      //  "via" => $via
                    ], [
                        "candidate_id" => $this->candidate_id,
                        "store_id" => $this->store_id,
                        "date" => $this->date,
                    ]);

                } else { //when timer started

                    //as we have health indicator now + working tag, no more need to reset status

                    /*
                    CandidateWorkingDate::updateAll([
                        "status" => $this->status, //reset status
                        "total_time" => null, //reset total time as new session pending to finish
                        "end_time" => null, //as current session will be always latest session
                     //   "via" => $via
                    ], [
                        "candidate_id" => $this->candidate_id,
                        "store_id" => $this->store_id,
                        "date" => $this->date,
                    ]);*/
                }
            }
        }
        else //update will be always end session action
        {
            $total_time = CandidateWorkingHour::find()->andWhere([
                    "candidate_id" => $this->candidate_id,
                    "store_id" => $this->store_id,
                    "date" => $this->date,
                ])
                ->sum("total_time");

            CandidateWorkingDate::updateAll([
                "status" => $this->status,
                "total_time" => $total_time,
                "end_time" => $this->end_time, //as current session will be always latest session
               // "via" => $via
            ], [
                "candidate_id" => $this->candidate_id,
                "store_id" => $this->store_id,
                "date" => $this->date,
            ]);

        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'candidate_working_hour_uuid' => 'Candidate Working Hour Uuid',
            'candidate_id' => 'Candidate ID',
            'store_id' => 'Store ID',
            'date' => 'Date',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'total_time' => 'Total Time',
            'start_location_lat' => 'Star Location Lat',
            'start_location_long' => 'Star Location Long',
            'end_location_lat' => 'End Location Lat',
            'end_location_long' => 'End Location Long',
            'status' => 'Status',
            'note' => 'Note',
            "via" => "Via",
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function extraFields()
    {
        return [
            'candidate',
            'store',
            'company',
            'parentCompany',
            'dateListByCandidate',
            'checkIn',
            "checkOut",
            "dateStatus",
            "firstSession",
            "lastSession"
        ];
    }

    public function getCheckIn() {
        if ($this->firstSession) {
            return $this->firstSession->start_time;
        }
    }

    public function getCheckOut() {
        if ($this->lastSession) {
            return $this->lastSession->end_time;
        }
    }

    public function getDateStatus() {
        if ($this->lastSession) {
            return $this->lastSession->status;
        }
    }

    public function getLastSession() {
        return CandidateWorkingHour::find()
            ->andWhere(['date' => $this->date])
            ->andWhere(['candidate_id' => $this->candidate_id])
            ->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at DESC')
            ->one();
    }

    public function getFirstSession() {
        return self::find()
            ->andWhere(['date' => $this->date])
            ->andWhere(['candidate_id' => $this->candidate_id])
            // ->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at ASC')
            ->one();
    }

    public function getTotalTime() {
        return CandidateWorkingHour::find()
            ->andWhere(['date' => $this->date])
            ->andWhere(['candidate_id' => $this->candidate_id])
            ->andWhere(new Expression("end_time IS NOT NULL"))
            ->sum("total_time");
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
    public function getStore($className = '\common\models\Store')
    {
        return $this->hasOne($className::className(), ['store_id' => 'store_id']);
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
    public function getParentCompany($className = '\common\models\Company')
    {
        return $this->hasOne($className::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getDateListByCandidate() {
        return self::find()
            ->andWhere(['date' => $this->date,'candidate_id'=>$this->candidate_id])
            ->orderBy('created_at')
            ->all();
    }

    /**
     * @return query\CandidateWorkingHourQuery
     */
    public static function find()
    {
        return new query\CandidateWorkingHourQuery(get_called_class());
    }
}
