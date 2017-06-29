<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use company\models\Store;
use company\models\Company;

/**
 * Store controller - Manage store as Admin
 */
class StoreController extends Controller
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
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Store by companyId if provided
     * else by current login company id.
     * @param null $companyId
     * @return array|ActiveDataProvider
     */
    public function actionList($companyId = null)
    {
        $company = Yii::$app->user->identity;

        if($companyId)
        {
            $sub_company = Company::findOne([
                'parent_company_id' => $company->company_id,
                'company_id' => $companyId
            ]);
        }

        if($companyId && empty($sub_company)) {
            return [
                    "operation" => "error",
                    "message" => 'Company not found'
                ];
        }

        if(!$companyId) {
            $companyId = $company->company_id;
        }

        $query = Store::find()
            ->filterCompany($companyId);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return array
     */
    public function actionIndex()
    {
        $company = Company::findOne(Yii::$app->user->id);
        $list = [];

        if (isset($company->subCompanies) && count($company->subCompanies)>0) {

            $list['type'] = 'Company';
            $list['results'] = $company->subCompanies;

        } else if (isset($company->stores) && count($company->stores)>0) {

            $list['type'] = 'Stores';
            $list['results'] = $company->stores;
        }

        return $list;
    }
}
