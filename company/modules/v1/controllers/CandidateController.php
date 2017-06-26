<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use company\models\Store;
use company\models\Company;
use company\models\Candidate;

/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends Controller
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
            'collectionOptions' => ['GET'],
            'resourceOptions' => ['GET', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Candidate Accounts assigned to work
     * for current company.
     */
    public function actionList()
    {
        $company = Yii::$app->user->identity;

        $query = $company->getCandidates();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts assigned to work without pagination 
     * for current company.
     */
    public function actionListAll()
    {        
        $company = Yii::$app->user->identity;

        return $company->candidates;
    }

    /**
     * Return a List of Candidate Accounts assigned to
     * Specific Store.
     */
    public function actionFilter()
    {
        $company = Yii::$app->user->identity;

        $store_id = Yii::$app->request->getBodyParam("store_id");

        $store = Store::findOne($store_id);

        if(empty($store) || empty($store->company)) {
            return [
                    "operation" => "error",
                    "message" => "Store not valid."
                ];
        }

        $arr_store_company_ids = [
                $store->company->company_id,
                $store->company->parent_company_id
            ];

        //check if logined company does not belong to store companies

        if(!in_array($company->company_id, $arr_store_company_ids)) {
            return [
                    "operation" => "error",
                    "message" => "You are not authorize to list candidates from this store."
                ];
        }

        $query = Candidate::find()
            ->filterStore($store_id)
            ->notDeleted();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
