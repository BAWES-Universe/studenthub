<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use company\models\Company;
use common\models\Candidate;
use common\models\Invoice;
use common\models\InvoiceCandidates;
use yii\db\Query;

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
                'Access-Control-Request-Method' => ['POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
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
     * Return a List of Invoice.
     */
    public function actionList()
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $query = Invoice::find()
            ->select('{{%invoice}}.*, {{%company}}.company_name, {{%company}}.company_email')
            ->leftJoin('{{%company}}', '{{%company}}.company_id = {{%invoice}}.company_id')
            ->where(['in', '{{%invoice}}.company_id', $company_ids])
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
        $company = Yii::$app->user->identity;

        $invoice = Invoice::find()
            ->where([
                'company_id' => $company->company_id,
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

        $invoice['candidates'] = InvoiceCandidates::find()
            ->select('{{%invoice_candidates}}.*, {{%store}}.store_name, {{%company}}.company_name, {{%company}}.company_email, {{%candidate}}.candidate_name, {{%candidate}}.candidate_email')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%invoice_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->where([
                '{{%invoice_candidates}}.invoice_id' => $invoice['invoice_id']
            ])
            ->asArray()
            ->all();

        return $invoice;
    }

    /**
     * Mark Invoice as Payment Sent
     */
    public function actionPaymentSent($id)
    {
        $company = Yii::$app->user->identity;

        $invoice = Invoice::findOne([
                'company_id' => $company->company_id,
                'invoice_id' => $id
            ]);

        if(!$invoice) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found'
                ];
        }

        $invoice->invoice_status = Invoice::STATUS_PAYMENT_SENT;
        $invoice->save();

        return [
                "operation" => "success",
                "message" => 'Invoice marked as "Payment Sent" successfully'
            ];
    }

    /**
     *  Lock invoice
     */
    public function actionLock($id)
    {
        $company = Yii::$app->user->identity;

        $model = Invoice::findOne([
                'company_id' => $company->company_id,
                'invoice_id' => $id
            ]);

        if(!$model) {
            return [
                    "operation" => "error",
                    "message" => 'Invoice not found!'
                ];
        }

        //select distinct company and create invoice for each company

        $companies = InvoiceCandidates::find()
            ->select('{{%store}}.company_id')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%invoice_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->where([
                '{{%invoice_candidates}}.invoice_id' => $model->invoice_id
            ])
            ->distinct()
            ->asArray()
            ->all();

        /**
         * if invoice initiated by parent company split it for each 
         * sub companies else change status to lock 
         */
        if(sizeof($companies) == 1)
        {
            $model->invoice_status = Invoice::STATUS_LOCK;
            $model->save();

            return [
                "operation" => "success",
                "message" => "Invoice locked successfully"
            ];
        } 

        foreach ($companies as $key => $sub_company) {

            //move invoice to invoice

            $invoice = new Invoice;
            $invoice->attributes = $model->attributes;
            $invoice->company_id = $sub_company['company_id'];
            $invoice->invoice_status = Invoice::STATUS_LOCK;
            $invoice->save(false);

            $total = 0;

            // invoice candidate for current company

            $candidates = InvoiceCandidates::find()
                ->select('{{%candidate}}.candidate_hourly_rate, {{%invoice_candidates}}.*')
                ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%invoice_candidates}}.candidate_id')
                ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
                ->where([
                    '{{%invoice_candidates}}.invoice_id' => $model->invoice_id,
                    '{{%store}}.company_id' => $sub_company['company_id']
                ])
                ->asArray()
                ->all();

            foreach ($candidates as $key => $value)
            {
                //get hourly rate

                $invoice_candidate = new InvoiceCandidates;
                $invoice_candidate->invoice_id = $model->invoice_id;
                $invoice_candidate->candidate_id = $value['candidate_id'];
                $invoice_candidate->hours = $value['hours'];
                $invoice_candidate->bonus = $value['bonus'];
                $invoice_candidate->transfer_cost = Yii::$app->params['transfer_cost'];
                $invoice_candidate->hourly_rate = $value['candidate_hourly_rate'];
                $invoice_candidate->save();

                $total += $invoice_candidate->bonus + ($invoice_candidate->hours * $invoice_candidate->hourly_rate) + Yii::$app->params['transfer_cost'];

                //delete invoice candidate

                InvoiceCandidates::deleteAll(['ic_id' => $value['ic_id']]);
            }

            //save total in invoice

            $invoice->total = $total;
            $invoice->save();
        }

        //delete main invoice

        $model->delete();

        return [
                "operation" => "success",
                "message" => "Invoice locked successfully"
            ];
    }

    /**
     * Initiate invoice.
     */
    public function actionCreate()
    {
        $company = Yii::$app->user->identity;

        //validate input

        $errors = Invoice::validate_candidates(
            $company->company_id,
            Yii::$app->request->getBodyParam("candidates")
        );

        if($errors) {
            return [
                    "operation" => "error",
                    "message" => $errors
                ];
        }

        //save invoice

        $transaction = Yii::$app->db->beginTransaction();

        $invoice = new Invoice;
        $invoice->company_id = $company->company_id;

        if(!$invoice->save()){

            if(isset($invoice->errors)){
                return [
                    "operation" => "error",
                    "message" => $invoice->errors
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

        $total = 0;

        foreach ($candidates as $key => $value) {

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

            $tc = new InvoiceCandidates;
            $tc->transfer_cost = Yii::$app->params['transfer_cost'];
            $tc->hourly_rate = $hourly_rate;
            $tc->attributes = $value;
            $tc->invoice_id = $invoice->invoice_id;

            $total += $value['bonus'] + ($value['hours'] * $hourly_rate) + Yii::$app->params['transfer_cost'];

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

        $invoice->total = $total;
        $invoice->save();

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Invoice initiated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
}
