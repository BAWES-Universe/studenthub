<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use company\models\Company;

/**
 * Company controller - Manage company accounts as Admin
 */
class CompanyController extends Controller
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
                'Access-Control-Request-Method' => ['GET', 'OPTIONS'],
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
            'collectionOptions' => ['GET', 'OPTIONS'],
            'resourceOptions' => ['GET', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Company Accounts available.
     */
    public function actionList()
    {
        $company = Yii::$app->user->identity;

        $query = Company::find()
            ->select([
                'company_id', 
                'parent_company_id',
                'company_name', 
                'company_email',
                'company_status',
                'company_created_at', 
                'company_updated_at'
            ])
            ->where(['parent_company_id' => $company->company_id])
            ->asArray();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
