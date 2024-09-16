<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_working_date".
 *
 * @property string $cwd_uuid
 * @property int $candidate_id
 * @property int $store_id
 * @property int $company_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property int $total_time
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Company $company
 * @property Store $store
 */
class CandidateWorkingDate extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_working_date';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //'cwd_uuid',
            [['candidate_id', 'store_id', 'company_id', 'date', 'start_time'], 'required'],
            [['candidate_id', 'store_id', 'company_id', 'total_time', 'status'], 'integer'],
            [['date', 'start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['cwd_uuid'], 'string', 'max' => 60],
            [['cwd_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cwd_uuid',
                ],
                'value' => function() {
                    if (!$this->cwd_uuid)
                        $this->cwd_uuid = 'cwd_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cwd_uuid;
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
            'cwd_uuid' => Yii::t('app', 'Cwd Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'date' => Yii::t('app', 'Date'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'total_time' => Yii::t('app', 'Total Time'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return query\CandidateWorkingDateQuery
     */
    public static function find()
    {
        return new query\CandidateWorkingDateQuery(get_called_class());
    }
}
