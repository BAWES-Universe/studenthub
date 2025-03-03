<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "transfer_cost".
 *
 * @property int $candidate_id
 * @property int $company_id
 * @property string $transfer_cost
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Company $company
 */
class TransferCost extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transfer_cost';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'company_id'], 'required'],//'transfer_cost'
            [['candidate_id', 'company_id'], 'integer'],
            [['transfer_cost'], 'number'],//, "max" => 1000
            [['transfer_cost'],  "default", "value" => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['candidate_id', 'company_id'], 'unique', 'targetAttribute' => ['candidate_id', 'company_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
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
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'transfer_cost' => Yii::t('app', 'Transfer Cost'),
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
}
