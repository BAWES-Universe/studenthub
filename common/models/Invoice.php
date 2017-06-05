<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "invoice".
 *
 * @property integer $invoice_id
 * @property integer $transfer_id
 * @property string $invoice_date
 * @property string $invoice_status
 *
 * @property Transfer $transfer
 */
class Invoice extends \yii\db\ActiveRecord
{
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
            [['transfer_id'], 'integer'],
            [['invoice_date'], 'safe'],
            [['invoice_status'], 'string'],
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::className(), 'targetAttribute' => ['transfer_id' => 'transfer_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'invoice_id' => 'Invoice ID',
            'transfer_id' => 'Transfer ID',
            'invoice_date' => 'Invoice Date',
            'invoice_status' => 'Invoice Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['transfer_id' => 'transfer_id']);
    }

    /* check salary transfer not paid
     * @return null
     */
    public function unpaidAlert()
    {
        //check only after salary day of every month

        if(date('d') <= Yii::$app->params['salaryDay'])
            return null;

        $companies = Company::find()
            ->where('DATE(company_created_at) < DATE("'.date('Y-m-1').'")')
            ->all();

        if(!$companies)
            return null;

        $result = [];

        foreach ($companies as $key => $value) 
        {
            $invoice = Invoice::find()
                ->innerJoin('transfer', 'transfer.transfer_id = invoice.transfer_id')
                ->where(['invoice_status' => 'paid'])
                ->andWhere(['in', 'transfer.company_id', [$value->company_id, $value->parent_company_id]])
                ->one();

            if(!$invoice)
            {
                $result[] = $value;
            }
        }

        Yii::$app->mailer->compose("companyNotPaid",
            [
                "companies" => $result,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Company not paid in current month')
            ->send();
    }

    /**
     * @inheritdoc
     * @return query\InvoiceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\InvoiceQuery(get_called_class());
    }
}
