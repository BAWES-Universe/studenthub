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
 * This is the model class for table "invoice".
 *
 * @property integer $invoice_id
 * @property integer $company_id
 * @property integer $invoice_status
 * @property number $total
 * @property string $invoice_created_at
 * @property string $invoice_updated_at
 *
 * @property Company $company
 * @property InvoiceCandidates[] $invoiceCandidates
 */
class Invoice extends \yii\db\ActiveRecord
{
    const STATUS_PAYMENT_SENT = 1;
    const STATUS_PAYMENT_RECEIVED = 2;
    const STATUS_SALARY_DISTRIBUTION_IN_PROGRESS = 3;
    const STATUS_TRANSFER_COMPLETE = 4;
    const STATUS_LOCK = 10;

    public function statusList()
    {
        return [
            STATUS_PAYMENT_SENT => 'Payment Sent',
            STATUS_PAYMENT_RECEIVED => 'Payment Received',
            STATUS_SALARY_DISTRIBUTION_IN_PROGRESS => 'Salary distribution in progress',
            STATUS_TRANSFER_COMPLETE => 'Transfer Completed',
            STATUS_LOCK => 'Lock'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'invoice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'invoice_status'], 'integer'],
            [['total'], 'number'],
            [['invoice_created_at', 'invoice_updated_at'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'invoice_created_at',
                'updatedAtAttribute' => 'invoice_updated_at',
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
            'invoice_id' => 'Invoice ID',
            'company_id' => 'Company ID',
            'invoice_status' => 'Invoice Status',
            'invoice_created_at' => 'Invoice Created At',
            'invoice_updated_at' => 'Invoice Updated At',
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
    public function getInvoiceCandidates()
    {
        return $this->hasMany(InvoiceCandidates::className(), ['invoice_id' => 'invoice_id']);
    }

    /* check salary transfer not paid 
     * @return null
     */
    public function unpaidAlert()
    {
        //check only after salary day of every month 

        if(date('d') <= Yii::$app->params['salaryDay']) 
            return null;

        /* list all companies not paid in current month + should not added in current month + don't list if parent 
         * company have paid for sub companies 
         */

        $companies = Company::find()
            ->where('NOT EXISTS (select 1 from invoice where invoice_status="'.self::STATUS_PAYMENT_RECEIVED.'" and company_id IN (company.company_id, company.parent_company_id))')
            ->andWhere('DATE(company_created_at) < DATE("'.date('Y-m-1').'")')
            ->all();

        if(!$companies)
            return null;

        Yii::$app->mailer->compose("companyNotPaid",
            [
                "companies" => $companies,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Company not paid in current month')
            ->send();
    }
}
