<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "candidate_id_card".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property string $expiry_date
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 */
class CandidateIdCard extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'candidate_id_card';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id'], 'integer'],
            [['candidate_id'], 'unique'],
            [['expiry_date'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
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
            'id' => 'ID',
            'candidate_id' => 'Candidate ID',
            'expiry_date' => 'Expiry Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At'
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
     * @inheritdoc
     * @return query\CandidateIdCardQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateIdCardQuery(get_called_class());
    }
}
