<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "contract".
 *
 * @property string $contract_uuid
 * @property int $candidate_id
 * @property int $parent_company_id
 * @property int $company_id
 * @property int $store_id
 * @property string $type
 * @property string $detail
 * @property string $start_date
 * @property string $end_date
 * @property string $transfer_cost
 * @property string $currency_code
 * @property boolean $auto_generate
 * @property int $status
 * @property int $created_by
 * @property boolean $deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 * @property Staff $createdBy
 * @property FixedPriceContract[] $fixedPriceContract
 * @property HourlyContract[] $hourlyContract
 * @property MonthlySalaryContract[] $monthlySalaryContract
 */
class Contract extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const TYPE_FIXED_PRICE = "FIXED_PRICE";
    const TYPE_HOURLY = "HOURLY";
    const TYPE_MONTHLY_SALARY = "MONTHLY_SALARY";

    public $amountDetails;
    
    const SCENARIO_TEMPLATE = "template";
    const SCENARIO_ASSIGN = "assign";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contract';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id',  'type'], 'required'],
            [['candidate_id', 'store_id'], 'required', 'on' => self::SCENARIO_ASSIGN],
            [['company_id', 'status', 'created_by'], 'integer'],
            [['detail'], 'string'],
            [['deleted', 'auto_generate'], 'boolean'],
            [["status"], "default", "value" => 0],
            [['transfer_cost'],  "default", "value" => 0],
            [['currency_code'],  "default", "value" => "KWD"],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['transfer_cost'], 'number'],
            [['contract_uuid'], 'string', 'max' => 60],
            [['type'], 'string', 'max' => 255],
            [['currency_code'], 'string', 'max' => 3],
            [['contract_uuid'], 'unique'],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['parent_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['parent_company_id' => 'company_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['created_by' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => null,
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'contract_uuid',
                ],
                'value' => function() {
                    if(!$this->contract_uuid)
                        $this->contract_uuid = 'contract_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->contract_uuid;
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
            'contract_uuid' => Yii::t('app', 'Contract Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'type' => Yii::t('app', 'Type'),
            'detail' => Yii::t('app', 'Detail'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'transfer_cost' => Yii::t('app', 'Transfer Cost'),
            'currency_code' => Yii::t('app', 'Currency Code'),
            "auto_generate" => Yii::t('app', 'Auto Generate'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            "deleted" => Yii::t('app', 'Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            "amount",
            "store",
            "candidate",
            "parentCompany",
            "company"
        ]);
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert) {
        if ($this->scenario == self::SCENARIO_ASSIGN) {
            $this->company_id = $this->store->company_id;
            $this->parent_company_id = $this->store->company->parent_company_id;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return array|string[]|void|bool
     */
    public function afterSave($insert, $changedAttributes) {

        if (!$insert) { 

            if (isset($changedAttributes['deleted'])) {
                return true;
            }

            /*if (isset($changedAttributes['type']) && $this->amount) {
                $this->amount->delete();
            }*/
        }

        if ($this->amountDetails && !empty($this->amountDetails)) {
            $this->updateAmountDetails();
        }

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return array|string[]|void
     * @throws \yii\db\Exception
     */
    public function updateAmountDetails()
    {
        if ($this->type == Contract::TYPE_FIXED_PRICE) {

            //delete old amount details
            HourlyContract::deleteAll(['contract_uuid' => $this->contract_uuid]);
            MonthlySalaryContract::deleteAll(['contract_uuid' => $this->contract_uuid]);

            $fixedPriceContract = empty($this->amountDetails['fp_contract_uuid']) ?
                new FixedPriceContract(): FixedPriceContract::findOne($this->amountDetails['fp_contract_uuid']);

            if (!$fixedPriceContract) {
                $fixedPriceContract = new FixedPriceContract();
            }

            $fixedPriceContract->contract_uuid = $this->contract_uuid;
            $fixedPriceContract->candidate_total = $this->amountDetails['candidate_total'];
            $fixedPriceContract->company_total = $this->amountDetails['company_total'];
            $fixedPriceContract->completion_percentage = $this->amountDetails['completion_percentage'];

            if (!$fixedPriceContract->save()) {
                if (isset($fixedPriceContract->errors)) {
                    Yii::error($fixedPriceContract->errors);

                    return [
                        "operation" => "error",
                        "message" => $fixedPriceContract->getErrors()
                    ];
                } else {
                    Yii::error("We've faced a problem adding the contract amount, please contact us for assistance");

                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem adding the contract amount, please contact us for assistance"
                    ];
                }
            }

        } else if ($this->type ==  Contract::TYPE_HOURLY) {

            //delete old amount details
            FixedPriceContract::deleteAll(['contract_uuid' => $this->contract_uuid]);
            MonthlySalaryContract::deleteAll(['contract_uuid' => $this->contract_uuid]);

            $hourlyContract = empty($this->amountDetails['h_contract_uuid']) ?
                new HourlyContract(): HourlyContract::findOne($this->amountDetails['h_contract_uuid']);

            if (!$hourlyContract) {
                $hourlyContract = new HourlyContract();
            }

            $hourlyContract->contract_uuid = $this->contract_uuid;
            $hourlyContract->candidate_hourly_rate = $this->amountDetails['candidate_hourly_rate'];
            $hourlyContract->company_hourly_rate = $this->amountDetails['company_hourly_rate'];

            if (!$hourlyContract->save()) {

                if (isset($hourlyContract->errors)) {

                    Yii::error($hourlyContract->errors);

                    return [
                        "operation" => "error",
                        "message" => $hourlyContract->getErrors()
                    ];
                } else {

                    Yii::error("We've faced a problem adding the contract amount, please contact us for assistance");

                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem adding the contract amount, please contact us for assistance"
                    ];
                }
            }

        } else if ($this->type == Contract::TYPE_MONTHLY_SALARY) {

            //delete old amount details

            HourlyContract::deleteAll(['contract_uuid' => $this->contract_uuid]);
            FixedPriceContract::deleteAll(['contract_uuid' => $this->contract_uuid]);

            $monthlySalaryContract = empty($this->amountDetails['ms_contract_uuid']) ?
                new MonthlySalaryContract(): MonthlySalaryContract::findOne($this->amountDetails['ms_contract_uuid']);

            if (!$monthlySalaryContract) {
                $monthlySalaryContract = new MonthlySalaryContract();
            }

            $monthlySalaryContract->contract_uuid = $this->contract_uuid;
            $monthlySalaryContract->salary_day = $this->amountDetails['salary_day'];
            $monthlySalaryContract->candidate_total = $this->amountDetails['candidate_total'];
            $monthlySalaryContract->company_total = $this->amountDetails['company_total'];

            if (!$monthlySalaryContract->save()) {
                if (isset($monthlySalaryContract->errors)) {

                    Yii::error($monthlySalaryContract->errors);

                    return [
                        "operation" => "error",
                        "message" => $monthlySalaryContract->getErrors()
                    ];
                } else {

                    Yii::error("We've faced a problem adding the contract amount, please contact us for assistance");

                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem adding the contract, please contact us for assistance"
                    ];
                }
            }
        }
    }

    /**
     * @return void
     */
    public function generateCertificate() {
        $model = new CandidateCertificate();
        $model->candidate_id = $this->candidate_id;
        $model->contract_uuid = $this->contract_uuid;
        $model->certificate_type = CandidateCertificate::TYPE_EXPERIENCE;
        $model->store_id = $this->store_id;
        $model->company_id = $this->company_id;
        $model->parent_company_id = $this->parent_company_id;
        $model->start_date = $this->start_date;
        $model->end_date = !empty($this->end_date) ? $this->end_date: new \yii\db\Expression('NOW()');;
        $model->staff_id = $this->created_by;// Yii::$app->user->getId();

        if(!$model->save()) {
            echo "error";
            print_r($model->errors);
            Yii::error($model->errors);
        }
    }

    /**
     * save candidate un-assign record
     * @param $candidate
     * @return array
     */
    public static function saveUnAssignedHistory($candidate) {

        // check if candidate assigned today then delete assigned history
        if (CandidateWorkHistory::checkTotalHistory($candidate)) {
            //['candidate_id'=>$candidate->candidate_id,'start_date'=>date('Y-m-d')]

            $expression = "candidate_id='".$candidate->candidate_id."' AND 
                DATE(start_date) >= DATE('".date('Y-m-d')."')";

            return CandidateWorkHistory::updateAll(["deleted" => true],
                new Expression($expression)
            );

        } else {
            
            // else save unassigned history
            $model = CandidateWorkHistory::find()
                ->filterCandidate($candidate->candidate_id)
                ->emptyEndDate()
                ->one();

            if ($model) {
                
                $model->end_date  = new \yii\db\Expression('NOW()');

                if ($model->save()) {

                    $model->generateCertificate();

                    return [
                        'operation' =>'success',
                        'message' =>Yii::t('candidate','record successfully updated')
                    ];
                } else {
                    return [
                        'operation' =>'error',
                        'message' =>Yii::t('candidate','error while updating record. Please try again')
                    ];
                }
            } else {
                return [
                    'operation' =>'error',
                    'message' =>Yii::t('app','no record found')
                ];
            }
        }
    }

    /**
     * check is candidate assigned
     * today to store
     * @param $candidate
     * @return mixed
     */
    public static function checkTotalHistory($candidate) {
        $expression = "DATE(start_date) >= DATE('".date('Y-m-d')."')";

        return CandidateWorkHistory::find()
            ->filterCandidate($candidate->candidate_id)
            //->filterDate(date('Y-m-d'))
            ->andWhere(new Expression($expression))
            ->exists();
    }

    public function getStore($className = '\common\models\Store')
    {
        return $this->hasOne($className::className(), ['store_id' => 'store_id']);
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
    public function getCandidateWorkHistory($className = '\common\models\CandidateWorkHistory')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
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
    public function getParentCompany($className = '\common\models\Company')
    {
        return $this->hasOne($className::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($className = '\common\models\Staff')
    {
        return $this->hasOne($className::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return array|void|\yii\db\ActiveRecord|null
     */
    public function getAmount() {
        if ($this->type == self::TYPE_FIXED_PRICE) {
                return $this->getFixedPriceContract()
                    ->one();
        } else if ($this->type == self::TYPE_HOURLY) {
                return $this->getHourlyContract()
                    ->one();
        } else if ($this->type == self::TYPE_MONTHLY_SALARY) {
                return $this->getMonthlySalaryContract()
                    ->one();
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFixedPriceContract($className = '\common\models\FixedPriceContract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHourlyContract($className = '\common\models\HourlyContract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMonthlySalaryContract($className = '\common\models\MonthlySalaryContract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid']);
    }

    /**
     * @return query\ContractQuery
     */
    public static function find()
    {
        return new query\ContractQuery(get_called_class());
    }
}
