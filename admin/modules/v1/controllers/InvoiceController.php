<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\db\Query;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use admin\models\Company;
use common\models\Invoice;
use common\models\InvoiceCandidates;

/**
 * Invoice controller - Manage Invoice
 */
class InvoiceController extends Controller
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
     * Return a List of Invoice.
     */
    public function actionList()
    {
        $query = Invoice::find()
            ->select('{{%invoice}}.*, {{%company}}.company_name, {{%company}}.company_email')
            ->leftJoin('{{%company}}', '{{%company}}.company_id = {{%invoice}}.company_id')
            ->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Invoice detail.
     */
    public function actionView($id)
    {
        $invoice = Invoice::find()
            ->where([
                'invoice_id' => $id
            ])
            ->asArray()
            ->one();
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found'
                ];
        }

        //get total profit

        $invoice['profit'] = InvoiceCandidates::find()
            ->where([
                '{{%invoice_candidates}}.invoice_id' => $invoice['invoice_id']
            ])
            ->sum('((2 - {{%invoice_candidates}}.hourly_rate) * hours)');

        $invoice['candidates'] = InvoiceCandidates::find()
            ->select('{{%invoice_candidates}}.*, 
                {{%store}}.store_name, 
                {{%company}}.company_name, 
                {{%company}}.company_email, 
                {{%candidate}}.candidate_name, 
                {{%candidate}}.candidate_email, 
                {{%candidate}}.candidate_iban, 
                {{%bank}}.bank_name, 
                ((2 - {{%invoice_candidates}}.hourly_rate) * hours) as profit
            ')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%invoice_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->leftJoin('{{%bank}}', '{{%bank}}.bank_id = {{%candidate}}.bank_id')
            ->where([
                '{{%invoice_candidates}}.invoice_id' => $invoice['invoice_id']
            ])
            ->asArray()
            ->all();

        return $invoice;
    }

    /**
     * Export Invoice detail.
     */
    public function actionExport($id)
    {
        $invoice = Invoice::find()
            ->where([
                'invoice_id' => $id
            ])
            ->asArray()
            ->one();
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found!'
                ];
        }

        $candidates = InvoiceCandidates::find()
            ->where([
                '{{%invoice_candidates}}.invoice_id' => $invoice['invoice_id']
            ])
            ->all();

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                'candidate.candidate_name',
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'transfer_cost',
                'hourly_rate',
                'bonus',
                'total',
                'candidate.candidate_iban', 
                'candidate.bank.bank_name'
            ]
        ]);
    }

    /** 
     * Mark Invoice as Payment Received
     */ 
    public function actionPaymentReceived($id)
    {
        $invoice = Invoice::findOne([
                'invoice_id' => $id
            ]);
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found!'
                ];
        }

        $invoice->invoice_status = Invoice::STATUS_PAYMENT_RECEIVED;
        $invoice->save();

        return [
                "operation" => "success",
                "message" => 'Invoice marked as "Payment Received" successfully'
            ];
    }

    /** 
     * Mark Invoice as Initiated
     */ 
    public function actionUnlock($id)
    {
        $invoice = Invoice::findOne([
                'invoice_id' => $id
            ]);
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found!'
                ];
        }

        // to unlock invoice, invoice status should be in lock status 

        if($invoice->invoice_status != Invoice::STATUS_LOCK)
        {
            return [
                    "operation" => "error",
                    "message" => 'Invoice status should be "Locked" to unlock it!'
                ];
        }

        $invoice->invoice_status = Invoice::STATUS_INITIATED;
        $invoice->save();

        return [
                "operation" => "success",
                "message" => 'Invoice unlocked successfully'
            ];
    }

    /** 
     * Mark Invoice as Payment In Process
     */ 
    public function actionPaymentInProcess($id)
    {
        $invoice = Invoice::findOne([
                'invoice_id' => $id
            ]);
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found'
                ];
        }

        $invoice->invoice_status = Invoice::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;
        $invoice->save();

        return [
                "operation" => "success",
                "message" => 'Invoice marked as "Salary Distribution in Progress" successfully'
            ];
    }

    /** 
     * Mark Invoice as Payment In Completed
     */ 
    public function actionPaymentCompleted($id)
    {
        $invoice = Invoice::findOne([
                'invoice_id' => $id
            ]);
            
        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found'
                ];
        }

        $invoice->invoice_status = Invoice::STATUS_TRANSFER_COMPLETE;
        $invoice->save();

        return [
            "operation" => "success",
            "message" => 'Invoice marked as "Payment Complete" successfully'
        ];
    }
}
