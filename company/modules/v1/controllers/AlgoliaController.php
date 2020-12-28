<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;


/**
 * Algolia controller
 */
class AlgoliaController extends Controller
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
     * Return auto disposable secure api key
     */
    public function actionKey()
    {
        $ttl = 60 * 2; //2 min

        $params = [
            'restrictIndices' => [
                Yii::$app->params['algolia_candidate_index']
            ],
            'filters' => '',
            'validUntil' => time() + $ttl,
            'userToken' => Yii::$app->user->getId(),
            //'getRankingInfo' => true,
            //'aroundLatLngViaIP' => true,
            'aroundRadius' => 'all'
        ];

        $securedApiKey = Yii::$app->algolia->getSecureApiKey($params);

        return [
            'securedApiKey' => $securedApiKey,
            'securedApiKeyValidUntil' => $params['validUntil'],
            'appId' => Yii::$app->algolia->appId
        ];
    }
}
