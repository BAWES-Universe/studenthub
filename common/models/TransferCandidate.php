<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use common\models\Invoice;
use common\models\Store;
use common\models\Company;
use common\models\Candidate;
use common\models\Transfer;

/**
 * This is the model class for table "transfer_candidate".
 *
 * @property integer $tc_id
 * @property integer $transfer_id
 * @property integer $candidate_id
 * @property integer $store_id
 * @property string $store_name
 * @property integer $company_id
 * @property string $company_name
 * @property string $company_email
 * @property string $hours
 * @property string $candidate_hourly_rate
 * @property string $company_hourly_rate
 * @property string $bonus
 * @property string $transfer_cost
 * @property integer $paid
 * @property string $tc_created_at
 * @property string $tc_updated_at
 *
 * @property Candidate $candidate
 * @property Transfer $transfer
 * @property Invoice $Invoice
 */
class TransferCandidate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_candidate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transfer_id', 'candidate_id', 'store_id', 'company_id'], 'integer'],
            [['store_name', 'company_name'], 'string', 'max' => 100],
            [['company_email'], 'email'],
            [['hours', 'transfer_cost', 'bonus', 'candidate_hourly_rate', 'company_hourly_rate'], 'number'],
            [['tc_created_at', 'tc_updated_at'], 'safe'],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
        ];
    }

    /**
     * @return array
     */
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
            'store_id' => 'Store ID',
            'store_name' => 'Store Name',
            'company_id' => 'Company ID',
            'company_name' => 'Company Name',
            'company_email' => 'Company Email',
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
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // Total amount paid by company
        $fields['total_paid'] =  function($model) {
            return $this->totalPaidByCompany;
        };
        // Total amount we need to pay to candidate
        $fields['total_amount'] =  function($model) {
            return $this->totalPaidToCandidate;
        };
        // Our Profile
        $fields['profit'] = function($model) {
            return $this->profit;
        };

        /**
         * Format as numbers/double so API doesnt output as a string
         */
        $fields['hours'] = function($model) {
            return (double) $this->hours;
        };
        $fields['bonus'] = function($model) {
            return (double) $this->bonus;
        };
        $fields['transfer_cost'] = function($model) {
            return (double) $this->transfer_cost;
        };
        $fields['candidate_hourly_rate'] = function($model) {
            return (double) $this->candidate_hourly_rate;
        };
        $fields['company_hourly_rate'] = function($model) {
            return (double) $this->company_hourly_rate;
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'store',
            'company',
            'candidate',
            'transfer',
            'invoice'
        ];
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidToCandidate()
    {
        return ($this->candidate_hourly_rate * $this->hours) + $this->bonus;
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getTotalPaidByCompany()
    {
        return ($this->company_hourly_rate * $this->hours) + $this->bonus;
    }

    /**
     * Total amount that will be sent to the candidate
     * @return string
     */
    public function getProfit()
    {
        return (($this->company_hourly_rate - $this->candidate_hourly_rate) * $this->hours) - $this->transfer_cost;
    }

    /**
     * Relations below
     */

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass= "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\common\models\Invoice")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @inheritdoc
     * @return query\TransferCandidateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferCandidateQuery(get_called_class());
    }
}
