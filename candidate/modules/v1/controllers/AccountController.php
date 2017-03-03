<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\InvoiceCandidates;

/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends Controller
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
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Salary transfers
     */
    public function actionSalary()
    {
        $candidate = Yii::$app->user->identity;

        $query = InvoiceCandidates::find()
            ->select([
                '{{%invoice_candidates}}.ic_id', 
                '{{%invoice_candidates}}.invoice_id', 
                '{{%company}}.company_name',
                '{{%company}}.company_email',
                '{{%invoice_candidates}}.hourly_rate', 
                '{{%invoice_candidates}}.hours', 
                '{{%invoice_candidates}}.bonus', 
                '({{%invoice_candidates}}.hourly_rate * {{%invoice_candidates}}.hours) + {{%invoice_candidates}}.bonus as total', 
                '{{%invoice_candidates}}.ic_created_at'
            ])
            ->innerJoin('{{%invoice}}', '{{%invoice}}.invoice_id = {{%invoice_candidates}}.invoice_id')
            ->innerJoin('{{%company}}', '{{%company}}.company_id = {{%invoice}}.company_id')
            ->where([
                'candidate_id' => $candidate->candidate_id
            ])
            ->asArray();

        return new ActiveDataProvider([
            'query' => $query, 
        ]);
    }

    /**
     * Return currnet employer detail
     */
    public function actionEmployer()
    {
        $candidate = Yii::$app->user->identity;

        //store detail 

        if(empty($candidate->store)) {
            return [
                "operation" => "error",
                "message" => "No employer detail found"
            ];
        }

        //company details 

        if(empty($candidate->store->company)) {
            $company_id = '';
            $company_name = '';
            $company_email = '';            
        }else{
            $company_id = $candidate->store->company->company_id; 
            $company_name = $candidate->store->company->company_name;
            $company_email = $candidate->store->company->company_email;            
        }

        return [
            'company_id' => $company_id,
            'store_id' => $candidate->store->store_id,
            'store_name' => $candidate->store->store_name,
            'company_name' => $company_name,
            'company_email'=> $company_email
        ];
    }
}
