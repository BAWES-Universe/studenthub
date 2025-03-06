<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "hourly_contract".
 *
 * @property string $h_contract_uuid
 * @property string $contract_uuid
 * @property string $candidate_hourly_rate
 * @property string $company_hourly_rate
 *
 * @property Contract $contract
 */
class HourlyContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'hourly_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_uuid', 'candidate_hourly_rate', 'company_hourly_rate'], 'required'],
            [['candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['h_contract_uuid', 'contract_uuid'], 'string', 'max' => 60],
            [['h_contract_uuid'], 'unique'],
            [['contract_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Contract::class, 'targetAttribute' => ['contract_uuid' => 'contract_uuid']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'h_contract_uuid',
                ],
                'value' => function() {
                    if(!$this->h_contract_uuid)
                        $this->h_contract_uuid = 'h_contract_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->h_contract_uuid;
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
            'h_contract_uuid' => Yii::t('app', 'H Contract Uuid'),
            'contract_uuid' => Yii::t('app', 'Contract Uuid'),
            'candidate_hourly_rate' => Yii::t('app', 'Candidate Hourly Rate'),
            'company_hourly_rate' => Yii::t('app', 'Company Hourly Rate'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['candidate_hourly_rate'] = function($model) {
            return (double) $model->candidate_hourly_rate;
        };

        $fields['company_hourly_rate'] = function($model) {
            return (double) $model->company_hourly_rate;
        };

        return $fields;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        CandidateWorkHistory::updateAll([
            "candidate_hourly_rate" => $this->candidate_hourly_rate,
            "company_hourly_rate" => $this->company_hourly_rate,
            "transfer_cost" => $this->contract->transfer_cost
        ], [
            "contract_uuid" => $this->contract_uuid
        ]);

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContract($className = '\common\models\Contract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
    }
}
