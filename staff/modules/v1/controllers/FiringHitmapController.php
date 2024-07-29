<?php

namespace staff\modules\v1\controllers;

use Yii;
use common\models\FiringHitmap;
use staff\models\Company;
use staff\models\Fulltimer;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;

class FiringHitmapController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
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
            'class' => HttpBearerAuth::className(),
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
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $company_id = Yii::$app->request->get("company_id");

        if ($company_id) {
            return FiringHitmap::find()
                ->orderBy("firing_year DESC, firing_month DESC")
                ->andWhere(['company_id' => $company_id])
                ->limit(12)
                ->all(); //last 12 month
        }

        $companiesQuery = Company::find()
            ->filterParent();

        $result = [];

        foreach ($companiesQuery->batch() as $companies) {
            foreach ($companies as $company) {
                $data = [
                    "company" => [
                        "company_name" => $company->company_name
                    ],
                    "hitmap" => FiringHitmap::find()
                        ->orderBy("firing_year DESC, firing_month DESC")
                        ->andWhere(['company_id' => $company->company_id])
                        ->limit(12)
                        ->all()//last 12 month
                ];

                $total = 0;

                foreach ($data['hitmap'] as $value) {
                    $total += $value['total'];
                }

                $data["average"] = $total/ 12;

                $result[] = $data;
            }
        }

        return $result;
    }
}