<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;

/**
 *  Account controller - Manage account as staff
 */
class AccountController extends Controller
{
    public function behaviors()
    {
        $behaviors = [];//parent::behaviors();

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
     * Updates password based on current password
     * @return array
     */
    public function actionUpdatePassword()
    {
        $staff = Yii::$app->user->identity;
        
        $password = Yii::$app->request->getBodyParam("password");
        $newPassword = Yii::$app->request->getBodyParam("newPassword");

        //validate current password 
        
        if(!$staff->validatePassword($password)) 
        {
            return [
                'operation' => 'error',
                'message' => 'Invalid current password provided'
            ];
        }
        
        //update password 
        
        $staff->setPassword($newPassword);
        $staff->save(false);
        
        return [
            'operation' => 'success',
            'message' => 'Your password has been reset'
        ];
    }
}