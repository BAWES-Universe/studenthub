<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "transfer_candidates".
 *
 * @property integer $tc_id
 * @property integer $transfer_id
 * @property integer $candidate_id
 * @property string $hours
 * @property string $candidate_hourly_rate
 * @property string $bonus
 * @property string $transfer_cost 
 * @property string $tc_created_at
 * @property string $tc_updated_at
 *
 * @property Candidate $candidate
 * @property Transfer $transfer
 */
class TransferCandidates extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_candidates';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transfer_id', 'candidate_id'], 'integer'],
            [['hours', 'transfer_cost', 'bonus', 'candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['tc_created_at', 'tc_updated_at'], 'safe'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'tc_created_at',
                'updatedAtAttribute' => 'tc_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tc_id' => 'TC ID',
            'transfer_id' => 'Transfer ID',
            'candidate_id' => 'Candidate ID',
            'hours' => 'Hours',
            'candidate_hourly_rate' => 'Candidate Hourly Rate',
            'company_hourly_rate' => 'Company Hourly Rate',
            'transfer_cost' => 'Transfer cost',
            'bonus' => 'Bonus',
            'tc_created_at' => 'Tc Created At',
            'tc_updated_at' => 'Tc Updated At',
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
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['transfer_id' => 'transfer_id']);
    }

    public function getCandidate_total()
    {
        return ($this->candidate_hourly_rate * $this->hours) + $this->bonus;
    }

    /**
     * @inheritdoc
     * @return query\TransferCandidatesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferCandidatesQuery(get_called_class());
    }
}
