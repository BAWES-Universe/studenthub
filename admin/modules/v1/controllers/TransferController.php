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
     * Return a List of Transfer.
     */
    public function actionList()
    {
        $query = Transfer::find()
            ->select('{{%transfer}}.*, {{%company}}.company_name, {{%company}}.company_email')
            ->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id')
            ->where('parent_transfer_id IS NULL')
            ->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Download Transfer as PDF 
     */
    public function actionPdf($id)
    {
        $transfer = Invoice::find()
            ->select('{{%invoice}}.*, {{%transfer}}.*')
            ->innerJoin('{{%transfer}}', '{{%transfer}}.transfer_id = {{%invoice}}.transfer_id')
            ->where(['{{%invoice}}.invoice_id' => $id])
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
            'methods' => [ 
                'SetHeader'=>['Transfer #'.$transfer['transfer_id']], 
                'SetFooter'=>['{PAGENO}'],
            ]
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

        //get total profit

        $transfer['profit'] = TransferCandidates::find()
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
            ->sum('(({{%transfer_candidates}}.company_hourly_rate - {{%transfer_candidates}}.candidate_hourly_rate ) * hours) - {{%transfer_candidates}}.transfer_cost');
            // transfer cost will be on admin  

        $transfer['candidates'] = TransferCandidates::find()
            ->select('{{%transfer_candidates}}.*, 
                {{%store}}.store_name, 
                {{%company}}.company_name, 
                {{%company}}.company_email, 
                {{%candidate}}.candidate_name, 
                {{%candidate}}.candidate_email, 
                {{%candidate}}.candidate_iban, 
                {{%bank}}.bank_name, 
                (({{%transfer_candidates}}.company_hourly_rate - {{%transfer_candidates}}.candidate_hourly_rate) * hours) - transfer_cost as profit
            ')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->leftJoin('{{%bank}}', '{{%bank}}.bank_id = {{%candidate}}.bank_id')
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
     * Export Transfer detail.
     */
    public function actionExport($id)
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
                    "message" => 'Transfer not found!'
                ];
        }

        $candidates = TransferCandidates::find()
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
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
                'candidate_total',
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

        $transfer->transfer_status = Transfer::STATUS_PAYMENT_RECEIVED;
        $transfer->save();

        // mark invoice as paid for all child transfer and main transfer in case of no child company 

        Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $transfer->transfer_id]);

        $child_transfers = Transfer::findAll(['parent_transfer_id' => $transfer->transfer_id]);

        foreach ($child_transfers as $key => $value) {
            Invoice::updateAll(['invoice_status' => 'paid'], ['transfer_id' => $value->transfer_id]);
        }

        //send notification to company transfer available to download 

        Yii::$app->mailer->compose("invoiceReceiptAvailable",
            [
                "transfer" => $transfer,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($transfer->company->company_email)
            ->setSubject('Transfer receipt available to download!')
            ->send();

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
            ->select('{{%candidate}}.candidate_id, {{%candidate}}.candidate_name')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->where([
                '{{%transfer_candidates}}.paid' => 0,
                'transfer_id' => $id
            ])
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
}
