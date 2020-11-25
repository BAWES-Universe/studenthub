<?php

namespace inspector\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use inspector\models\Inspector;


/**
 * Auth controller provides the initial access token that is required for further requests
 * It initially authorizes via Http Basic Auth using a base64 encoded username and password
 */
class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        //Allow XHR Requests from our different subdomains and dev machines
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

        //Basic Auth accepts Base64 encoded username/password and decodes it for you
        $behaviors['authenticator'] = [
            'class' => HttpBasicAuth::className(),
            'except' => ['options'],
            'auth' => function ($email, $password) {

                $user = Inspector::findByEmail(trim($email));
                
                if ($user && $user->validatePassword(trim($password))) {
                    return $user;
                }

                return null;
            }
        ];

        /**
         * avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
         * also avoid for public actions like registration and password reset
         */
        $behaviors['authenticator']['except'] = [
            'options',            
            'update-password',
            'request-reset-password'
        ];

        return $behaviors;
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();

        //Return Header explaining what options are available for next request
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            //optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }


    /**
     * Perform validation on the staff account (check if he's allowed login to platform)
     * If everything is alright,
     * Returns the BEARER access token required for futher requests to the API
     * @return array
     */
    public function actionLogin()
    {
        $user = Yii::$app->user->identity;

        return $this->_loginResponse($user);
    }

    /**
     * Sends password reset email to user
     * @return array
     */
    public function actionRequestResetPassword()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $model = new \inspector\models\PasswordResetRequestForm();
        $model->email = $emailInput;

        if ($model->validate()) {

            $inspector = Inspector::findOne([
                'inspector_email' => $model->email,
            ]);

            $inspector->sendPasswordResetEmail();

        } else {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Reset password token sent on your email address.'
        ];
    }
    
    /**
     * Updates password based on passed token
     * @return array
     */
    public function actionUpdatePassword()
    {
        $token = Yii::$app->request->getBodyParam("token");
        $newPassword = Yii::$app->request->getBodyParam("newPassword");

        $model =  Inspector::findByPasswordResetToken($token);

        if(!$model) {
            return [
                'operation' => 'error',
                'message' => 'Invalid password reset token. Please request another password reset email'
            ];
        }

        if(!$newPassword) {
            return [
                'operation' => 'error',
                'message' => 'Password field required'
            ];
        }

        $model->scenario = 'updatePassword';

        $model->setPassword($newPassword);

        $model->removePasswordResetToken();

        if(!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Your password has been reset',
            "accessToken" => $this->_loginResponse($model)
        ];
    }

    private function _loginResponse($user) {

        $accessToken = $user->accessToken->token_value;

        return [
            "operation" => "success",
            "token" => $accessToken,
            "inspector_uuid" => $user->inspector_uuid,
            "name" => $user->inspector_name,
            "email" => $user->inspector_email
        ];
    }
}
