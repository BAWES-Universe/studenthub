<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "monthly_salary_contract".
 *
 * @property string $ms_contract_uuid
 * @property string $contract_uuid
 * @property string $candidate_total
 * @property string $company_total
 * @property int $salary_day e.g., 5th of the month
 *
 * @property Contract $contract
 */
class MonthlySalaryContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'monthly_salary_contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_uuid', 'candidate_total', 'company_total'], 'required'],
            [['candidate_total', 'company_total'], 'number'],
            [['salary_day'], 'integer'],
            [['ms_contract_uuid', 'contract_uuid'], 'string', 'max' => 60],
            [['ms_contract_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ms_contract_uuid',
                ],
                'value' => function() {
                    if(!$this->ms_contract_uuid)
                        $this->ms_contract_uuid = 'ms_contract_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->ms_contract_uuid;
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
            'ms_contract_uuid' => Yii::t('app', 'Ms Contract Uuid'),
            'contract_uuid' => Yii::t('app', 'Contract Uuid'),
            'candidate_total' => Yii::t('app', 'Candidate Total'),
            'company_total' => Yii::t('app', 'Company Total'),
            'salary_day' => Yii::t('app', 'Salary Day'),
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
