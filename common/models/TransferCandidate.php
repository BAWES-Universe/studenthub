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
 * @property string $hours
 * @property string $candidate_hourly_rate
 * @property string $company_hourly_rate
 * @property string $bonus
 * @property string $transfer_cost
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
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        //total amount need to pay to candidate 
        $fields['total_amount'] =  function($model) {
            return ($this->candidate_hourly_rate * $this->hours) + $this->bonus;
        };
        $fields['profit'] = function($model) {
            return (($this->company_hourly_rate - $this->candidate_hourly_rate) * $this->hours) - $this->transfer_cost;
        };
        
        // remove fields that contain sensitive information
        $field['payment_amount'] = $this->candidateTotal;

        return $fields;
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
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @return string
     */
    public function getCandidateTotal()
    {
        return ($this->candidate_hourly_rate * $this->hours) + $this->bonus;
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
