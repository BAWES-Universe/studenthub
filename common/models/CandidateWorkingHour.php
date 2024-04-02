<?php

namespace common\models;

use candidate\models\Candidate;
use candidate\models\Store;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

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
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Store $store
 */
class CandidateWorkingHour extends \yii\db\ActiveRecord
{
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
            [['candidate_id', 'store_id','total_time'], 'integer'],
            [['date', 'start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['start_location_lat', 'start_location_long', 'end_location_lat', 'end_location_long'], 'number'],
            [['candidate_working_hour_uuid'], 'string', 'max' => 60],
            [['candidate_working_hour_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => false, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['store_id'], 'exist', 'skipOnError' => false, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
        ];
    }

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
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'parent_company_id']);
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
}
