<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use admin\models\Store;
use admin\models\Candidate;

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
     * Return a List of Store Accounts available.
     */
    public function actionList()
    {
        $query = Store::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a store account
     */
    public function actionCreate()
    {
        // Attempt to create new store
        $model = new Store();
        
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_name = Yii::$app->request->getBodyParam("name");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the store, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Store successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a store account
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Store::findOne((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Store not found."
                ];
        }

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_name = Yii::$app->request->getBodyParam("name");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the store, please contact us for assistance."
                ];
            }
        }

        Yii::info("[Store Updated] ".$model->store_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Store successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $store = Store::findOne((int)$id);

        if(!$store) {
            return [
                "operation" => "error",
                "message" => "Store not found or already deleted."
            ];
        }

        //Shouldn't be able to delete a store that has candidates assigned to it

        $candidates = candidate::findOne(['store_id' => $store->store_id]);

        if($candidates) {
            return [
                "operation" => "error",
                "message" => "Store have some candidates assigned to it."
            ];
        }

        Yii::warning("[Store Deleted] ".$store->store_name, __METHOD__);

        // Delete store
        $store->delete();

        return [
            "operation" => "success",
        ];
   
        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
}
