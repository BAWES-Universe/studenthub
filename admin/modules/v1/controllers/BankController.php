<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\models\Bank;

/**
 * Bank controller - Manage bank as Admin
 */
class BankController extends Controller
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
     * Return a List of Bank Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Bank::find();
        $query->notDeleted();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a bank account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new bank
        $model = new Bank();
        
        $model->bank_name = Yii::$app->request->getBodyParam("name");
        $model->bank_swift_code = Yii::$app->request->getBodyParam("swift_code");
        $model->bank_address = Yii::$app->request->getBodyParam("address");
        $model->bank_transfer_type = Yii::$app->request->getBodyParam("type");
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
                    "message" => "We've faced a problem creating the bank, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Bank created successfully "
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a bank account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Bank::findOne((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Bank not found."
                ];
        }

        $model->bank_name = Yii::$app->request->getBodyParam("name");
        $model->bank_swift_code = Yii::$app->request->getBodyParam("swift_code");
        $model->bank_address = Yii::$app->request->getBodyParam("address");
        $model->bank_transfer_type = Yii::$app->request->getBodyParam("type");

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
                    "message" => "We've faced a problem updating the bank, please contact us for assistance."
                ];
            }
        }

        Yii::info("[Bank Updated] ".$model->bank_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Bank successfully updated"
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
        $bank = Bank::findOne((int)$id);

        if(!$bank) {
            return [
                "operation" => "error",
                "message" => "Bank not found or already deleted"
            ];
        }

        if(count($bank->candidate)>0) {
            return [
                "operation" => "error",
                "message" => "Bank already assigned to ".count($bank->candidate)." candidate(s)"
            ];
        }

        Yii::warning("[Bank Soft Deleted] ".$bank->bank_name, __METHOD__);

        // Delete bank
        $bank->softDelete();

        return [
            "operation" => "success",
            "message" => "Bank deleted successfully"
        ];
   
        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
}
