<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;  


/**
 * GoogleMap controller - Call Google Map API
 */
class GoogleMapController extends Controller
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
     * return list of places by keyword 
     * @return type
     */
    public function actionPlacePredictions() {
        $query = Yii::$app->request->get('query');
        $country_name = Yii::$app->request->get('country_name');
        return Yii::$app->googleMap->getPlacePredictions($query, urldecode($country_name));
    }
    
    /**
     * Return place detail by google map place_id
     * @param type $place_id
     * @return type
     */
    public function actionPlaceDetail($place_id) {
        $name = Yii::$app->request->get('name');
        $country_name = Yii::$app->request->get('country_name');

        return Yii::$app->googleMap->placeDetail($place_id, $name, urldecode($country_name));
    }
}
