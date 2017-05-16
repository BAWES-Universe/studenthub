<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use common\models\Store;
use common\models\Company;
use common\models\Candidate;

/**
 * This is the model class for table "transfer".
 *
 * @property integer $transfer_id
 * @property integer $company_id
 * @property integer $transfer_status
 * @property number $total
 * @property string $transfer_created_at
 * @property string $transfer_updated_at
 *
 * @property Company $company
 * @property TransferCandidates[] $transferCandidates
 */
class Transfer extends \yii\db\ActiveRecord
{
    const STATUS_PAYMENT_SENT = 1;
    const STATUS_PAYMENT_RECEIVED = 2;
    const STATUS_SALARY_DISTRIBUTION_IN_PROGRESS = 3;
    const STATUS_TRANSFER_COMPLETE = 4;
    const STATUS_LOCK = 5;
    const STATUS_INITIATED = 10; // Draft

    public function statusList()
    {
        return [
            self::STATUS_PAYMENT_SENT => 'Payment Sent',
            self::STATUS_PAYMENT_RECEIVED => 'Payment Received',
            self::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS => 'Salary distribution in progress',
            self::STATUS_TRANSFER_COMPLETE => 'Transfer Completed',
            self::STATUS_LOCK => 'Locked',
            self::STATUS_INITIATED => 'Draft'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'transfer_status'], 'integer'],
            [['total', 'company_total'], 'number'],
            [['transfer_created_at', 'transfer_updated_at', 'payment_received_on'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'transfer_created_at',
                'updatedAtAttribute' => 'transfer_updated_at',
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
            'transfer_id' => 'Transfer ID',
            'company_id' => 'Company ID',
            'company_total' => 'Total for company',
            'total' => 'Total',
            'transfer_status' => 'Transfer Status',
            'transfer_created_at' => 'Transfer Created At',
            'transfer_updated_at' => 'Transfer Updated At',
            'payment_received_on' => 'Payment Received On'
        ];
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
    public function getTransferCandidates()
    {
        return $this->hasMany(TransferCandidates::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * Validate candidate array to initiate transfer
     */
    public function validate_candidates($company_id, $candidates)
    {
        $errors = [];

        if(!is_array($candidates)) {
            $candidates = [];
        }

        // check if empty field

        foreach ($candidates as $key => $value)
        {
            if(empty($value['candidate_id']))
            {
                $errors['candidate_id'][] = 'Candidate field require.';
                return $errors;
            }
        }

        //check for missing candidates

        $candidate_ids = ArrayHelper::map($candidates, 'candidate_id', 'candidate_id');

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company_id;

        // list all stores

        $stores = Store::find()
            ->where(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

        $missing = Candidate::find()
            ->where(['in', 'store_id', $store_ids])
            ->andWhere(['NOT IN', 'candidate_id', $candidate_ids])
            ->count();

        if($missing > 0)
        {
            $errors['candidate_id'][] = 'Missing ' . $missing . ' candidate(s).';
        }

        return $errors;
    }
}
