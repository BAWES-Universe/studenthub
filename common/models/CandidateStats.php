<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_stats".
 *
 * @property string $cs_uuid
 * @property int $candidate_id
 * @property string $total_revenue
 * @property string $currency_code
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property CandidateWorkHistory $candidateWorkHistories
 */
class CandidateStats extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_stats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_revenue', 'currency_code'], 'required'],//'cs_uuid',
            [['candidate_id'], 'integer'],
            [['total_revenue'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['cs_uuid'], 'string', 'max' => 60],
            [['currency_code'], 'string', 'max' => 3],
            [['cs_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cs_uuid',
                ],
                'value' => function() {
                    if (!$this->cs_uuid)
                        $this->cs_uuid = 'cs_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cs_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => "updated_at",
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
            'cs_uuid' => Yii::t('app', 'Cs Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'total_revenue' => Yii::t('app', 'Total Revenue'),
            'currency_code' => Yii::t('app', 'Currency Code'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistories($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasMany($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @inheritdoc
     * @return query\CandidateStatsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateStatsQuery(get_called_class());
    }
}
