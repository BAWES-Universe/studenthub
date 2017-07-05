<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use company\models\Company;
use company\models\Candidate;
use company\models\Transfer;
use common\models\Invoice;
use company\models\TransferCandidate;
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
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
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
            'resourceOptions' => ['POST', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Transfer.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $company = Company::findOne(Yii::$app->user->id);
        $query = $company->getTransfers()
            ->where('parent_transfer_id IS NULL')
            ->orderBy('transfer_id DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array
     */
    public function actionView($id)
    {
        $company = Company::findOne(Yii::$app->user->id);

        $transfer = Transfer::find()
            ->joinWith('company')
            ->filterCurrentCompany($company)
            ->filterTransfer($id)
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        return $transfer;
    }

    /**
     * Initiate transfer.
     * @return array
     */
    public function actionCreate()
    {
        $company = Yii::$app->user->identity;
        $candidates = Yii::$app->request->getBodyParam("candidates");
        // Validate input
        $errors = Transfer::validateCandidates(
            $company->company_id,
            $candidates
        );

        if($errors) {
            return [
                "operation" => "error",
                "message" => $errors
            ];
        }

        //save transfer
        return Transfer::saveTransfer($company, $candidates);

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Edit transfer with "Initiated" status
     * @param $id
     * @return array
     */
    public function actionEdit($id)
    {
        $company = Company::findOne(Yii::$app->user->id);

        // list all sub companies

        $model = Transfer::find()
            ->filterTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

        if(!$model) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        if($model->parent_transfer_id > 0) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer for sub company can\'t be edited!'
                ];
        }

        //transfer status should be "Initiated" to edit it

        if($model->transfer_status != Transfer::STATUS_INITIATED)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status should be "Initiated" to edit it!'
            ];
        }

        //validate input

        $errors = Transfer::validateCandidates(
            $company->company_id,
            Yii::$app->request->getBodyParam("candidates")
        );

        if($errors) {
            return [
                "operation" => "error",
                "message" => $errors
            ];
        }

        $candidates = Yii::$app->request->getBodyParam("candidates");
        return Transfer::updateTransfer($company,$id,$candidates);

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Mark Transfer as Payment Sent
     * @param $id
     * @return array
     */
    public function actionPaymentSent($id)
    {
        $company = Company::findOne(Yii::$app->user->id);
        // list all sub companies

        $transfer = Transfer::find()
            ->filterTransfer($id)
            ->filterCompanyId($company->company_id)
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
                ];
        }

        if($transfer->transfer_status == Transfer::STATUS_PAYMENT_SENT)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer already marked as "Payment Sent"'
            ];
        }

        if($transfer->transfer_status != Transfer::STATUS_LOCK)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status should be "Locked" to send it!'
            ];
        }

        $transfer->transfer_status = Transfer::STATUS_PAYMENT_SENT;
        $transfer->save();

        return [
            "operation" => "success",
            "message" => 'Transfer has been marked as "Payment Sent"'
        ];
    }

    /**
     * Lock transfer
     * @param $id
     * @return array
     */
    public function actionLock($id)
    {
        $company = Company::findOne(Yii::$app->user->id);

        $model = Transfer::find()
            ->filterTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

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
                "message" => 'Transfer already locked'
            ];
        }

        if($model->transfer_status != Transfer::STATUS_INITIATED)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status need to be "Initiated" to lock it!'
            ];
        }

        $model->transfer_status = Transfer::STATUS_LOCK;
        $model->save();


        //select distinct company and create transfer for each company
        Transfer::generateEachCompanyTransfer($model, $company);

        $this->invoiceMail($id); // send invoice mail

        return [
            "operation" => "success",
            "message" => "Transfer has been locked. Invoices will be sent to your email."
        ];
    }

    /**
     * Delete transfer with "Initiated" or "Locked" status
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        $company = Company::findOne(Yii::$app->user->id);
        $model = Transfer::find()
            ->filterTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

        if(!$model) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        //transfer status should be "Initiated" or "Locked" to delete it

        $allowedStatus = [
            Transfer::STATUS_INITIATED,
            Transfer::STATUS_LOCK
        ];

        if(!in_array($model->transfer_status, $allowedStatus))
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status should be "Initiated" or "Locked" to delete it!'
            ];
        }

        //delete data child transfer
        Transfer::deleteChildTransfer($model);

        return [
            "operation" => "success",
            "message" => 'Transfer deleted as requested.'
        ];
    }

    /**
     * Download Transfer as PDF
     * @param $id
     * @return array|mixed
     */
    public function actionPdf($id)
    {
        $company = Company::findOne(Yii::$app->user->id);

        $invoice = Invoice::find()
            ->withTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

        if(!$invoice) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
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
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
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
//            'methods' => [
//                'SetHeader'=>['Transfer #'.$transfer['transfer_id']],
//                'SetFooter'=>['{PAGENO}'],
//            ]
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }

    

    /**
     * send invoice mail to recipient and cc to company email
     * @param $id
     * @return array|bool
     */
    public function invoiceMail($id)
    {
        $invoices = Invoice::find()
            ->byTransfer($id)
            ->all();

        if(!$invoices) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }
        $this->layout = 'pdf';
        $subject = [];
        $template = 'invoice';
        $message = Yii::$app->mailer->compose('invoice-attachment',['invoices'=>$invoices]);
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
            $invoice_id = 0;
        }

        $subjectLine = Yii::t('app','StudentHub {numReceipts, plural, =1{invoice} other{Invoices}} {invoicesList} ', ['numReceipts' => count($invoices),'invoicesList'=>implode(', ',$subject)]);

        return $message->setTo($email)
            ->setCc(Yii::$app->params['invoiceCC'])
            ->setSubject($subjectLine)
            ->send();
    }
}
