<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use company\models\Company;
use company\models\Candidate;
use common\models\Transfer;
use common\models\TransferCandidates;
use common\models\Invoice;
use common\models\InvoiceCandidates;
use yii\db\Query;

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
     * Return a List of Transfer draft.
     */
    public function actionList()
    {
        $company = Yii::$app->user->identity;

        // list all sub companies

        $companies = Company::findAll(['parent_company_id' => $company->company_id]);

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        $query = Transfer::find()
            ->select('{{%transfer}}.*, {{%company}}.company_name, {{%company}}.company_email')
            ->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id')
            ->where(['in', '{{%transfer}}.company_id', $company_ids])
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

        $transfer = Transfer::find()
            ->where([
                'company_id' => $company->company_id,
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

        $transfer['candidates'] = TransferCandidates::find()
            ->select('{{%transfer_candidates}}.*, {{%store}}.store_name, {{%company}}.company_name, {{%company}}.company_email, {{%candidate}}.candidate_hourly_rate, {{%candidate}}.candidate_name, {{%candidate}}.candidate_email')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer['transfer_id']
            ])
            ->asArray()
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

        foreach ($candidates as $key => $value) {
            $tc = new TransferCandidates;
            $tc->attributes = $value;
            $tc->transfer_id = $transfer->transfer_id;

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

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Transfer initiated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     *  Lock transfer to invoice
     */
    public function actionLock($id)
    {
        $company = Yii::$app->user->identity;

        $transfer = Transfer::findOne([
                'company_id' => $company->company_id,
                'transfer_id' => $id
            ]);

        if(!$transfer) {
            return [
                    "operation" => "error",
                    "message" => 'Transfer not found!'
                ];
        }

        //select distinct company and create invoice for each company

        $companies = TransferCandidates::find()
            ->select('{{%store}}.company_id')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer->transfer_id
            ])
            ->distinct()
            ->asArray()
            ->all();

        foreach ($companies as $key => $sub_company) {

            //move transfer to invoice

            $invoice = new Invoice;
            $invoice->attributes = $transfer->attributes;
            $invoice->company_id = $sub_company['company_id'];
            $invoice->save(false);

            $total = 0;

            // transfer candidate for current company

            $candidates = TransferCandidates::find()
                ->select('{{%candidate}}.candidate_hourly_rate, {{%transfer_candidates}}.*')
                ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
                ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
                ->where([
                    '{{%transfer_candidates}}.transfer_id' => $transfer->transfer_id,
                    '{{%store}}.company_id' => $sub_company['company_id']
                ])
                ->asArray()
                ->all();

            foreach ($candidates as $key => $value)
            {
                //get hourly rate

                $invoice_candidate = new InvoiceCandidates;
                $invoice_candidate->invoice_id = $invoice->invoice_id;
                $invoice_candidate->candidate_id = $value['candidate_id'];
                $invoice_candidate->hours = $value['hours'];
                $invoice_candidate->bonus = $value['bonus'];
                $invoice_candidate->hourly_rate = $value['candidate_hourly_rate'];
                $invoice_candidate->save();

                $total += $invoice_candidate->bonus + ($invoice_candidate->hours * $invoice_candidate->hourly_rate);

                //delete transfer candidate

                TransferCandidates::deleteAll(['tc_id' => $value['tc_id']]);
            }

            //save total in invoice

            $invoice->total = $total;
            $invoice->save();
        }

        //delete transfer

        $transfer->delete();

        return [
                "operation" => "success",
                "message" => "Transfer locked successfully"
            ];
    }
}
