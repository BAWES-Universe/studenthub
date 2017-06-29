<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use company\models\Company;
use company\models\Candidate;
use company\models\Transfer;
use common\models\Invoice;
use common\models\TransferCandidate;
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

        //save transfer

        $transaction = Yii::$app->db->beginTransaction();

        $transfer = new Transfer;
        $transfer->company_id = $company->company_id;

        if(!$transfer->save()){

            if(isset($transfer->errors)){
                return [
                    "operation" => "error",
                    "message" => $transfer->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        //save candidates

        $candidates = Yii::$app->request->getBodyParam("candidates");

        $total = $company_total = 0;

        foreach ($candidates as $key => $value) {

            if(empty($value['bonus']))
                $value['bonus'] = 0;

            if(empty($value['hours']))
                $value['hours'] = 0;

            //candiate hourly_rate

            $candidate = Candidate::findOne($value['candidate_id']);

            if(!$candidate)
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found"
                ];
            }

            $hourly_rate = $candidate->candidate_hourly_rate;

            $tc = new TransferCandidate;
            $tc->transfer_cost = Yii::$app->params['transfer_cost'];
            $tc->candidate_hourly_rate = $hourly_rate;
            $tc->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
            $tc->attributes = $value;
            $tc->store_id = $candidate->store_id;
            $tc->store_name = $candidate->store->store_name;
            $tc->company_id = $candidate->store->company_id;
            $tc->company_name = $candidate->store->company->company_name;
            $tc->company_email = $candidate->store->company->company_email;

            $tc->transfer_id = $transfer->transfer_id;

            $total += $value['bonus'] + ($value['hours'] * $hourly_rate) + Yii::$app->params['transfer_cost'];

            $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);

            if(!$tc->save())
            {
                $transaction->rollBack();

                if(isset($tc->errors)){
                    return [
                        "operation" => "error",
                        "message" => $tc->errors
                    ];
                }else{
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem creating the account, please contact us for assistance."
                    ];
                }
            }
        }

        if($total <= 0)
        {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "transfer total can not be zero!"
            ];
        }

        $transfer->company_total = $company_total;
        $transfer->total = $total;
        $transfer->save();

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Transfer initiated successfully"
        ];

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

        $new_transfer_id = $new_invoice_id = [];

        //Old Child Transfers
        $old_child_transfers = Transfer::findAll(['parent_transfer_id' => $model->transfer_id]);

        //Old Invoices
        $old_invoices = Invoice::find()
            ->byTransfer($model->transfer_id)
            ->all();

        $transaction = Yii::$app->db->beginTransaction();

        //remove old candidates

        TransferCandidate::deleteAll(['transfer_id' => $model->transfer_id]);

        //save candidates

        $candidates = Yii::$app->request->getBodyParam("candidates");

        $total = $company_total = 0;

        foreach ($candidates as $key => $value) {

            if(empty($value['bonus']))
                $value['bonus'] = 0;

            if(empty($value['hours']))
                $value['hours'] = 0;

            //candiate hourly_rate

            $candidate = Candidate::findOne($value['candidate_id']);

            if(!$candidate)
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found"
                ];
            }

            $hourly_rate = $candidate->candidate_hourly_rate;

            $tc = new TransferCandidate;
            $tc->transfer_cost = Yii::$app->params['transfer_cost'];
            $tc->candidate_hourly_rate = $hourly_rate;
            $tc->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
            $tc->attributes = $value;
            $tc->transfer_id = $model->transfer_id;
            $tc->store_id = $candidate->store_id;
            $tc->store_name = $candidate->store->store_name;
            $tc->company_id = $candidate->store->company_id;
            $tc->company_name = $candidate->store->company->company_name;
            $tc->company_email = $candidate->store->company->company_email;

            $total += $value['bonus'] + ($value['hours'] * $hourly_rate) + Yii::$app->params['transfer_cost'];

            $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);

            if(!$tc->save())
            {
                $transaction->rollBack();

                if(isset($tc->errors)){
                    return [
                        "operation" => "error",
                        "message" => $tc->errors
                    ];
                }else{
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem creating the account, please contact us for assistance."
                    ];
                }
            }
        }

        $model->company_total = $company_total;
        $model->total = $total;

        if($total <= 0)
        {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "transfer total can not be zero!"
            ];
        }

        $model->save();

        $new_transfer_id[] = $model->transfer_id;

        //update child transfers

        //select distinct company and update transfer for each company if already added else create new

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($model->transfer_id)
            ->groupByCompany($model->company_id)
            ->asArray()
            ->all();

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for each sub companies
         */
        if(!$sub_companies)
        {
            $invoice = Invoice::findOne(['transfer_id' => $model->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $model->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();
            }

            $new_invoice_id[] = $invoice->invoice_id;
        }

        foreach ($sub_companies as $key => $sub_company) {

            //move transfer to transfer
            $transfer = Transfer::find()
                ->filterCompanyId($sub_company['company_id'])
                ->filterParent($model->transfer_id)
                ->one();

            if(empty($transfer)) {
                $transfer = new Transfer;
                $transfer->attributes = $model->attributes;
                $transfer->parent_transfer_id = $model->transfer_id;
                $transfer->company_id = $sub_company['company_id'];
            }

            if(!$transfer->save(false))
            {
                $transaction->rollBack();

                return [
                    "operation" => "success",
                    "message" => $transfer->getErrors()
                ];
            }

            $total = $company_total = 0;

            //remove old candidate id exists

            TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

            // transfer candidate for current company

            $candidates = TransferCandidate::find()
                ->candidatesByTransfer($model->transfer_id)
                ->filterCompanyId($sub_company['company_id'])
                ->all();

            foreach ($candidates as $key => $value)
            {
                $total += $value['bonus'] + ($value['hours'] * $value['candidate_hourly_rate']) + Yii::$app->params['transfer_cost'];

                $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
            }

            //save total in transfer

            $transfer->company_total = $company_total;
            $transfer->total = $total;
            if(!$transfer->save())
            {
                $transaction->rollBack();

                return [
                    "operation" => "success",
                    "message" => $transfer->getErrors()
                ];
            }

            //generate invoice for each transfer
            $invoice = Invoice::findOne(['transfer_id' => $transfer->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $transfer->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();
            }

            $new_transfer_id[] = $transfer->transfer_id;
            $new_invoice_id[] = $invoice->invoice_id;
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

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Transfer updated successfully"
        ];

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
                "message" => 'Transfer marked as "Payment Sent" successfully'
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

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($model->transfer_id)
            ->groupByCompany($model->company_id)
            ->all();

        // condition to check if current company has existing sub companies.
        $sub_companies = ($sub_companies && (isset($company->subCompanies)) && count($company->subCompanies)>0) ? $sub_companies : false;

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for sub companies
         */
        if(!$sub_companies)
        {
            $invoice = Invoice::findOne(['transfer_id' => $model->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $model->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();
            }
        }

        /**
         * if transfer initiated by parent company split it for each
         * sub companies
         */
        if ($sub_companies) {
            foreach ($sub_companies as $key => $sub_company) {

                //move transfer to transfer
                $transfer = Transfer::findOne([
                    'parent_transfer_id' => $model->transfer_id,
                    'company_id' => $sub_company['company_id']
                ]);

                if (!$transfer) {
                    $transfer = new Transfer;
                    $transfer->attributes = $model->attributes;
                    $transfer->parent_transfer_id = $model->transfer_id;
                    $transfer->company_id = $sub_company['company_id'];
                    $transfer->transfer_status = Transfer::STATUS_LOCK;
                    $transfer->save(false);
                }

                $total = $company_total = 0;

                //remove old candidates if exists

                TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

                // transfer candidate for current company

                $candidates = TransferCandidate::find()
                    ->candidatesByTransfer($model->transfer_id)
                    ->filterCompanyId($sub_company['company_id'])
                    ->asArray()
                    ->all();

                foreach ($candidates as $key => $value) 
                {                    
                    $total += $value['bonus'] + ($value['hours'] * $value['candidate_hourly_rate']) + Yii::$app->params['transfer_cost'];

                    $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                //save total in transfer

                $transfer->company_total = $company_total;
                $transfer->total = $total;
                $transfer->save();

                //generate invoice for each transfer
                $invoice = Invoice::findOne(['transfer_id' => $transfer->transfer_id]);

                if (!$invoice) {
                    $invoice = new Invoice;
                    $invoice->transfer_id = $transfer->transfer_id;
                    $invoice->invoice_date = date('Y-m-d');
                    $invoice->invoice_status = 'unpaid';
                    $invoice->save();
                }

            }
        }

        $this->invoiceMail($id); // send invoice mail

        return [
            "operation" => "success",
            "message" => "Transfer locked successfully"
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

        $childs = Transfer::find()
            ->filterParent($model->transfer_id)
            ->all();

        //delete data for each child

        foreach ($childs as $key => $value)
        {
            Invoice::updateAll(['deleted' => 1], ['transfer_id' => $value->transfer_id]);

            //TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $value->transfer_id]);

            Transfer::updateAll(['deleted' => 1], ['transfer_id' => $value->transfer_id]);
        }

        //delete data for main transfer

        Invoice::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);

        //TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);

        Transfer::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);

        return [
            "operation" => "success",
            "message" => 'Transfer deleted successfully!'
        ];
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

}
