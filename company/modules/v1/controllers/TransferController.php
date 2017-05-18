<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use company\models\Company;
use common\models\Candidate;
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
     */
    public function actionList()
    {
        $company = Yii::$app->user->identity;

        $query = Transfer::find()
            ->select('{{%transfer}}.*, {{%company}}.company_name, {{%company}}.company_email')
            ->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id')
            ->where(['{{%transfer}}.company_id' => $company->company_id])
            ->andWhere('parent_transfer_id IS NULL')
            ->orderBy('transfer_id DESC')
            ->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Transfer detail.
     */
    public function actionView($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $transfer = Transfer::find()
            ->select('{{%company}}.company_email, {{%company}}.company_name, {{%company}}.company_id, {{%transfer}}.*')
            ->innerJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id')
            ->where(['transfer_id' => $id])
            ->andWhere(['in', '{{%transfer}}.company_id', $company_ids])            
            ->asArray()
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        $transfer['candidates'] = TransferCandidates::find()
            ->select('{{%transfer_candidates}}.*, {{%store}}.store_name, {{%company}}.company_name, {{%company}}.company_email, {{%candidate}}.candidate_name, {{%candidate}}.candidate_email')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
            ->asArray()
            ->all();

        //invoices 

        $transfer['invoices'] = Invoice::find()
            ->innerJoin('transfer', 'transfer.transfer_id = invoice.transfer_id')
            ->where(['transfer.transfer_id' => $id])
            ->orWhere(['transfer.parent_transfer_id' => $id])
            ->all();

        return $transfer;
    }

    /**
     * Initiate transfer.
     */
    public function actionCreate()
    {
        $company = Yii::$app->user->identity;

        //validate input

        $errors = Transfer::validate_candidates(
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

            $tc = new TransferCandidates;
            $tc->transfer_cost = Yii::$app->params['transfer_cost'];
            $tc->candidate_hourly_rate = $hourly_rate;
            $tc->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
            $tc->attributes = $value;
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
     */
    public function actionEdit($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $model = Transfer::find()
            ->where(['transfer_id' => $id])
            ->andWhere(['in', '{{%transfer}}.company_id', $company_ids])
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

        $errors = Transfer::validate_candidates(
            $company->company_id,
            Yii::$app->request->getBodyParam("candidates")
        );

        if($errors) {
            return [
                    "operation" => "error",
                    "message" => $errors
                ];
        }
        
        $transaction = Yii::$app->db->beginTransaction();

        //remove old candidates 

        TransferCandidates::deleteAll(['transfer_id' => $model->transfer_id]);

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

            $tc = new TransferCandidates;
            $tc->transfer_cost = Yii::$app->params['transfer_cost'];
            $tc->candidate_hourly_rate = $hourly_rate;
            $tc->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
            $tc->attributes = $value;
            $tc->transfer_id = $model->transfer_id;

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

        //update child transfers 

        //select distinct company and update transfer for each company if already added else create new 

        $sub_companies = TransferCandidates::find()
            ->select('{{%store}}.company_id')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $model->transfer_id
            ])
            ->distinct()
            ->asArray()
            ->all();
        
        foreach ($sub_companies as $key => $sub_company) {

            //move transfer to transfer
            $transfer = Transfer::findOne([
                    'parent_transfer_id' => $model->transfer_id,
                    'company_id' => $sub_company['company_id']
                ]);

            if(!$transfer) {
                $transfer = new Transfer;
                $transfer->attributes = $model->attributes;
                $transfer->parent_transfer_id = $model->transfer_id;
                $transfer->company_id = $sub_company['company_id'];                    
            }

            $transfer->save(false);

            $total = $company_total = 0;

            //remove old candidate id exists 

            TransferCandidates::deleteAll(['transfer_id' => $transfer->transfer_id]);

            // transfer candidate for current company

            $candidates = TransferCandidates::find()
                ->select('{{%candidate}}.candidate_hourly_rate, {{%transfer_candidates}}.*')
                ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
                ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
                ->where([
                    '{{%transfer_candidates}}.transfer_id' => $model->transfer_id,
                    '{{%store}}.company_id' => $sub_company['company_id']
                ])
                ->asArray()
                ->all();

            foreach ($candidates as $key => $value)
            {
                //get hourly rate

                $transfer_candidate = new TransferCandidates;
                $transfer_candidate->transfer_id = $transfer->transfer_id;
                $transfer_candidate->candidate_id = $value['candidate_id'];
                $transfer_candidate->hours = $value['hours'];
                $transfer_candidate->bonus = $value['bonus'];
                $transfer_candidate->transfer_cost = Yii::$app->params['transfer_cost'];
                $transfer_candidate->candidate_hourly_rate = $value['candidate_hourly_rate'];
                $transfer_candidate->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
                $transfer_candidate->save();

                $total += $transfer_candidate->bonus + ($transfer_candidate->hours * $transfer_candidate->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];

                $company_total += $transfer_candidate->bonus + ($transfer_candidate->hours * Yii::$app->params['candidate_max_hourly_rate']);
            }

            //save total in transfer

            $transfer->company_total = $company_total;
            $transfer->total = $total;
            $transfer->save();

            //generate invoice for each transfer 
            $invoice = Invoice::findOne(['transfer_id' => $transfer->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $transfer->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();    
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
     * Download Transfer as PDF 
     */
    public function actionPdf($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $transfer = Invoice::find()
            ->select('{{%invoice}}.*, {{%transfer}}.*')
            ->innerJoin('{{%transfer}}', '{{%transfer}}.transfer_id = {{%invoice}}.transfer_id')
            ->where(['{{%invoice}}.invoice_id' => $id])
            ->andWhere(['in', '{{%transfer}}.company_id', $company_ids])            
            ->asArray()
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found!'
                ];
        }
        $transfer['company'] = Company::findOne($transfer['company_id']);

        $transfer['candidates'] = TransferCandidates::find()
            ->select('{{%transfer_candidates}}.*, {{%store}}.store_name, {{%company}}.company_name, {{%company}}.company_email, {{%candidate}}.candidate_name, {{%candidate}}.candidate_email')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
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
            'cssInline' => '.kv-heading-1{font-size:38px}', 
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
     * Mark Transfer as Payment Sent
     */
    public function actionPaymentSent($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $transfer = Transfer::find()
            ->where(['transfer_id' => $id])
            ->andWhere(['{{%transfer}}.company_id' => $company->company_id])            
            ->one();

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found'
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
     *  Lock transfer
     */
    public function actionLock($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $model = Transfer::find()
            ->where(['transfer_id' => $id])
            ->andWhere(['in', '{{%transfer}}.company_id', $company_ids])            
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

        $sub_companies = TransferCandidates::find()
            ->select('{{%store}}.company_id')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $model->transfer_id
            ])
            ->distinct()
            ->asArray()
            ->all();

        /**
         * if transfer initiated by parent company split it for each 
         * sub companies
         */
        if(!$sub_companies) 
        {
            //generate invoice for main transfer if no sub companies else generate invoice for 
            //each sub companies 
            $invoice = Invoice::findOne(['transfer_id' => $transfer->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $model->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();
            }

            return [
                "operation" => "success",
                "message" => "Transfer locked successfully"
            ];
        }
        
        foreach ($sub_companies as $key => $sub_company) {

            //move transfer to transfer
            $transfer = Transfer::findOne([
                    'parent_transfer_id' => $model->transfer_id,
                    'company_id' => $sub_company['company_id']
                ]);

            if(!$transfer) {
                $transfer = new Transfer;
                $transfer->attributes = $model->attributes;
                $transfer->parent_transfer_id = $model->transfer_id;
                $transfer->company_id = $sub_company['company_id'];
                $transfer->transfer_status = Transfer::STATUS_LOCK;
                $transfer->save(false);
            }
            
            $total = $company_total = 0;

            //remove old candidates if exists 

            TransferCandidates::deleteAll(['transfer_id' => $transfer->transfer_id]);

            // transfer candidate for current company

            $candidates = TransferCandidates::find()
                ->select('{{%candidate}}.candidate_hourly_rate, {{%transfer_candidates}}.*')
                ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
                ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
                ->where([
                    '{{%transfer_candidates}}.transfer_id' => $model->transfer_id,
                    '{{%store}}.company_id' => $sub_company['company_id']
                ])
                ->asArray()
                ->all();

            foreach ($candidates as $key => $value)
            {
                //get hourly rate

                $transfer_candidate = new TransferCandidates;
                $transfer_candidate->transfer_id = $transfer->transfer_id;
                $transfer_candidate->candidate_id = $value['candidate_id'];
                $transfer_candidate->hours = $value['hours'];
                $transfer_candidate->bonus = $value['bonus'];
                $transfer_candidate->transfer_cost = Yii::$app->params['transfer_cost'];
                $transfer_candidate->candidate_hourly_rate = $value['candidate_hourly_rate'];
                $transfer_candidate->company_hourly_rate = Yii::$app->params['candidate_max_hourly_rate'];
                $transfer_candidate->save();

                $total += $transfer_candidate->bonus + ($transfer_candidate->hours * $transfer_candidate->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];

                $company_total += $transfer_candidate->bonus + ($transfer_candidate->hours * Yii::$app->params['candidate_max_hourly_rate']);
            }

            //save total in transfer

            $transfer->company_total = $company_total;
            $transfer->total = $total;
            $transfer->save();

            //generate invoice for each transfer 
            $invoice = Invoice::findOne(['transfer_id' => $transfer->transfer_id]);

            if(!$invoice) {
                $invoice = new Invoice;
                $invoice->transfer_id = $transfer->transfer_id;
                $invoice->invoice_date = date('Y-m-d');
                $invoice->invoice_status = 'unpaid';
                $invoice->save();    
            }
            $this->invoiceMail($invoice->invoice_id); // send invoice mail
        }

        return [
            "operation" => "success",
            "message" => "Transfer locked successfully"
        ];
    }


    public function invoiceMail($id)
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $transfer = Invoice::find()
            ->select('{{%invoice}}.*, {{%transfer}}.*')
            ->innerJoin('{{%transfer}}', '{{%transfer}}.transfer_id = {{%invoice}}.transfer_id')
            ->where(['{{%invoice}}.invoice_id' => $id])
            ->andWhere(['in', '{{%transfer}}.company_id', $company_ids])
            ->asArray()
            ->one();

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }
        $transfer['company'] = Company::findOne($company->company_id);

        $transfer['candidates'] = TransferCandidates::find()
            ->select('{{%transfer_candidates}}.*, {{%store}}.store_name, {{%company}}.company_name, {{%company}}.company_email, {{%candidate}}.candidate_name, {{%candidate}}.candidate_email')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
            ->asArray()
            ->all();

        if($transfer['invoice_status'] == 'paid')
            $template = 'receipt';
        else
            $template = 'invoice';

        $this->layout = 'pdf';
        $content = $this->render($template, [
            'transfer' => $transfer,
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
            'cssInline' => '.kv-heading-1{font-size:38px}',
            // set mPDF properties on the fly
            'options' => [],//['title' => 'Booking #'.$id],
            // call mPDF methods on the fly
        ]);

        $mpdf = $pdf->api; // fetches mpdf api
        $mpdf->WriteHtml($content); // call mpdf write html
        $pdfAttachment = $mpdf->Output($template.'.pdf', 'S'); // call the mpdf api output as needed

        $message = Yii::$app->mailer->compose('invoice-receipt-attachment',['detail'=>$transfer]);
        $message->setFrom(Yii::$app->params['invoiceFrom']);
        $message->attachContent($pdfAttachment,['fileName' => $template.'-#'.$id.'.pdf', 'contentType' => 'application/pdf']);
        return $message->setTo($transfer['company']['company_email'])
            ->setCc('finance@bawes.net')
            ->setSubject('Invoice Attachment #'.$id)
            ->send();
    }
}
