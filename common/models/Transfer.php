<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use kartik\mpdf\Pdf;
use yii\db\ActiveRecord;


/**
 * This is the model class for table "transfer".
 *
 * @property integer $transfer_id
 * @property integer $parent_transfer_id
 * @property integer $company_id
 * @property integer $total
 * @property integer $company_total
 * @property date $payment_received_on
 * @property integer $transfer_status
 * @property string $transfer_created_at
 * @property string $transfer_updated_at
 * @property number deleted
 * 
 * @property Company $company
 * @property TransferCandidate[] $transferCandidates
 * @property Invoice $invoice
 * @property Transfer[] $childTransfers
 * @property TransferCandidate[] $childTransferCandidates
 * @property Invoice $childTransferInvoices
 */
class Transfer extends ActiveRecord
{
    const STATUS_PAYMENT_SENT = 1;
    const STATUS_SALARY_DISTRIBUTION_IN_PROGRESS = 3;
    const STATUS_TRANSFER_COMPLETE = 4;
    const STATUS_LOCK = 5;
    const STATUS_INITIATED = 10; // Draft

    /**
     * @return array
     */
    public static function statusList()
    {
        return [
            self::STATUS_INITIATED => 'Draft',
            self::STATUS_LOCK => 'Locked',
            self::STATUS_PAYMENT_SENT => 'Payment Sent',
            self::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS => 'Received & Distributing Salary',
            self::STATUS_TRANSFER_COMPLETE => 'Transfer Completed',
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
            [['transfer_status'], 'validateTransferStatus'],
            [['total', 'company_total'], 'number'],
            [['transfer_created_at', 'transfer_updated_at', 'payment_received_on'], 'safe'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * find if transfer status invalid
     */
    public function validateTransferStatus()
    {
        $arrStatus = array_keys(self::statusList());
        
        if(!in_array($this->transfer_status, $arrStatus)) { 
            $this->addError('transfer_status', "Invalid Transfer Status.");
        } 
    }
        
    /**
     * @return array
     */
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
            'transfer_id' => Yii::t('app','Transfer ID'),
            'company_id' => Yii::t('app','Company ID'),
            'company_total' => Yii::t('app','Total for company'),
            'total' => Yii::t('app','Total'),
            'transfer_status' => Yii::t('app','Transfer Status'),
            'transfer_created_at' => Yii::t('app','Transfer Created At'),
            'transfer_updated_at' => Yii::t('app','Transfer Updated At'),
            'payment_received_on' => Yii::t('app','Payment Received On')
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['transfer_created_at'] = function($model) {
            return Yii::$app->formatter->asDateTime($model->transfer_created_at);
        };
        $fields['transfer_updated_at'] = function($model) {
            return Yii::$app->formatter->asDateTime($model->transfer_updated_at);
        };
        $fields['payment_received_on'] = function($model) {
            return $model->payment_received_on? Yii::$app->formatter->asDate($model->payment_received_on) : $model->payment_received_on;
        };

        $fields['transfer_status'] = function($model) {
            return (int) $model->transfer_status;
        };
        
        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'invoices',
            'transferCandidates',
            'childTransfers'
        ];
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
     * Get all unpaid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getUnPaidTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->getTransferCandidates($modelClass)
            ->andWhere(['paid' => 0]);
    }

    /**
     * Get all TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        // If this is a child transfer return all TransferCandidate records
        // belonging to its parent transfer
        if($this->parent_transfer_id)
        {
            return $this->getParentTransferCandidates($modelClass)
                ->andWhere(['company_id' => $this->company_id]);
        }

        // Otherwise return all TransferCandidate records belonging to this transfer
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\common\models\Invoice")
    {
        // If this is a parent transfer, return all invoices belonging to its children transfers
        if($this->childTransfers)
            return $this->getChildTransferInvoices($modelClass);

        // Otherwise return all invoices belonging to it
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id']);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id'=>'parent_transfer_id'])
            ->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('parentTransfer');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getChildTransfers($modelClass = "\common\models\Transfer")
    {
        return $this->hasMany($modelClass::className(), ['parent_transfer_id'=>'transfer_id']);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\common\models\Invoice")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('childTransfers');
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->via('childTransfers');
    }

    /**
     * Generate Invoice for Tranfer
     * @return integer invoice_id
     */
    public function generateInvoice()
    {
        $invoice = Invoice::findOne(['transfer_id' => $this->transfer_id]);

        if(!$invoice) {
            $invoice = new Invoice;
            $invoice->transfer_id = $this->transfer_id;
            $invoice->invoice_date = date('Y-m-d');
            $invoice->invoice_status = 'unpaid';
            $invoice->save();
        }

        return $invoice->invoice_id;
    }

    /**
     * Receipt/Invoice Mail by transfer id to recipient
     * and also forward to finance@bawes.net
     * @param $template invoice/receipt
     * @return array|bool
     */
    public function notify($template = 'invoice')
    {
        $invoices = Invoice::find()
            ->byTransfer($this->transfer_id)
            ->all();

        if(!$invoices) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }

        Yii::$app->controller->layout = 'pdf';
        
        $subject = [];

        $message = Yii::$app->mailer->compose($template.'-attachment',['invoices' => $invoices]);
        
        $message->setFrom([Yii::$app->params['invoiceFrom'] => 'Khalid Al-Mutawa']);
        
        $i=1;
        $invoice_id = 0;

        foreach ($invoices as $invoice)
        {
            $invoice_id = $invoice->invoice_id;

            $viewPath = '@admin/modules/v1/views/transfer/' . $template . '.php';

            $content = Yii::$app->controller->render($viewPath, [
                'invoice' => $invoice,
            ]);

            $pdf = new Pdf([
                'mode' => Pdf::MODE_UTF8,
                //UTF mode for arabic language
                'format' => Pdf::FORMAT_A4,
                // portrait orientation
                'orientation' => Pdf::ORIENT_PORTRAIT,
                // stream to browser inline
                'destination' => Pdf::DEST_BROWSER,
                // your html content input
                'content' => $content,
                // any css to be embedded if required
                'cssInline' => 'body {line-height: 1.85714286em;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #666666;} h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #252525;font-variant-ligatures: common-ligatures;margin-top: 0;margin-bottom: 0;}',
                // set mPDF properties on the fly
                'options' => [],//['title' => 'Booking #'.$id],
                // call mPDF methods on the fly
            ]);
            
            $pdfAttachment = $pdf->output($content, $template.'-'.$invoice_id.'.pdf', 'S');
            
            $email = (isset($invoice->transfer->company->parentCompany->company_email)) ? 
                $invoice->transfer->company->parentCompany->company_email :  $invoice->transfer->company->company_email;
            
            $message->attachContent($pdfAttachment,['fileName' => $template.'-#'.$invoice_id.'.pdf', 'contentType' => 'application/pdf']);
            
            $i++;

            $subject[] = '#'.$invoice_id;
        }

        if ( $template == 'invoice' ) {
            $subjectLine = Yii::t('app','StudentHub {numReceipts, plural, =1{invoice} other{Invoices}} {invoicesList} ', ['numReceipts' => count($invoices),'invoicesList'=>implode(', ',$subject)]);
        }  else {
            $subjectLine = Yii::t('app','StudentHub {numReceipts, plural, =1{Receipt} other{Receipts}} {invoicesList} ', ['numReceipts' => count($invoices),'invoicesList'=>implode(', ',$subject)]);
        }
        
        if(YII_ENV != 'prod') {
            $subjectLine = '[Fake] [Ignore] ' . $subjectLine;
        }

        return $message->setTo($email)
            ->setCc(Yii::$app->params['invoiceCC'])
            ->setSubject($subjectLine)
            ->send();
    }

    /**
     * @inheritdoc
     * @return query\TransferQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\TransferQuery(get_called_class());
    }

    /**
     * mobile notification on new transfer creation
     */
    public function sendNewTransferNotification($transferCandidate)
    {
        $total = ($transferCandidate->candidate_hourly_rate * $transferCandidate->hours) + $transferCandidate->bonus - $transferCandidate->bonus_commission;
        
        $heading = Yii::t('app', 'New transfer initiated');
        $subtitle = "@ " . $transferCandidate->store->store_name . ', ' . $transferCandidate->company->company_name;
        $content = 'KWD ' . number_format($total, 3);

        $filters = [
            [
                "field" => "tag",
                "key" => "candidate_id",
                "relation" => "=",
                "value" => $transferCandidate->candidate_id
            ]
        ];

        $params = [
            'subject' => 'transfer',
            'transfer_id' => $this->transfer_id
        ];

        MobileNotification::notifyCandidate($heading, $params, $filters, $subtitle, $content);
    }
}
