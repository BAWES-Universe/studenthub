<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use common\models\Staff;

/**
 * Staff controller - Manage staff accounts as Admin
 */
class StaffController extends Controller
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
     * Return a List of Staff Accounts available.
     */
    public function actionList()
    {
        return Staff::find()->all();
    }

    /**
     * Create a staff account
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Staff();
        $model->scenario = "newAccount";

        $model->staff_name = Yii::$app->request->getBodyParam("name");
        $model->staff_email =Yii::$app->request->getBodyParam("email");
        $model->staff_password_hash = Yii::$app->request->getBodyParam("password");

        if (!$model->signup())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Staff account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a staff account
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Staff::findOne((int) $id);
        $model->staff_name = Yii::$app->request->getBodyParam("name");
        $model->staff_email =Yii::$app->request->getBodyParam("email");

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
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info("[Staff Account Updated] ".$model->staff_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff account successfully updated"
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
        $staffMember = Staff::findOne((int)$id);

        if($staffMember){
            Yii::warning("[Staff Account Deleted] ".$staffMember->staff_email, __METHOD__);

            // Delete the account
            $staffMember->delete();
            return [
                "operation" => "success",
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Account not found or already deleted."
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occured, please contact us for assistance."
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
}
