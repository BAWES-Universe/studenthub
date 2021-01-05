<?php

namespace inspector\modules\v1\controllers;

use Yii;
use yii\rest\Controller;


/**
 *  Account controller - Manage account as inspector
 */
class AccountController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'test'];

        return $behaviors;
    }

    public function actionTest() {
        return [
                'operation' => 'success'
            ];
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
     * Updates password based on current password
     * @return array
     */
    public function actionUpdatePassword()
    {
        $password = Yii::$app->request->getBodyParam("password");
        $newPassword = Yii::$app->request->getBodyParam("newPassword");

        if(!$password || !$newPassword) {
            return [
                'operation' => 'error',
                'message' => 'Password field required'
            ];
        }

        $model = Yii::$app->user->identity;
        
        //validate current password 

        if(!$model->validatePassword($password)) {
            return [
                'operation' => 'error',
                'message' => 'Invalid Password'
            ];
        }

        $model->scenario = 'updatePassword';
        
        $model->inspector_password_hash = $newPassword;
        
        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->getErrors()
            ];
        }
        
        return [
            "operation" => "success",
            'message' => 'Your password has been reset'
        ];
    }
}
