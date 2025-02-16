<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%candidate_work_history}}".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property string $contract_uuid
 * @property integer $store_id
 * @property integer $company_id
 * @property integer $parent_company_id
 * @property integer $staff_id
 * @property string $start_date
 * @property string $end_date
 * @property number $candidate_hourly_rate
 * @property number $company_hourly_rate
 * @property number $transfer_cost
 */
class CandidateWorkHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%candidate_work_history}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id', 'store_id', 'parent_company_id', 'company_id'], 'integer'],
            [['candidate_id', 'store_id', 'company_id'], 'required'],
            [['start_date', 'end_date'], 'safe'],
            [['transfer_cost'], 'number'],//, "max" => 1000
            [['candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['candidate_hourly_rate'], 'validateRate'],
            [['contract_uuid'], 'validateContract'],
            /*[['contract_uuid'], 'exist', 'skipOnError' => true,
                'targetClass' => Contract::class, 'targetAttribute' => ['contract_uuid' => 'contract_uuid']
            ],*/
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * @param $attribute
     * @param $params
     * @param $validator
     * @return bool|null
     */
    public function validateContract($attribute, $params, $validator)
    {
        $model = Contract::find()
            ->andWhere(['contract_uuid' => $this->contract_uuid])
            //contract either at parent or given child level
            ->andWhere([
                "OR",
                ["company_id" => $this->parent_company_id],
                ["company_id" => $this->company_id],
            ])
            ->one();

        if (!$model) {
            $this->addError('contract_uuid', "Invalid contract");
            return null;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateRate($attribute, $params, $validator) {

        if ($this->contract_uuid) {
            return true;
        }
        
        if($this->candidate_hourly_rate <= 0)
        {
            $this->addError('candidate_hourly_rate', Yii::t('candidate','Candidate hourly rate should be greater than 0.'));
            return null;
        }

        $max = 0;

        if($this->company_hourly_rate > 0) {
            $max = $this->company_hourly_rate;
        }
        else if($this->company && $this->company->company_hourly_rate)
        {
            $max = $this->company->company_hourly_rate;
        }
        elseif($this->company && $this->company->parentCompany)
        {
            $max =  $this->company->parentCompany->company_hourly_rate;
        }

        if($max && $this->candidate_hourly_rate > $max)
        {
            $this->addError('candidate_hourly_rate', Yii::t('candidate', "Candidate hourly rate should be less than or equal to {max}.", ['max' => $max]));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            "contract_uuid"=> Yii::t('app', 'Contract ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'parent_company_id' => Yii::t('app', 'parent company ID'),
            'company_id' => Yii::t('app', 'company ID'),
            'staff_id' => Yii::t('app', 'staff ID'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'candidate_hourly_rate' => Yii::t('app', 'Candidate Hourly Rate'),
            'company_hourly_rate' => Yii::t('app', 'Company Hourly Rate'),
            "transfer_cost" => Yii::t("app", "Transfer Cost")
        ];
    }

    /**
     * save candidate assigned history
     * @param $candidate
     * @return bool
     */
    public static function saveAssignedHistory(
        $candidate,
        $start_date = null,
        $company_hourly_rate = null,
        $transfer_cost = null,
        $contract_uuid = null
    ) {
        $model = new CandidateWorkHistory();
        $model->candidate_id = $candidate->candidate_id;
        $model->staff_id = Yii::$app->user->identity->getId();
        $model->store_id = $candidate->store_id;
        $model->company_id = (isset($candidate->company->company_id)) ? $candidate->company->company_id : null;
        $model->parent_company_id = (isset($candidate->company->parent_company_id)) ? $candidate->company->parent_company_id : $candidate->company->company_id;
        $model->start_date = $start_date != null ? date('Y-m-d', strtotime($start_date)): new \yii\db\Expression('NOW()');
        $model->contract_uuid = $contract_uuid;

        if ($contract_uuid) {

            if (!$model->contract || !$model->contract->amount) {
                $model->addError("contract_uuid", "Invalid contract");
            }

            $model->transfer_cost = $model->contract->transfer_cost;

            if ($model->contract->type == Contract::TYPE_HOURLY) {
                $model->candidate_hourly_rate = $model->contract->amount->candidate_hourly_rate;
                $model->company_hourly_rate = $model->contract->amount->company_hourly_rate;
            }

        } else {
            $model->candidate_hourly_rate = $candidate->candidate_hourly_rate;
            $model->company_hourly_rate = $company_hourly_rate;
            $model->transfer_cost = $transfer_cost;
        }

        if ($model->save()) {
            $candidate->updateAlgoliaIndex();
        }  

        return $model;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {

            $model = new CandidateNotification();
            $model->candidate_id = $this->candidate_id;
            $model->candidate_work_history_id = $this->id;
            $model->company_id = $this->company_id;
            $model->store_id = $this->store_id;
            $model->type = CandidateNotification::TYPE_ASSIGNMENT;
            $model->staff_id = $this->staff_id;
            if (!$model->save()) {
                Yii::error("Error saving notification: " . print_r($model->errors, true));
            }

        } /*else if (array_key_exists('end_date', $changedAttributes) && $this->end_date) {

            $model = new CandidateNotification();
            $model->candidate_id = $this->candidate_id;
            $model->candidate_work_history_id = $this->id;
            $model->company_id = $this->company_id;
            $model->store_id = $this->store_id;
            $model->staff_id = $this->staff_id;
            $model->type = CandidateNotification::TYPE_UNASSIGNED;
            if (!$model->save()) {
                Yii::error("Error saving notification: " . print_r($model->errors, true));
            }
        }*/

        return true;
    }

    /**
     * @return void
     */
    public function generateCertificate() {
        $model = new CandidateCertificate();
        $model->candidate_id = $this->candidate_id;
        $model->candidate_work_history_id = $this->id;
        $model->certificate_type = CandidateCertificate::TYPE_EXPERIENCE;
        $model->store_id = $this->store_id;
        $model->company_id = $this->company_id;
        $model->parent_company_id = $this->parent_company_id;
        $model->start_date = $this->start_date;
        $model->end_date = !empty($this->end_date) ? $this->end_date: new \yii\db\Expression('NOW()');;
        $model->staff_id = $this->staff_id;// Yii::$app->user->getId();

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

    /**
     * @inheritdoc
     */
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
     * @return string[]
     */
    public function extraFields()
    {
        return [
            'candidate',
            'store',
            'company',
            'parentCompany',
            "transferCost",
            "contract"
        ];
    }

    /**
     * @return mixed|null
     */
    public function getTransferCost() {

        if ($this->transfer_cost > 0) {
            return $this->transfer_cost;
        }

        // company level

        $company_id = empty($this->parent_company_id) ? $this->company_id:
            $this->parent_company_id;

        if (!isset(Yii::$app->params['arrTransferCosts'])) {

            $transferCosts = TransferCost::find()
                ->andWhere([
                    //"candidate_id" => $this->candidate_id,
                    "company_id" => $company_id
                ])
                ->all();

            $arrTransferCosts = Yii::$app->params['arrTransferCosts'] = ArrayHelper::map($transferCosts,
                "candidate_id", "transfer_cost");
        } else {
            $arrTransferCosts = Yii::$app->params['arrTransferCosts'];
        }

        if (
            isset($arrTransferCosts[$this->candidate_id]) &&
            $arrTransferCosts[$this->candidate_id] > 0
        ) {
            return $arrTransferCosts[$this->candidate_id];
        }

        return Yii::$app->params['transfer_cost']; //default transfer cost
    }

    /**
     * return firing by months
     * @return array
     */
    public static function getFiringChartData($company_id, $months = 12) {

        $dayTime =  60 * 60 * 24;

        $cacheDuration = $dayTime;// 1 day then delete from cache

        $cacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `candidate_work_history` WHERE end_date IS NULL AND company_id=' . $company_id,
        ]);

        $chart_data = [];

        $day = date("d") > 29? date("d") - 29: 0;

        for ($i = 0; $i < $months; $i++) {

            $time = strtotime( '-'.($months - $i).' month');

            //fix 29 days in feb
            if ($day > 0) {
                $time -= strtotime("-" . $day * $dayTime . " day");
            }

            $month = date('m', $time);

            $chart_data[$month] = array(
                'month'   => date('F', $time),
                'total' => 0
            );
        }

        $rows = CandidateWorkHistory::getDb()->cache(function($db) use($months, $company_id) {
            return CandidateWorkHistory::find()
                ->andWhere(['parent_company_id' => $company_id])
                ->select ('end_date, COUNT(*) as total')
                ->andWhere(new Expression("DATE(end_date) >= (NOW() - INTERVAL ".$months." MONTH)"))
                ->groupBy (new Expression('MONTH(end_date), YEAR(end_date)'))
                //->orderBy('end_date')
                ->asArray()
                ->all();
        }, $cacheDuration, $cacheDependency);

        foreach ($rows as $result) {
            $chart_data[date ('m', strtotime ($result['end_date']))] = array(
                'month' => Yii::t('app', date ('F', strtotime ($result['end_date']))),
                'total' => (int) $result['total']
            );
        }

        return array_values($chart_data);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\common\models\Company') {
        return $this->hasOne($className::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($className = '\common\models\Candidate') {
        return $this->hasOne($className::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($className = '\common\models\Company') {
        return $this->hasOne($className::className(), ['company_id' => 'parent_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\common\models\Store') {
        return $this->hasOne($className::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContract($className = '\common\models\Contract')
    {
        return $this->hasOne($className::className(), ['contract_uuid' => 'contract_uuid'])
            ->andWhere([
                "OR",
                ["company_id" => $this->parent_company_id],
                ["company_id" => $this->company_id],
            ]);
    }

    /**
     * @inheritdoc
     * @return CandidateWorkHistoryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateWorkHistoryQuery(get_called_class());
    }
}
