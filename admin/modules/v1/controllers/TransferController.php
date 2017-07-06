<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use admin\models\Company;
use admin\models\Invoice;
use admin\models\Transfer;
use admin\models\TransferCandidate;
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
                    'filename',
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
        $behaviors['authenticator']['except'] = ['options','text'];

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
     * Return a List of Transfer.
     */
    public function actionList()
    {
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');

        $query = Transfer::find()
            ->notDeleted()
            ->isParentTransfer();

        if($company_name)
        {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        $query->groupBy('{{%transfer}}.transfer_id');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $transfer = Transfer::find()
            ->where([
                'transfer_id' => $id
            ])
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
                ];
        }

        return $transfer;
    }

    /**
     * Mark Transfer as Payment Received
     * @param $id
     * @return array
     */
    public function actionPaymentReceived($id)
    {
        $transfer = Transfer::findOne($id);

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        if($transfer->transfer_status != Transfer::STATUS_PAYMENT_SENT)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status need to be "Payment Sent" to mark as "Payment Received"',
            ];
        }

        if($transfer->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer already marked as "Payment Received"',
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
            "message" => 'Transfer marked as "Payment Received" successfully',
            'totalPayableCandidate'=> Candidate::getTotalPayableCandidate()
        ];
    }

    /**
     * Return Transfer by mark as Initiated from Lock
     * @param $id
     * @return array
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

        if($transfer->transfer_status == Transfer::STATUS_INITIATED)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer already unlocked!'
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
     * Return Transfer by mark as Lock from Payment Sent
     * @param $id
     * @return array
     */
    public function actionLock($id)
    {
        $model = Transfer::findOne($id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        if($model->transfer_status == Transfer::STATUS_LOCK)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer already locked!'
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

    /**
     * Mark Transfer as Payment In Completed
     * @param $id
     * @return array
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

        if($transfer->transfer_status != Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status need to be "Received & Distributing Salary" to mark as "Payment Complete"'
            ];
        }

        if($transfer->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer already marked as "Payment Complete"'
            ];
        }

        $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
        $transfer->save();

        //get all child transfers

        $transfers = Transfer::findAll(['parent_transfer_id' => $id]);

        $transfer_ids = ArrayHelper::map($transfers, 'transfer_id', 'transfer_id');

        $transfer_ids[] = $id;

        //mark candidates as paid

        TransferCandidate::updateAll(['paid' => 1], 'transfer_id IN ('.implode(',', $transfer_ids).')');

        return [
            "operation" => "success",
            "message" => 'Transfer marked as "Payment Complete" successfully'
        ];
    }

    /**
     * Return unpaid candidates for given transfer
     * @param $id
     * @return array
     */
    public function actionUnpaidCandidates($id)
    {
        $candidates = TransferCandidate::find()
            ->unpaid($id)
            ->all();

        return [
            'candidates' => $candidates
        ];
    }

    /**
     * Method to mark payment as paid
     * @param $id
     * @return array
     */
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
            TransferCandidate::updateAll(['paid' => 1], 'candidate_id = "'.$value.'" AND transfer_id IN ('.implode(',', $transfer_ids).')');
        }

        //check if all paid, mark transfer as complete

        $unpaid = TransferCandidate::find()
            ->andwhere([
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

    /**
     * Method linked with payable candidate
     * section option to mark all candidate at one time
     */
    public function actionMarkPaidAll()
    {
        $candidate_ids = Yii::$app->request->getBodyParam('candidates');
        $main_transfer_id = 0;

        foreach ($candidate_ids as $list) {

            // find transfer
            $transfer = Transfer::findOne($list['transfer_id']);

            //get all child transfers

            $transfers = Transfer::findAll(['parent_transfer_id' => $list['transfer_id']]);

            $transfer_ids = ArrayHelper::map($transfers, 'transfer_id', 'transfer_id');

            $transfer_ids[] = $list['transfer_id'];

            TransferCandidate::updateAll(['paid' => 1], 'candidate_id = "'.$list['candidate_id'].'" AND transfer_id IN ('.implode(',', $transfer_ids).')');
            //check if all paid, mark transfer as complete

            $unpaid = TransferCandidate::find()
                ->where([
                    'paid' => 0
                ])
                ->andWhere(['in', 'transfer_id', $transfer_ids])
                ->count();

            if (!$unpaid) {
                $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
                $transfer->save();
            }
        }
            return [
                'operation' => 'success',
                'message' => count($candidate_ids). ' Candidate(s) marked as paid successfully',
                'totalPayableCandidate'=> Candidate::getTotalPayableCandidate()
            ];
    }

    /**
     * Return a Excel Containing Payable Candidates
     */
    public function actionExportPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet

        $candidates = TransferCandidate::find()
            ->payable()
            ->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'tc_id',
                'transfer_id',
                'candidate_id',
                'candidate.candidate_name',
                [
                    'attribute'=>'Beneficiary name',
                    'label'=>'Beneficiary name',
                    'value'=>function($data) {
                        return $data->candidate->bank_account_name;
                    }
                ],
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
                        return $data->totalPaidToCandidate;
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
        $result = [];

        $transfers = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer)
        {
            if($transfer->transferCandidates) 
            {
                $result[] = [
                    'transfer_id' => $transfer->transfer_id,
                    'candidates' => $transfer->transferCandidates,
                    'total' => $transfer->total
                ];
            }
            else
            { // remove transfer if no candidate available
                unset($result[$transfer->transfer_id]);
            }
        }

        return new ArrayDataProvider([
            'allModels' => $result
        ]);
    }

    /**
     * Return a List of all Payable Candidates with invoice status paid
     */
    public function actionAllPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet
        $result = [];

        $transfers = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer)
        {
            $candidates = $transfer->getTransferCandidates()
                ->where(['paid' => '0'])
                ->all();

            if($candidates)
            {
                $result[] = [
                    'transfer_id' => $transfer->transfer_id,
                    'candidates' => $candidates
                ];
            }
        }

        return $result;
    }

    /**
     * method to generate text file for all unpaid candidates
     * @return array
     */
    public function actionText() {

        $s1 = 'S1,11622216,,MXD,M,,'.date('d/m/Y').','.date('dmY').'-01'.PHP_EOL; // header line

        $s2 = '';
        $totalTransaction = 0;
        $totalAmount = 0;

        $candidates = TransferCandidate::find()
            ->payable()
            ->all();

        if(!$candidates) {
            return [
                "operation" => "error",
                "message" => 'No Payable Candidates!'
            ];
        }

        foreach ($candidates as $detail) {

            $totalAmount += $detail->totalPaidToCandidate;
            $description = 'Internship '.$detail->hours.' Hours';

            if(empty($detail->candidate->bank)) {
                continue;
            }

            $s2 .=  "S2,".$detail->candidate->bank->bank_transfer_type.",".$detail->totalPaidToCandidate.",KWD,,,,11622216,".
                    $detail->candidate->candidate_iban.",".
                    $detail->transfer_id.",".
                    $detail->invoice->invoice_id.",".
                    $description.",,,,".
                    $detail->candidate->bank_account_name.",".
                    $detail->candidate->bank->bank_name.",,".
                    $detail->candidate->bank->bank_name.",".
                    $detail->candidate->bank->bank_address.",,,".
                    $detail->candidate->bank->bank_swift_code.",,,,,,,B,,,".
                    $detail->candidate->candidate_iban.",".PHP_EOL;
            $totalTransaction +=1;
        }

        $finalAmount = number_format($totalAmount,3,'.',',');
        $s3 = 'S3,'.$totalTransaction.','.$finalAmount; // Footer
        $sAll = $s1.$s2.$s3;

        $fileName = 'BAWS-PAY-'.date('dmY').'-01.txt';

        $path = sys_get_temp_dir() .DIRECTORY_SEPARATOR. $fileName;

        $handle = fopen($path, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        Yii::$app->response->headers->add('filename', $fileName);

        return Yii::$app->response->sendFile($path);
    }

    /**
     * Export Transfer detail as Excel
     * @param $id
     * @return array
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

        $candidates = TransferCandidate::find()
            ->candidatesByTransfer($id)
            ->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                'candidate.candidate_name',
                [
                    'label'=>'Beneficiary name',
                    'value'=>function($data) {
                        return $data->candidate->bank_account_name;
                    }
                ],
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
                        return $data->totalPaidToCandidate;
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
     * Download Transfer as PDF
     * @param $id
     * @return array|mixed
     */
    public function actionPdf($id)
    {
        $invoice = Invoice::find()
            ->withTransfer($id)
            ->one();

        if(!$invoice) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        $this->layout = 'pdf';

        if($invoice['invoice_status'] == 'paid')
            $template = 'receipt';
        else
            $template = 'invoice';

        $content = $this->render($template, [
            'invoice' => $invoice,
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
     * Receipt Mail by transfer id to recipient
     * and also forward to finance@bawes.net
     * @param $transfer_id
     * @return array|bool
     */
    private function receiptMail($transfer_id)
    {
        $invoices = Invoice::find()
            ->byTransfer($transfer_id)
            ->all();

        if(!$invoices) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }

        $this->layout = 'pdf';
        $subject = [];
        $template = 'receipt';
        $message = Yii::$app->mailer->compose('receipt-attachment',['invoices'=>$invoices]);
        $message->setFrom([Yii::$app->params['invoiceFrom'] => 'Khalid Al-Mutawa']);
        $i=1;
        $invoice_id = 0;
        foreach ($invoices as $invoice) {
            $invoice_id = $invoice->invoice_id;
            $content = $this->render($template, [
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
            $email = (isset($invoice->transfer->company->parentCompany->company_email)) ? $invoice->transfer->company->parentCompany->company_email :  $invoice->transfer->company->company_email;
            $message->attachContent($pdfAttachment,['fileName' => $template.'-#'.$invoice_id.'.pdf', 'contentType' => 'application/pdf']);
            $i++;
            $subject[] = '#'.$invoice_id;
            $invoice_id = 0; // reinitialize to 0 to store new with new loop
        }
        $subjectLine = Yii::t('app','StudentHub {numReceipts, plural, =1{receipt} other{receipts}} {invoicesList} ', ['numReceipts' => count($invoices),'invoicesList'=>implode(', ',$subject)]);

        return $message->setTo($email)
            ->setCc(Yii::$app->params['invoiceCC'])
            ->setSubject($subjectLine)
            ->send();
    }
}
