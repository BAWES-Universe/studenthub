<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "invoice".
 *
 * @property integer $invoice_id
 * @property integer $transfer_id
 * @property string $invoice_date
 * @property string $invoice_status
 * @property integer $deleted
 * @property Transfer $transfer
 */
class Invoice extends ActiveRecord
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
            [['transfer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::class, 'targetAttribute' => ['transfer_id' => 'transfer_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'invoice_id' => Yii::t('app','Invoice ID'),
            'transfer_id' =>Yii::t('app', 'Transfer ID'),
            'invoice_date' => Yii::t('app','Invoice Date'),
            'invoice_status' => Yii::t('app','Invoice Status'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // Total Invoice Amount
        $fields['invoice_total'] = function($model) {
            return $model->transfer->company_total;
        };
        
        unset($fields['deleted']);

        return $fields;
    }

    public function extraFields()
    {
        return [
            'company',
            'transfer',
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])
            ->via('transfer');
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'transfer_id']);
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
            ->andWhere('DATE(company_created_at) < DATE("'.date('Y-m-1').'")')
            ->all();

        if(!$companies)
            return null;

        $result = [];

        foreach ($companies as $key => $value)
        {
            $invoice = Invoice::find()
                ->innerJoin('transfer', 'transfer.transfer_id = invoice.transfer_id')
                ->andWhere(['invoice_status' => 'paid'])
                ->andWhere(['in', 'transfer.company_id', [$value->company_id, $value->parent_company_id]])
                ->one();

            if(!$invoice)
            {
                $result[] = $value;
            }
        }

        $ml = new MailLog();
        $ml->to = \Yii::$app->params['adminEmail'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Company not paid in current month';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer =Yii::$app->mailer->compose("companyNotPaid",
            [
                "companies" => $result,
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo(Yii::$app->params['adminEmail'])
            ->setSubject('Company not paid in current month');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return  $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
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
