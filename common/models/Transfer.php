<?php

namespace common\models;

use Yii;
use yii\base\Exception;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use kartik\mpdf\Pdf;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use Segment\Segment;

/**
 * This is the model class for table "transfer".
 *
 * @property integer $transfer_id
 * @property integer $parent_transfer_id
 * @property integer $company_id
 * @property string $contract_uuid
 * @property string $contract_type
 * @property integer $total
 * @property integer $company_total
 * @property integer $transfer_cost
 * @property string $currency_code
 * @property date $payment_received_on
 * @property integer $transfer_status
 * @property date $start_date
 * @property date $end_date
 * @property string $transfer_created_by
 * @property string $transfer_updated_by
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
    //for transfer create form
    public $candidates = [];

    const STATUS_CANCEL = 0;
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
            self::STATUS_CANCEL => 'Cancel',
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
            [['start_date', 'end_date', "currency_code"], 'required'],
            [['transfer_status'], 'validateTransferStatus'],
            [['total', 'company_total', "transfer_cost"], 'number'],
            ['start_date', 'validateDates'],
            ['contract_uuid', 'validateContract'],
            [["currency_code", "contract_type"], "string"],
            [['transfer_created_at', 'transfer_updated_at', 'payment_received_on','start_date','end_date'], 'safe'],
            [['contract_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Contract::class, 'targetAttribute' => ['contract_uuid' => 'contract_uuid']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @return void
     */
    public function validateContract() {

        //validate contract date range matched transfer date

        if ($this->contract) {

            if ($this->contract->start_date) {
                if (strtotime($this->start_date) < strtotime($this->contract->start_date)) {
                    $this->addError('start_date', 'Start date should be greater then or equal to contract start date');
                }
            }

            if ($this->contract->end_date) {
                if (strtotime($this->end_date) > strtotime($this->contract->end_date)) {
                    $this->addError('end_date', 'End date should be less then or equal to contract end date');
                }
            }
        }
    }

    /**
     * @return void
     */
    public function validateDates() {
        if($this->end_date && $this->start_date && strtotime($this->end_date) <= strtotime($this->start_date)) {
            $this->addError('start_date','End date should be greater then start date');
        }
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
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'transfer_created_by',
                'updatedByAttribute' => 'transfer_updated_by',
                'value' => function() {

                    //if user available and it's staff

                    if(
                        isset(Yii::$app->components['user']['identityClass']) &&
                        Yii::$app->user->identity instanceof Staff
                    )
                        return Yii::$app->user->getId();
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'transfer_created_at',
                'updatedAtAttribute' => 'transfer_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param $insert
     * @return bool|void
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        if(!$this->currency_code) {

            if($this->parent_transfer_id) {

                $parent = $this->getParentTransfer()->one();

                if($parent)
                    $this->currency_code = $parent->currency_code;
            }

            //no parent

            if(!$this->currency_code) {
                $this->currency_code = "KWD";
            }
        }

        if (!$this->contract_type && $this->contract) {
            $this->contract_type = $this->contract->type;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transfer_id' => Yii::t('app','Transfer ID'),
            'company_id' => Yii::t('app','Company ID'),
            'company_total' => Yii::t('app','Total for Company'),
            "currency_code" => Yii::t('app','Currency Code'),
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

        $fields['transfer_created_at_unix'] = function($model) {
            return date('Y-m-d',strtotime($model->transfer_created_at));
        };

        $fields['transfer_updated_at_unix'] = function($model) {
            return $model->transfer_updated_at?
                date('Y-m-d',strtotime($model->transfer_updated_at)): null;
        };

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

        $fields['transfer_status_name'] = function($model) {
            $arrStatus = self::statusList();
            return isset($arrStatus[$model->transfer_status])? $arrStatus[$model->transfer_status]: null;
        };
        
        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (
            array_key_exists('transfer_status', $changedAttributes) &&
            $this->transfer_status == Transfer::STATUS_PAYMENT_SENT
            //$changedAttributes['transfer_status'] == self::STATUS_LOCK
        ) {
            $this->company->last_payment_datetime = new Expression("NOW()");
            $this->company->save(false);
        }

        return true;
    }

    /**
     * is current transfer suspicious of manipulation in transfer amount?
     * @return bool
     */
    public function getIsSuspicious() {

        return TransferCandidate::find()
            ->innerJoinWith('transferFileEntry')
            ->andWhere(new Expression('credit_amount != candidate_total'))
            ->andWhere(['transfer_id' => $this->transfer_id])
            ->exists();

        /*$totalPaid = TransferFileEntry::find()
            ->andWhere(['debit_narrative' => $this->transfer_id])
            ->sum('credit_amount');

        return $totalPaid != $this->total;*/
    }

    /**
     * total amount paid
     * @return bool|int|mixed|string|null
     */
    public function getTransferFileTotal() {
        return TransferFileEntry::find()
            ->andWhere(['debit_narrative' => $this->transfer_id])
            ->sum('credit_amount');
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            "contract",
            'createdBy',
            'updatedBy',
            'invoices',
            'company',
            "contract",
            'invoices',
            'transferCandidates',
            'childTransfers',
            'paidTransferCandidates',
            'isSuspicious',
            'transferFileTotal',
            'profit',
            'revenue'
        ];
    }

    /**
     * generate graph data
     * @param $months
     * @return array
     */
    public static function getTotalsByMonths($months)
    {
        $data = [];

        $date_start = date('Y-m-d', strtotime('first day of -'.$months.' month'));

        $date_end = date('Y-m-d', strtotime('last day of previous month'));

        for ($i = 0; $i <= $months; $i++) {

            $month = date('F', strtotime('-'.($months - $i).' month'));

            $data[$month] = array(
                'month' => date('F', strtotime('-'.($months - $i).' month')),
                'gross_profit' => 0,
                'revenue' => 0,
                'salary' => 0,
                'expense' => 0,
                'net_profit' => 0
            );
        }

        $rows = self::find()
            //->filterPaymentReceived()
            ->select(new Expression('transfer_created_at, SUM(transfer.company_total) as revenue, SUM(transfer.company_total - transfer.total) as gross_profit'))
            //->andWhere('`transfer_created_at` >= (NOW() - INTERVAL '.$months.' MONTH)')
            ->andWhere('DATE(`transfer_created_at`) >= DATE("'.$date_start.'") AND DATE(`transfer_created_at`) <= DATE("'.$date_end.'")')
            ->groupBy(new Expression('MONTH(transfer_created_at)'))
            ->asArray()
            ->all();

        foreach ($rows as $result) {

            $data[date ('F', strtotime ($result['transfer_created_at']))] = array(
                'month' => date ('F', strtotime ($result['transfer_created_at'])),
                'gross_profit' => (double) $result['gross_profit'],
                'revenue' => (double) $result['revenue'],
                'salary' => 0,
                'expense' => 0,
                'net_profit' => $result['gross_profit']
            );
        }

        $salaries = StaffSalary::find()
            ->select(new Expression('salary_date, SUM(salary) as salary'))
            //->andWhere('`transfer_created_at` >= (NOW() - INTERVAL '.$months.' MONTH)')
            ->andWhere('DATE(`salary_date`) >= DATE("'.$date_start.'") AND DATE(`salary_date`) <= DATE("'.$date_end.'")')
            ->groupBy(new Expression('MONTH(salary_date)'))
            ->asArray()
            ->all();

        foreach ($salaries as $result) {

            $row = $data[date ('F', strtotime ($result['salary_date']))];

            $data[date ('F', strtotime ($result['salary_date']))] = array_merge ([
                    'salary' => (double) $result['salary'],
                    'net_profit'=> $row['gross_profit'] - $result['salary']
                ], $row
            );
        }

        $expenses = Expense::find()
            ->select(new Expression('created_at, SUM(amount) as expense'))
            //->andWhere('`transfer_created_at` >= (NOW() - INTERVAL '.$months.' MONTH)')
            ->andWhere('DATE(`created_at`) >= DATE("'.$date_start.'") AND DATE(`created_at`) <= DATE("'.$date_end.'")')
            ->groupBy(new Expression('MONTH(created_at)'))
            ->asArray()
            ->all();

        foreach ($expenses as $result) {

            $row = $data[date ('F', strtotime ($result['created_at']))];

            $data[date ('F', strtotime ($result['created_at']))] = array_merge ([
                    'expense' => (double) $result['expense'],
                    'net_profit'=> $row['gross_profit'] - $row['salary'] - $result['expense']
                ], $row
            );
        }

        //format for graph

        $series = [
            [
                "name" => "Revenue",
                "data" => array_values(ArrayHelper::getColumn ($data, 'revenue'))
            ],
            [
                "name" => "Gross Profit",
                "data" => array_values(ArrayHelper::getColumn ($data, 'gross_profit'))
            ],
            [
                "name" => "Salary",
                "data" => array_values(ArrayHelper::getColumn ($data, 'salary'))
            ],
            [
                "name" => "Expense",
                "data" => array_values(ArrayHelper::getColumn ($data, 'expense'))
            ],
            [
                "name" => "Net Profit",
                "data" => array_values(ArrayHelper::getColumn ($data, 'net_profit'))
            ],
        ];

        return [
            'series' => $series,
            'categories' => array_keys ($data)
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContract($modelClass = "\common\models\Contract")
    {
        return $this->hasOne($modelClass::className(), ['contract_uuid' => 'contract_uuid']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'transfer_created_by']);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'transfer_updated_by']);
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
     * Get all paid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getPaidTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->getTransferCandidates($modelClass)
            ->andWhere(['paid' => 1]);
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

        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id'])
            ->andWhere(['transfer_candidate.deleted' => 0]);
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
        return $this->hasMany($modelClass::className(), ['transfer_id' => 'transfer_id'])
            ->andWhere(['{{%invoice}}.deleted'=>0]);
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
        return $this->hasMany($modelClass::className(), ['parent_transfer_id'=>'transfer_id'])
            ->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\common\models\Invoice")
    {
        return $this->hasMany($modelClass::className(), ['transfer_id'=>'transfer_id'])
            ->andWhere(['{{%invoice}}.deleted'=>0])
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
            ->andWhere(['{{%transfer_candidate}}.deleted'=>0])
            ->via('childTransfers');
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFileEntries($modelClass = "\common\models\TransferFileEntry")
    {
        return $this->hasMany($modelClass::className(), ['debit_narrative' => 'transfer_id']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency($modelClass = "\common\models\Currency")
    {
        return $this->hasMany($modelClass::className(), ['code' => 'currency_code']);
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

        Yii::$app->controller->layout = '@common/mail/layouts/pdf';

        $subject = [];

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $message = Yii::$app->mailer->compose($template.'-attachment',[
            'invoices' => $invoices,
            'company' => $this->company,
            'logo' => Yii::$app->urlManagerStaff->createUrl(
                '../images/logo.png'
            )
        ]);
        
        if(\Yii::$app->params['elasticMailIpPool']) {
            $message->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        $message->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']]);
        
        $i=1;
        $invoice_id = 0;

        $emails = [];

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

            $name = (isset($invoice->transfer->company->parentCompany->company_common_name_en)) ?
                $invoice->transfer->company->parentCompany->company_common_name_en :  $invoice->transfer->company->company_common_name_en;

            $message->attachContent($pdfAttachment,[
                'fileName' => $template.'-#'.$invoice_id.'.pdf',
                'contentType' => 'application/pdf'
            ]);
            
            $i++;

            $subject[] = '#'.$invoice_id;

            //emails of all contacts in invoice company 

            $subQuery = CompanyContact::find()
                ->select('contact_uuid')
                ->andWhere([
                    'company_id' => $invoice->transfer->company_id
                ]);

            $contacts = Contact::find()
                ->andWhere(['contact_receive_email' => 1])
                ->andWhere(['in', 'contact_uuid', $subQuery])
                ->andWhere(new Expression('contact_email IS NOT NULL'))
                //->andWhere(['<>', 'contact_email', null])
                ->all();

            $emails = array_merge($emails, ArrayHelper::getColumn($contacts, 'contact_email'));

            //company's contact email

            if($invoice->transfer->company->company_email)
                $emails[] = $invoice->transfer->company->company_email;
        }

        //if parent company, add company contact email if any + parent company's contact persons' email

        if($invoices[0]->transfer->company->parent_company_id) {

            if($invoices[0]->transfer->company->parentCompany->company_email)
                $emails[] = $invoices[0]->transfer->company->parentCompany->company_email;

            //add parent company contact 

            $subQuery = CompanyContact::find()
                ->select('contact_uuid')
                ->andWhere([
                    'company_id' =>$invoice->transfer->company->parent_company_id
                ]);

            $contacts = Contact::find()
                ->andWhere(['contact_receive_email' => 1])
                ->andWhere(['in', 'contact_uuid', $subQuery])
                //->andWhere(['<>', 'contact_email', null])
                ->andWhere(new Expression('contact_email IS NOT NULL'))
                ->all();

            $emails = array_merge($emails, ArrayHelper::getColumn($contacts, 'contact_email'));
        }

        if ( $template == 'invoice' ) {
            $subjectLine = Yii::t('app','StudentHub {numReceipts, plural, =1{invoice} other{Invoices}} {invoicesList} ', ['numReceipts' => count($invoices),'invoicesList'=>implode(', ',$subject)]);
        }  else {
            $subjectLine = Yii::t('app','{name}: Thank you for your payment',['name'=>$name]);
        }
        
        if(YII_ENV != 'prod') {
            $subjectLine = '[Fake] [Ignore] ' . $subjectLine;
        }

        $emails = array_unique($emails); //remove duplicate

        foreach ($emails as $email) {
            $ml = new MailLog();
            $ml->to = $email;
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = $subjectLine;
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }
        }

        /**
         * sending invoice to internal team, but receipt to all
         */
        if ($template == "invoice") {
            $message->setTo(Yii::$app->params['invoiceCC'])
                ->setCc([Yii::$app->params['operationsEmail']]);
        } else {
            $message->setTo($emails)
                ->setCc([Yii::$app->params['invoiceCC'], Yii::$app->params['operationsEmail']]);
        }

        $message->setSubject($subjectLine);

        try {
            return $message->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * mobile notification on new transfer creation
     */
    public function sendNewTransferNotification($transferCandidate)
    {
        $total = $transferCandidate->candidate_total;
        //($transferCandidate->candidate_hourly_rate * $transferCandidate->hours) + $transferCandidate->bonus - $transferCandidate->bonus_commission;
        
        $heading = Yii::t('app', 'New transfer initiated');
        $subtitle = "@ " . $transferCandidate->store->store_name . ', ' . $transferCandidate->company->company_name;
        $content = $transferCandidate->transfer->currency_code . ' ' . number_format($total, 3);

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

        $model = new CandidateNotification();
        $model->candidate_id = $transferCandidate->candidate_id;
        $model->tc_id = $transferCandidate->tc_id;
        $model->company_id = $this->company_id;
        $model->store_id = $transferCandidate->store_id;
        $model->type = CandidateNotification::TYPE_TRANSFER_INIT;
        $model->appeal_uuid = null;
        if (!$model->save()) {
            Yii::error("Error saving notification: " . print_r($model->errors, true));
        }
    }

    /**
     * @param $sub_companies
     * @param $model
     */
    public static function generateSubCompanyTransfer($sub_companies, $model) {

        if ($sub_companies) {

            foreach ($sub_companies as $key => $company) {

                //move transfer to transfer
                $transfer = self::findOne([
                    'parent_transfer_id' => $model->transfer_id,
                    'company_id' => $company['company_id']
                ]);

                if (!$transfer) {
                    $transfer = new Transfer;
                    $transfer->attributes = $model->attributes;
                    $transfer->parent_transfer_id = $model->transfer_id;
                    $transfer->company_id = $company['company_id'];
                    $transfer->transfer_status = Transfer::STATUS_LOCK;
                    $transfer->currency_code = $model->currency_code;
                    $transfer->save(false);
                }

                $total = $company_total = $transfer_cost = 0;

                //remove old candidates if exists

                TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

                // transfer candidate for current company

                $candidates = TransferCandidate::find()
                    ->candidatesByTransfer($model->transfer_id)
                    ->filterCompanyId($company['company_id'])
                    ->asArray()
                    ->all();

                foreach ($candidates as $key_one => $value) {
                    /*if ((int)$value['minutes']>0 || (int)$value['seconds']>0 ||
                        (int)$value['hours']>0 || $value['bonus'] > 0) {

                        //total amount we will pay to bank
                        $total += $value['bonus'] - $value['bonus_commission'] +
                            ($value['hours'] * $value['candidate_hourly_rate']) +
                            ($value['minutes'] * ($value['candidate_hourly_rate'] / 60)) +
                            ($value['seconds'] * ($value['candidate_hourly_rate'] / 3600));

                        //total amount company will pay to us
                        $company_total += $value['bonus'] + ($value['hours'] * $value['company_hourly_rate'])
                            + ($value['minutes'] * ($value['company_hourly_rate'] / 60))
                            + ($value['seconds'] * ($value['company_hourly_rate'] / 3600))
                            + $value['transfer_cost'];

                        $transfer_cost += $value['transfer_cost'];
                    }*/

                    $total += $value['candidate_total'];

                    $company_total += $value['company_total'];

                    $transfer_cost += $value['transfer_cost'];
                }

                // Save total in transfer
                $transfer->transfer_cost = $transfer_cost;
                $transfer->company_total = $company_total;
                $transfer->total = $total;
                $transfer->save(false);

                // Generate invoice for each transfer
                $transfer->generateTransferInvoice();
            }
        }
    }

    /**
     * Revenue
     * @return string
     */
    public function getRevenue()
    {
        return $this->company_total;
    }

    /**
     * Revenue
     * @return string
     */
    public function getProfit()
    {
        return $this->company_total - $this->total;
    }

    /**
     * @return bool
     */
    public function generateTransferInvoice() {

        $invoice = Invoice::findOne(['transfer_id' => $this->transfer_id]);

        if(!$invoice) {
            $invoice = new Invoice;
            $invoice->transfer_id = $this->transfer_id;
            $invoice->invoice_date = date('Y-m-d');
            $invoice->invoice_status = 'unpaid';
            return $invoice->save();
        }

        return false; // in case if invoice exist
    }

    /**
     * @return bool
     */
    public function generateEachCompanyTransfer() {

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($this->transfer_id)
            ->groupByCompany($this->company_id)
            ->all();

        // condition to check if current company has existing sub companies.

        $sub_companies = (
            $sub_companies &&
            (isset($this->company->subCompanies)) &&
            count($this->company->subCompanies)>0
        ) ? $sub_companies : false;

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for sub companies
         */
        if (!$sub_companies) {
            $this->generateTransferInvoice();
        }

        /**
         * if transfer initiated by parent company split it for each
         * sub companies
         */
        if ($sub_companies) {
            Transfer::generateSubCompanyTransfer($sub_companies, $this);
        }

        return true;
    }

    /**
     * Mark transfer status to "Payment Sent"
     * This is only possible after the status has been marked as `Locked`
     * @throws yii\base\Exception
     */
    public function paymentSent()
    {
        if($this->transfer_status == Transfer::STATUS_PAYMENT_SENT)
        {
            throw new Exception('Transfer already marked as "Payment Sent"');
        }

        if($this->transfer_status != Transfer::STATUS_LOCK)
        {
            throw new Exception('Transfer status should be "Locked" to send it!');
        }

        $this->transfer_status = Transfer::STATUS_PAYMENT_SENT;

        return $this->save(false);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function cancel()
    {
        if($this->transfer_status == Transfer::STATUS_CANCEL)
        {
            throw new Exception('Transfer already cancelled.');
        }

        if($this->transfer_status != Transfer::STATUS_INITIATED)
        {
            throw new Exception('Transfer status need to be "Initiated" to cancel it!');
        }

        $this->transfer_status = Transfer::STATUS_CANCEL;

        if(isset(Yii::$app->components['user']['identityClass']) && Yii::$app->user->identity instanceof Staff) {
            $note = new Note;
            $note->note_type = Note::TYPE_INTERNAL_NOTE;
            $note->company_id = $this->company_id;
            $note->note_text = "I have cancelled a transfer for " . $this->company->company_common_name_en . " with a total of " . $this->company_total . " KD";
            $note->save();
        }

        return $this->save(false);
    }

    /**
     * Mark transfer status to locked
     * This is only possible after the status has been marked as `Payment Sent` by mistake
     * @throws yii\base\Exception
     */
    public function lock()
    {
        if($this->transfer_status == Transfer::STATUS_LOCK)
        {
            throw new Exception('Transfer already locked.');
        }

        if($this->transfer_status != Transfer::STATUS_INITIATED)
        {
            throw new Exception('Transfer status need to be "Initiated" to lock it!');
        }

        //check if is there any candidate in transfer who not working any more with this company

        $companyCandidates = $this->company->getCandidates()->select('candidate_id');

        $extraCandidates = $this->getTransferCandidates()
            ->andWhere([
                'not in',
                'candidate_id',
                $companyCandidates
            ])
            ->count();

        if($extraCandidates > 0)
        {
            throw new Exception('You got '.$extraCandidates.' candidate who not assign to you anymore. '
                . 'Please remove this transfer and create new one!');
        }

        $this->transfer_status = Transfer::STATUS_LOCK;

        //select distinct company and create transfer for each company
        $this->generateEachCompanyTransfer();

        if(isset(Yii::$app->components['user']['identityClass']) && Yii::$app->user->identity instanceof Staff) {
            $note = new Note;
            $note->note_type = Note::TYPE_INTERNAL_NOTE;
            $note->company_id = $this->company_id;
            $note->note_text = "I have locked a transfer for " . $this->company->company_common_name_en . " with a total of " . $this->company_total . " KD";
            $note->save();
        }

        return $this->save(false);
    }

    /**
     * @param $model
     * @return bool
     */
    public static function deleteTransfer($model) {

        //transfer status should be "Initiated" or "Locked" to delete it
        $allowedStatus = [
            Transfer::STATUS_CANCEL,
            Transfer::STATUS_INITIATED,
            Transfer::STATUS_LOCK
        ];

        if(!in_array($model->transfer_status, $allowedStatus))
        {
            return false;
        }

        $childrens = Transfer::find()->filterParent($model->transfer_id)->all();

        //delete data for each child

        foreach ($childrens as $child)
        {
            Invoice::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
            Transfer::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
            TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
        }

        //delete data for main transfer
        Invoice::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);
        Transfer::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);
        TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);

        if(isset(Yii::$app->components['user']['identityClass']) && Yii::$app->user->identity instanceof Staff) {
            $note = new Note;
            $note->note_type = Note::TYPE_INTERNAL_NOTE;
            $note->company_id = $model->company_id;
            $note->note_text = "I have deleted a transfer for " . $model->company->company_common_name_en . " with a total of " . $model->company_total . " KD";
            $note->save();
        }

        return true;
    }

    /**
     * @param $company
     * @param $candidates
     * @return array
     */
    public static function saveTransfer(
        $company,
        $candidates,
        $start_date,
        $end_date,
        $currency_code = "KWD",
        $contract_uuid = null,
        $noOfPayout = 1,
        $contract_type = null
    ) {

        if (sizeof($candidates) == 0) {
            return [
                "operation" => "error",
                "message" => "No candidates provided"
            ];
        }

        if(empty(Yii::$app->params['inCodeception']))
            $transaction = Yii::$app->db->beginTransaction();

        /*$contract = Contract::find()
            ->andWhere([
                "company_id" => $company->company_id,
                'contract_uuid' => $contract_uuid
            ])
            ->one();*/

        $transfer = new Transfer;
        $transfer->contract_uuid = $contract_uuid;
        $transfer->company_id = $company->company_id;
        $transfer->candidates = $candidates;
        $transfer->start_date = $start_date;
        $transfer->end_date   = $end_date;
        $transfer->contract_type = $contract_type;
        $transfer->currency_code = $currency_code;

        /*if ($contract) {
            $transfer->contract_type = $contract->type;
            $transfer->currency_code = $contract->currency_code;
        }*/

        if(!$transfer->save()) {
            if(isset($transfer->errors)) {
                return [
                    "operation" => "error",
                    "type" => "system",
                    "message" => $transfer->errors
                ];
            }

            return [
                "operation" => "error",
                "message" => "We've faced a problem creating the account, please contact us for assistance."
            ];
        }

        // Save candidate data

        $total = $company_total = $transfer_cost = 0;

        $response = 0;

        foreach ($candidates as $key => $value) {

            $value['currency_code'] = $transfer->currency_code;

            if(empty($value['bonus']) || $value['bonus'] < 0)
                $value['bonus'] = 0;

            if(empty($value['hours']) || $value['hours'] < 0)
                $value['hours'] = 0;

            if(empty($value['minutes']) || $value['minutes'] < 0)
                $value['minutes'] = 0;

            if(empty($value['seconds']) || $value['seconds'] < 0)
                $value['seconds'] = 0;

            /*if (
                (!$contract || $contract->type == Contract::TYPE_HOURLY) &&
                $value['bonus'] == 0 &&
                $value['hours'] == 0
            ) {
                continue;
            }*/

            if(empty($value['candidate_id']))
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate ID field required"
                ];
            }

            $candidate = Candidate::find()
                ->with(['store', 'company'])
                ->andWhere(['candidate_id' => $value['candidate_id']])
//                ->activeCivilId()
                ->asArray()
                ->one();

            if (!$candidate) {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found, please contact us for assistance"
                ];
            }

            if (!isset($candidate['store']) || !isset($candidate['company'])) {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();
                return [
                    "operation" => "error",
                    "message" => "Candidate (name : ".$value['candidate_name'].") (id : ".$value['candidate_id'].") not found, please contact us for assistance"
                ];
            }

            $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value, $noOfPayout, $contract_type);

            if ($response['operation'] == "error") {

                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return $response; // error will be respond back
            } else {
                $total += $response['total'];
                $company_total += $response['company_total'];
                $transfer_cost += $response['transfer_cost'];
            }
        }

        if($total <= 0) {

            if(empty(Yii::$app->params['inCodeception']))
                $transaction->rollBack();

            return [
                "operation" => "error",
                "response" => $response,
                "message" => "Transfer total is zero. Please input hours worked."
            ];
        }

        $transfer->transfer_cost = $transfer_cost;
        $transfer->company_total = $company_total;
        $transfer->total = $total;
        $transfer->save(false);

        //codeception does not support nested transaction
        if(empty(Yii::$app->params['inCodeception']))
            $transaction->commit();

        if(isset(Yii::$app->components['user']['identityClass']) && Yii::$app->user->identity instanceof Staff) {
            $note = new Note;
            $note->note_type = Note::TYPE_INTERNAL_NOTE;
            $note->company_id = $transfer->company_id;
            $note->note_text = "I have created a transfer for " . $transfer->company->company_common_name_en . " with a total of " . $transfer->company_total . " KD";
            $note->save();
        } else {
            $info = '';
            if (isset(Yii::$app->user->identity->staff_id)) {
                $info .= '[ Staff '.Yii::$app->user->identity->staff_name.' created a new transfer draft] ';
            } else if (isset(Yii::$app->user->identity->contact_uuid)) {
                $info .= '[ Agent '.Yii::$app->user->identity->contact_name.' created a new transfer draft] ';
            }
            $info .= '[ for '.$company->company_name.' ] ';
            $info .= ' Check if they require assistance on transfer #'.$transfer->transfer_id.'.';
            Yii::info($info, __METHOD__);
        }

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track('Transfer Created', [
                    'transfer_id' => $transfer->transfer_id,
                    'company_id' => $transfer->company_id,
                    'company_name' => $company->company_name,
                    'total' => $transfer->total
                ]);
        }

        return [
            "operation" => "success",
            "message" => "Transfer created.",
            "transfer_id" => $transfer->transfer_id,
            'execution_time' => Yii::getLogger()->getElapsedTime()
        ];
    }

    /**
     * update transfer method
     * @param $company
     * @param $id
     * @param $candidates
     * @return array
     */
    public function updateTransfer($candidates, $start_date, $end_date, $currency_code = "KWD",
                                   $contract_uuid = null, $contract_type = null) {

        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->currency_code = $currency_code;

        if ($this->contract) {
            $this->transfer_cost = $this->contract->transfer_cost;
            $this->contract_type = $this->contract->type;
            $this->currency_code = $this->contract->currency_code;
        }

        if ($contract_type) {
            $this->contract_type = $contract_type;
        }

        if($this->parent_transfer_id > 0) {
            return [
                "operation" => "error",
                "message" => 'Transfer for sub company can\'t be edited!'
            ];
        }

        //transfer status should be "Initiated" to edit it

        if($this->transfer_status != Transfer::STATUS_INITIATED)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status should be "Initiated" to edit it!'
            ];
        }

        $this->candidates = $candidates;

        $new_transfer_id = $new_invoice_id = [];

        //Old Child Transfers
        $old_child_transfers = $this->childTransfers;

        //Old Invoices
        $old_invoices = $this->invoices;

        if(empty(Yii::$app->params['inCodeception']))
            $transaction = Yii::$app->db->beginTransaction();

        //remove old candidates

        TransferCandidate::deleteAll(['transfer_id' => $this->transfer_id]);

        //save candidates

        $total = $company_total = $transfer_cost = 0;

        if (count($candidates) == 0) {

            if(empty(Yii::$app->params['inCodeception']))
                $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        foreach($candidates as $key => $value)
        {
            $value['currency_code'] = $currency_code;

            if(empty($value['bonus']) || $value['bonus'] < 0)
                $value['bonus'] = 0;

            if(empty($value['hours']) || $value['hours'] < 0)
                $value['hours'] = 0;

            if(empty($value['minutes']) || $value['minutes'] < 0)
                $value['minutes'] = 0;

            if(empty($value['seconds']) || $value['seconds'] < 0)
                $value['seconds'] = 0;

            /*if (
                (!$this->contract || $this->contract->type == Contract::TYPE_HOURLY) &&
                $value['bonus'] == 0 &&
                $value['hours'] == 0
            ) {
                continue;
            }*/

            if(empty($value['candidate_id']))
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate ID field required"
                ];
            }

            $candidate = Candidate::find()
                ->with(['store','company'])
                ->andWhere(['candidate_id' => $value['candidate_id']])
                ->asArray()
                ->one();

            if(!$candidate)
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found"
                ];
            }

            // save candidate transfer
            $response = TransferCandidate::saveCandidateTransfer($candidate, $this, $value, 1, $this->contract_type);

            if ($response['operation'] == "error") {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();
                return $response; // error will be respond back
            } else {
                $total += $response['total'];
                $company_total += $response['company_total'];
                $transfer_cost += $response['transfer_cost'];
            }
        }

        $this->transfer_cost = $transfer_cost;
        $this->company_total = $company_total;
        $this->total = $total;

        if($total <= 0)
        {
            if(empty(Yii::$app->params['inCodeception']))
                $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "transfer total can not be zero!"
            ];
        }

        if(!$this->save())
        {
            if(empty(Yii::$app->params['inCodeception']))
                $transaction->rollBack();

            return [
                "operation" => "error",
                "type" => "system",
                "message" => $this->getErrors()
            ];
        }

        $new_transfer_id[] = $this->transfer_id;

        //update child transfers

        //select distinct company and update transfer for each company if already added else create new

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($this->transfer_id)
            ->groupByCompany($this->company_id)
            ->all();

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for each sub companies
         */
        if(!$sub_companies && $this->transfer_status != Transfer::STATUS_INITIATED)
        {
            $new_invoice_id[] = $this->generateInvoice();
        }

        foreach ($sub_companies as $key => $sub_company) {

            //move transfer to transfer

            $transfer = Transfer::find()
                ->filterCompanyId($sub_company['company_id'])
                ->filterParent($this->transfer_id)
                ->one();

            if(empty($transfer)) {
                $transfer = new Transfer;
                $transfer->attributes = $this->attributes;
                $transfer->parent_transfer_id = $this->transfer_id;
                $transfer->company_id = $sub_company['company_id'];
                $transfer->contract_uuid = $this->contract_uuid;
                $transfer->contract_type = $this->contract_type;
            }

            if(!$transfer->save(false))
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $transfer->getErrors()
                ];
            }

            $total = $company_total = $transfer_cost = 0;

            // Remove old candidate id exists
            TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

            // Transfer candidate for current company
            $candidates = TransferCandidate::find()
                ->candidatesByTransfer($this->transfer_id)
                ->filterCompanyId($sub_company['company_id'])
                ->all();

            foreach ($candidates as $key => $value)
            {
                //(int) $value['minutes']>0 || (int)$value['seconds']>0 ||
                //                    (int)$value['hours']>0 || $value['bonus'] > 0

                if ($company_total > 0) {

                    /*$total += $value['bonus'] - $value['bonus_commission']
                        + ($value['hours'] * $value['candidate_hourly_rate'])
                        + ($value['minutes'] * ($value['candidate_hourly_rate'] / 60))
                        + ($value['seconds'] * ($value['candidate_hourly_rate'] / 3600));

                    $company_total += $value['bonus'] + ($value['hours'] * $value['company_hourly_rate'])
                        + ($value['minutes'] * ($value['company_hourly_rate'] / 60))
                        + ($value['seconds'] * ($value['company_hourly_rate'] / 3600))
                        + $value['transfer_cost'];*/

                    $company_total += $value['company_total'];
                    $total += $value['candidate_total'];
                    $transfer_cost += $value['transfer_cost'];
                }
            }

            // Save total in transfer
            $transfer->transfer_cost = $transfer_cost;
            $transfer->company_total = $company_total;
            $transfer->total = $total;

            if(!$transfer->save())
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    "operation" => "error",
                    "type" => "system",
                    "message" => $transfer->getErrors()
                ];
            }

            // Generate invoice for each transfer
            if($this->transfer_status != Transfer::STATUS_INITIATED) {
                $new_invoice_id[] = $transfer->generateInvoice();
            }

            $new_transfer_id[] = $transfer->transfer_id;
        }

        //remove extra transfers
        foreach ($old_child_transfers as $key => $value)
        {
            if(!in_array($value->transfer_id, $new_transfer_id))
            {
                //remove transfer data
                //Keep hard delete here as on recover of actual transfer we got required data
                TransferCandidate::deleteAll(['transfer_id' => $value->transfer_id]);
                Transfer::updateAll(['deleted' => 1], ['transfer_id' => $value->transfer_id]);
            }
        }

        //remove extra invoices
        foreach ($old_invoices as $key => $value)
        {
            if(!in_array($value->invoice_id, $new_invoice_id))
            {
                //remove invoice
                Invoice::updateAll(['deleted' => 1], ['invoice_id' => $value->invoice_id]);
            }
        }

        if(empty(Yii::$app->params['inCodeception']))
            $transaction->commit();

        if(isset(Yii::$app->components['user']['identityClass']) && Yii::$app->user->identity instanceof Staff) {
            $note = new Note;
            $note->note_type = Note::TYPE_INTERNAL_NOTE;
            $note->company_id = $this->company_id;
            $note->note_text = "I have updated a transfer for " . $this->company->company_common_name_en . " with a total of " . $this->company_total . " KD";
            $note->save();
        } else {
            $info = '';
            if (isset(Yii::$app->user->identity->staff_id)) {
                $info .= '[ Staff '.Yii::$app->user->identity->staff_name.' updated transfer #'.$this->transfer_id.'] ';
            } else if (isset(Yii::$app->user->identity->contact_uuid)) {
                $info .= '[ Agent '.Yii::$app->user->identity->contact_name.' updated transfer #'.$this->transfer_id.'] ';
            }
            $info .= '[ for '.$this->company->company_name.' ] ';
            $info .= ' Check if they require assistance ';
            Yii::info($info, __METHOD__);
        }

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track(
                'Transfer Updated', [
                    'transfer_id' => $this->transfer_id,
                    'company_id' => $this->company_id,
                    'company_name' => $this->company?$this->company->company_name: null,
                    'total' => $this->total
                ]);
        }

        return [
            "operation" => "success",
            "message" => "Your transfer has been updated."
        ];
    }

    /**
     * Static function to validate candidate array to initiate transfer
     * @param $attribute
     * @param $attribute
     * @param $validator
     * @return null
     */
    public function validateCandidates($attribute, $params, $validator)
    {
        $errors = [];
        $total = 0;

        $company_total = 0;

        if(!is_array($this->candidates)) {
            $this->candidates = [];
        }

        // check if empty field
        foreach ($this->candidates as $key => $value)
        {
            $bonus = (isset($value['bonus'])) ? $value['bonus'] : 0;
            $hours = (isset($value['hours'])) ? $value['hours'] : 0;
            $minutes = (isset($value['minutes'])) ? $value['minutes'] : 0;
            $seconds = (isset($value['seconds'])) ? $value['seconds'] : 0;

            if($seconds < 0)
            {
                $this->addError($attribute, 'Seconds can not be negative');
            }

            if($minutes < 0)
            {
                $this->addError($attribute, 'Minutes can not be negative');
            }

            if($hours < 0)
            {
                $this->addError($attribute, 'Hours can not be negative');
            }

            if($bonus < 0)
            {
                $this->addError($attribute, 'Bonus can not be negative');
            }

            if(empty($value['candidate_id']))
            {
                $this->addError($attribute, 'Candidate field require.');
            }

            //get company hourly rate

            $candidate = Candidate::find()
                ->andWhere(['candidate_id' => $value['candidate_id']])
                ->one();

            if(!$candidate) {
                $this->addError($attribute, 'Candidate #' . $value['candidate_id'] . ' not found.');
                return false;
            }

            if(!$candidate->company) {
                $this->addError($attribute, 'Candidate "' . $candidate->candidate_name . '" is not assigned to any employer.');
                return false;
            }

            $company = $candidate->company;

            //check if transfer company belong to candidate's company

            if(!in_array($this->company_id, [$company->parent_company_id, $company->company_id]))
            {
                $this->addError($attribute, 'Candidate "' . $candidate->candidate_name . '" is not your employee.');
            }

            if (!$this->contract_uuid || $this->contract_type == Contract::TYPE_HOURLY) {

                $company_hourly_rate = 0;
                $transfer_cost = 0;

                if ($this->contract) {
                    $company_hourly_rate = $this->contract->amount->company_hourly_rate;
                    $transfer_cost = $this->contract->amount->transfer_cost;
                } else {
                    $company_hourly_rate = $company['company_hourly_rate'];

                    //if value not set take from parent company

                    if ($company['company_hourly_rate'] == 0 && $company['company_bonus_commission'] == 0
                        && $company->parentCompany) {
                        $company_hourly_rate = $company->parentCompany['company_hourly_rate'];
                    }

                    $transfer_cost = (isset($value['transfer_cost'])) ? $value['transfer_cost'] : 0;
                }

                $company_minute_rate = $company_hourly_rate / 60;
                $company_second_rate = $company_minute_rate / 60;

                $company_total += $bonus + ($hours * $company_hourly_rate) + $transfer_cost + ($minutes * $company_minute_rate)
                    + ($seconds * $company_second_rate);
            } else {
                $company_total += $this->contract->amount->company_total;
            }
        }

        // Case where transfer total is zero/empty
        if ($company_total == 0) {
            $this->addError($attribute, "Transfer total is zero. Please input the actual hours worked.");
        }

        //commenting as some candidate might be paid in different transfer

        /*

        // Get list of all subcompanies belonging to this company.
        $companies = Company::findAll(['parent_company_id' => $this->company_id]);
        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');
        $company_ids[] = $this->company_id;

        // Use subcompany list to Get list of all stores belonging to the parent company
        $stores = Store::find()
           ->andWhere(['in', 'company_id', $company_ids])
           ->all();

       $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

       // Find all candidates that work in stores belonging to company but not included in candidate list
       // that is being validated. Show error if any missing
       $candidate_ids = ArrayHelper::map($this->candidates, 'candidate_id', 'candidate_id');

       $missing = Candidate::find()
           ->andWhere(['deleted' => 0])
           ->andWhere(['in', 'store_id', $store_ids])
           ->andWhere(['NOT IN', 'candidate_id', $candidate_ids])
           ->count();

       if($missing > 0)
       {
           $this->addError($attribute, 'Missing ' . $missing . ' candidate(s).');
       }*/
    }
}
