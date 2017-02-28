<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
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
            ->where(['in', 'company_id', $company_ids]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
