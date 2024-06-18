<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
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
 * @property int $status
 * @property string $note
 * @property string $reason
 * @property int $is_public
 * @property int $rating
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Company $company
 * @property Store $store
 */
class CandidateWorkLogFeedback extends \yii\db\ActiveRecord
{
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
            [['candidate_id', 'store_id', 'company_id', "status", "date"], 'required'],
            [['candidate_id', 'store_id', 'company_id', 'status', 'is_public', 'rating'], 'integer'],
            [['date', 'created_at', 'updated_at'], 'safe'],
            [['note'], 'string'],
            [['cwlf_uuid'], 'string', 'max' => 60],
            [['reason'], 'string', 'max' => 255],
            //[['cwlf_uuid'], 'unique'],
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
            'status' => Yii::t('app', 'Status'),
            'note' => Yii::t('app', 'Note'),
            'reason' => Yii::t('app', 'Reason'),
            'is_public' => Yii::t('app', 'Is Public'),
            'rating' => Yii::t('app', 'Rating'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        CandidateWorkingHour::updateAll([
            "status" => $this->status,
        ], [
            "candidate_id" => $this->candidate_id,
            "store_id" => $this->store_id,
            "date" => $this->date,
        ]);

        return true;
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
}
