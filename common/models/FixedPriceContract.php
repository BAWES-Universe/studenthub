<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "fixed_price_contract".
 *
 * @property string $fp_contract_uuid
 * @property string $contract_uuid
 * @property string $candidate_total
 * @property string $company_total
 * @property number $completion_percentage
 *
 * @property Contract $contract
 */
class FixedPriceContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fixed_price_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_uuid', 'candidate_total', 'company_total'], 'required'],
            [['candidate_total', 'company_total'], 'number'],
            [['completion_percentage'], 'number', "max" => 100, "min" => 0],
            [['fp_contract_uuid', 'contract_uuid'], 'string', 'max' => 60],
            [['fp_contract_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'fp_contract_uuid',
                ],
                'value' => function() {
                    if(!$this->fp_contract_uuid)
                        $this->fp_contract_uuid = 'fp_contract_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->fp_contract_uuid;
                }
            ],
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

        /*CandidateWorkHistory::updateAll([
            "candidate_total" => $this->candidate_total,
            "company_total" => $this->company_total,
        ], [
            "contract_uuid" => $this->contract_uuid
        ]);*/

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fp_contract_uuid' => Yii::t('app', 'Fp Contract Uuid'),
            'contract_uuid' => Yii::t('app', 'Contract Uuid'),
            'candidate_total' => Yii::t('app', 'Candidate Total'),
            'company_total' => Yii::t('app', 'Company Total'),
            'completion_percentage' => Yii::t('app', 'Completion Percentage'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['candidate_total'] = function($model) {
            return (double) $model->candidate_total;
        };

        $fields['company_total'] = function($model) {
            return (double) $model->company_total;
        };

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContract($className = '\common\models\Contract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
    }
}
