<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\db\Query;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use admin\models\Company;
use common\models\Invoice;
use common\models\Transfer;
use common\models\TransferCandidates;
use kartik\mpdf\Pdf;

/**
 * Transfer controller - Manage Transfer
 */
class TransferController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['POST'],
            'resourceOptions' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a Excel Containing Payable Candidates 
     */
    public function actionExportPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet 

        $candidates = TransferCandidates::find()->payable()->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'tc_id',
                'transfer_id',
                'candidate_id',
                'candidate.candidate_name',
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'hours',
                'candidate_hourly_rate',
                'bonus',
                'transfer_cost',                
                [
                    'attribute'=>'candidate_total',
                    'value' => function($data){
                        return $data->candidateTotal;
                    }
                ],
                'candidate.candidate_iban', 
                'candidate.bank.bank_name'
            ]
        ]);
    }

    /**
     * Return a List Payable Candidates 
     */
    public function actionPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet 

        $query = TransferCandidates::find()->payable()->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Transfer.
     */
    public function actionList()
    {
        $company_name = Yii::$app->request->get('company_name'); 
        $transfer_status = Yii::$app->request->get('transfer_status'); 

        $query = Transfer::find()
            ->selectedFields()
            ->companyJoin()
            ->parentTransfers();

        if($company_name)
            $query->filterCompany($company_name);

        if($transfer_status)
            $query->filterStatus($transfer_status);

        return new ActiveDataProvider([
            'query' => $query->asArray()
        ]);
    }

    /**
     * Download Transfer as PDF 
     */
    public function actionPdf($id)
    {
        $transfer = Invoice::find()
            ->withTransfer($id)
            ->asArray()
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        $transfer['company'] = Company::findOne($transfer['company_id']);

        $transfer['candidates'] = TransferCandidates::find()
            ->candidatesByTransfer($transfer['transfer_id'])
            ->asArray()
            ->all();

        $this->layout = 'pdf';

        if($transfer['invoice_status'] == 'paid') 
            $template = 'receipt';
        else
            $template = 'invoice';

        $content = $this->render($template, [
            'transfer' => $transfer,
        ]);

        $pdf = new Pdf([
            // A4 paper format
            'format' => Pdf::FORMAT_A4, 
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT, 
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER, 
            // your html content input
            'content' => $content,  
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:38px}', 
             // set mPDF properties on the fly
            'options' => [],//['title' => 'Booking #'.$id],
        ]);    

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }

    /**
     * Return Transfer detail.
     */
    public function actionView($id)
    {
        $transfer = Transfer::find()
            ->where([
                'transfer_id' => $id
            ])
            ->asArray()
            ->one();
            
        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
                ];
        }

        $transfer['total_paid'] = TransferCandidates::find()
            ->totalPaid($id);
        
        $transfer['total_unpaid'] = TransferCandidates::find()
            ->totalUnpaid($id);

        //get total profit

        $transfer['profit'] = TransferCandidates::find()
            ->profit($id);
            
        $transfer['candidates'] = TransferCandidates::find()
            ->candidatesByTransfer($transfer['transfer_id'])
            ->asArray()
            ->all();

        //invoices 

        $transfer['invoices'] = Invoice::find()
            ->byTransfer($id)
            ->all();

        return $transfer;
    }

    /**
     * Export Transfer detail.
     */
    public function actionExport($id)
    {
        $transfer = Transfer::find()
            ->where([
                'transfer_id' => $id
            ])
            ->one();
            
        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        $candidates = TransferCandidates::find()
            ->candidatesByTransfer($id)
            ->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                'candidate.candidate_name',
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'hours',
                'candidate_hourly_rate',
                'bonus',
                'transfer_cost',
                [
                    'attribute'=>'candidate_total',
                    'value'=>function($data) {
                        return $data->candidateTotal;
                    }
                ],
                'candidate.candidate_iban', 
                'candidate.bank.bank_name',
                [
                    'attribute' => 'paid',
                    'value' => function($model) {
                        if($model->paid)
                            return 'Yes';
                        else
                            return 'No';
                    },
                ],
            ]
        ]);
    }

    /** 
     * Mark Transfer as Payment Received
     */ 
    public function actionPaymentReceived($id)
    {
        $transfer = Transfer::findOne([
                'transfer_id' => $id
            ]);

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        //set payment received date 

        $transfer->payment_received_on = date('Y-m-d');    

        // remove status received and set to in progress to combine both.
        $transfer->transfer_status = Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;

        $transfer->save();

        // mark invoice as paid for all child transfer and main transfer in case of no child company 

        Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $transfer->transfer_id]);

        $child_transfers = Transfer::findAll(['parent_transfer_id' => $transfer->transfer_id]);

        foreach ($child_transfers as $key => $value) {
            Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $value->transfer_id]);
        }

        // sending mail to company as receipt

        $this->receiptMail($transfer->transfer_id); 

        return [
            "operation" => "success",
            "message" => 'Transfer marked as "Payment Received" successfully'
        ];
    }

    /** 
     * Mark Transfer as Initiated
     */ 
    public function actionUnlock($id)
    {
        $transfer = Transfer::findOne([
                'transfer_id' => $id
            ]);
            
        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        // to unlock transfer, transfer status should be in lock status 

        if($transfer->transfer_status != Transfer::STATUS_LOCK)
        {
            return [
                    "operation" => "error",
                    "message" => 'Transfer status should be "Locked" to unlock it!'
                ];
        }

        $transfer->transfer_status = Transfer::STATUS_INITIATED;
        $transfer->save();

        return [
                "operation" => "success",
                "message" => 'Transfer unlocked successfully'
            ];
    }

    /** 
     * Mark Transfer as Payment In Process
     */ 
    public function actionPaymentInProcess($id)
    {
        $transfer = Transfer::findOne([
                'transfer_id' => $id
            ]);
            
        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
                ];
        }

        $transfer->transfer_status = Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;
        $transfer->save();

        return [
                "operation" => "success",
                "message" => 'Transfer marked as "Salary Distribution in Progress" successfully'
            ];
    }

    /** 
     * Mark Transfer as Payment In Completed
     */ 
    public function actionPaymentCompleted($id)
    {
        $transfer = Transfer::findOne([
                'transfer_id' => $id
            ]);
            
        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
                ];
        }

        $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
        $transfer->save();

        //get all child transfers

        $transfers = Transfer::findAll(['parent_transfer_id' => $id]);

        $transfer_ids = ArrayHelper::map($transfers, 'transfer_id', 'transfer_id');

        $transfer_ids[] = $id;

        //mark candidates as paid 

        TransferCandidates::updateAll(['paid' => 1], 'transfer_id IN ('.implode(',', $transfer_ids).')');

        return [
            "operation" => "success",
            "message" => 'Transfer marked as "Payment Complete" successfully'
        ];
    }

    /** 
     * Return unpaid candidates for given transfer 
     */ 
    public function actionUnpaidCandidates($id)
    {
        $candidates = TransferCandidates::find()
            ->unpaid($id)
            ->asArray()
            ->all();

        return [
            'candidates' => $candidates
        ];
    }

    public function actionMarkPaid($id)
    {
        $transfer = Transfer::findOne($id);

        if(!$transfer)
        {
            return [
                'operation' => 'error',
                'message' => 'Transfer not found!'
            ];
        }

        //get all child transfers

        $transfers = Transfer::findAll(['parent_transfer_id' => $id]);

        $transfer_ids = ArrayHelper::map($transfers, 'transfer_id', 'transfer_id');

        $transfer_ids[] = $id;

        //mark as paid 

        $candidate_ids = Yii::$app->request->getBodyParam('candidates');

        foreach ($candidate_ids as $key => $value) 
        {
            TransferCandidates::updateAll(['paid' => 1], 'candidate_id = "'.$value.'" AND transfer_id IN ('.implode(',', $transfer_ids).')');
        }

        //check if all paid, mark transfer as complete 

        $unpaid = TransferCandidates::find()
            ->where([
                'paid' => 0
            ])
            ->andWhere(['in', 'transfer_id', $transfer_ids])
            ->count();

        if(!$unpaid)
        {
            $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
            $transfer->save();
        }

        return [
            'operation' => 'success',
            'message' => 'Candidate(s) marked as paid successfully'
        ];
    }

    private function receiptMail($transfer_id)
    {
        $company = \common\models\Company::findOne($company_id);
        
        $transfer = Transfer::find()
            ->where(['transfer_id' => $transfer_id])
            ->one();

        $invoice_id = $transfer->invoice->invoice_id;

        $invoice = Invoice::findOne($invoice_id);

        if(!$invoice) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }

        $candidates = TransferCandidates::find()
            ->candidatesByTransfer($transfer_id)
            ->asArray()
            ->all();

        $template = $invoice->invoice_status == 'paid'?'receipt':'invoice';
        
        $this->layout = 'pdf';

        $content = $this->render($template, [
            'transfer' => $transfer,
            'invoice' => $invoice,
            'candidates' => $candidates
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

        $pdfAttachment = $pdf->output($content, $template.'.pdf', 'S');

        $to = $transfer->company->company_email;
        
        if($transfer['invoice_status'] == 'paid') {
            $template = 'receipt';
            $subject = 'StudentHub Receipt for Invoice #'.$invoice_id;
        } else {
            $template = 'invoice';
            $subject = 'StudentHub Invoice #'.$invoice_id;
        }
        
        $message = Yii::$app->mailer->compose($template.'-attachment',[
            'transfer' => $transfer,
            'invoice' => $invoice
        ]);

        $message->setFrom([Yii::$app->params['invoiceFrom'] => 'Khalid Al-Mutawa']);

        $message->attachContent($pdfAttachment,['fileName' => 'Receipt-for-Invoice-#'.$invoice_id.'.pdf', 'contentType' => 'application/pdf']);
        
        return $message->setTo($to)
            ->setCc('finance@bawes.net')
            ->setSubject($subject)
            ->send();
    }

    public function actionText() {

        $s1 = 'S1,11622216,,MXD,M,,'.date('d/m/Y').','.date('dmY').'-01'.PHP_EOL; // header line

        $s2 = '';
        $totalUserHours = 0;
        $totalUserBonus = 0;
        $totalUserAmount = 0;
        $totalTransaction = 0;
        $totalAmount = 0;
        $finalAmount = 0;

        $invoices = Invoice::find()
            ->unpaid()
            ->all();

        if(!$invoices) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        foreach ($invoices as $invoice) 
        {
            $candidates = TransferCandidates::find()
                ->candidatesByTransfer($invoice->transfer_id)
                ->asArray()
                ->all();

            foreach ($candidates as $detail) {
                $totalUserHours += $detail['hours'];
                $totalUserBonus += $detail['bonus'];
                $totalUserAmount += ($detail['hours'] * $detail['company_hourly_rate']);
                $totalAmount += $totalUserAmount;
                $finalUserAmount = number_format($totalUserAmount,3,'.',',');
                $description = 'Internship '.$detail['hours'].' Hours';
                $s2 .= "S2,".$detail['bank_transfer_type'].",".$finalUserAmount.",KWD,,,,11622216,".$detail['candidate_iban'].",".$invoice->transfer_id.",".$invoice->invoice_id.",".$description.",,,,".$detail['bank_account_name'].",".$detail['bank_name'].",,".$detail['bank_name'].",".$detail['bank_address'].",,,".$detail['bank_swift_code'].",,,,,,,B,,,".$detail['candidate_iban'].",".PHP_EOL;
                $totalTransaction +=1;
            }
        }
        $finalAmount = number_format($totalAmount,3,'.',',');
        $s3 = 'S3,'.$totalTransaction.','.$finalAmount; // Footer
        $sAll = $s1.$s2.$s3;

        $fileName = 'BAWS-PAY-'.date('dmY').'-01.txt';

        $handle = fopen($fileName, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        header('Access-Control-Allow-Origin: *');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($fileName));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));
        readfile($fileName);
        exit;
    }


    public function actionLock($id)
    {
        $model = Transfer::findOne($id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        if($model->transfer_status != Transfer::STATUS_PAYMENT_SENT)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status need to be "Payment Sent" to lock it!'
            ];
        }

        $model->transfer_status = Transfer::STATUS_LOCK;
        if ($model->save()) {
            return [
                "operation" => "success",
                "message" => "Transfer status changed to locked successfully"
            ];
        }
    }

}
