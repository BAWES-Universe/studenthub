<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Country;


/**
 * Country controller - Manage university as Admin
 */
class CountryController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
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
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
     * Return a List of Country Accounts available.
     */
    public function actionList()
    {
        $q = Yii::$app->request->get("q");

        $query = Country::find()
            ->listWithCandidateCount();

        if ($q) {
            $query->filterName($q);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Country Accounts available
     * without pagination.
     */
    public function actionAll()
    {
        $q = Yii::$app->request->get("q");

        $query = Country::find()
            ->listWithCandidateCount();

        if ($q) {
            $query->filterName($q);
        }

        return $query
            ->all();
    }

    /**
     * detail
     * without pagination.
     */
    public function actionView($id)
    {
        $data = Country::findOne($id);

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }
}
