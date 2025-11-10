<?php

namespace inspector\modules\v1\controllers;

use company\models\Contact;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use inspector\models\Inspector;
use common\models\InspectorToken;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;


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
            'class' => \yii\filters\Cors::class,
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
            'class' => HttpBasicAuth::class,
            'except' => ['options'],
            'auth' => function ($email, $password) {

                $user = Inspector::findByEmail(trim($email));
                
                if ($user && !empty($password) && $user->validatePassword(trim($password))) {
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
            'set-password',       
            'update-password',
            'login-by-key',
            'request-reset-password',
            'login-two-step'
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
     * two step auth
     * @return array
     */
    public function actionLoginTwoStep() {

        $token = Yii::$app->request->headers->get("g-recaptcha-response");

        // if(YII_ENV != 'test') {

        //     $response = Yii::$app->reCaptcha->verify($token);

        //     if (!$response->data || !$response->data['success']) {
        //         return [
        //             "operation" => "error",
        //             "code" => 0,
        //             "message" => Yii::t('candidate', "Invalid captcha validation")
        //         ];
        //     }
        // }

        $token = Yii::$app->request->getBodyParam("token");
        $otp = Yii::$app->request->getBodyParam("otp");

        //validate token + OTP

        $inspector = Inspector::findIdentityByAccessToken(
            $token,
            HttpBearerAuth::class,
            \common\models\InspectorToken::STATUS_INACTIVE,
            $otp);

        if (!$inspector) {
            throw new UnauthorizedHttpException("Invalid token or OTP");
        }

        //passing token as want to keep same token as of token generated while login

        $inspectorToken = InspectorToken::find()
            ->andWhere(['token_value' => $token])
            ->one();

        return $this->_loginResponse($inspector, $inspectorToken);
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionLoginByKey() {

        $auth_key = Yii::$app->request->getBodyParam('auth_key');

        $user = Inspector::find()
            ->andWhere(['inspector_auth_key' => $auth_key])
            ->one();

        if(!$user) {
            throw new NotFoundHttpException('The requested page does not exist.');
            /*return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Not found")
            ];*/
        }

        $user->inspector_auth_key = "";
        $user->save(false);

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        /*if ($user->contact_email_verification != Inspector::EMAIL_VERIFIED) {

            //$contact->generateOtp();
            //$contact->save(false);

            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => Yii::t('company', "Please click the verification link sent to you by email to activate your account"),
                "unVerifiedToken" => $this->_loginResponse($contact)
            ];
        }*/

        //Update last active datetime for candidate
        //$contact->last_active_datetime = (new \yii\db\Query)->select(new \yii\db\Expression('NOW()'))->scalar();
        //$contact->save(false);

        return $this->_loginResponse($user);
    }

    /**
     * Perform validation on the staff account (check if he's allowed login to platform)
     * If everything is alright,
     * Returns the BEARER access token required for futher requests to the API
     * @return array
     */
    public function actionLogin()
    {
        $token = Yii::$app->request->headers->get("g-recaptcha-response");

        // if(YII_ENV != 'test') {
        //     $response = Yii::$app->reCaptcha->verify($token);

        //     if (!$response->data || !$response->data['success']) {
        //         return [
        //             "operation" => "error",
        //             "code" => 0,
        //             "message" => Yii::t('candidate', "Invalid captcha validation")
        //         ];
        //     }
        // }

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

    private function _loginResponse($user, $accessToken = null) {

        // Return candidate access token if everything valid
        if (!$accessToken) {
            // Two-step auth disabled - always use STATUS_ACTIVE
            // $accessToken = $user->getAccessToken(
            //     $user->enable_two_step_auth ? InspectorToken::STATUS_INACTIVE: InspectorToken::STATUS_ACTIVE
            // );
            $accessToken = $user->getAccessToken(InspectorToken::STATUS_ACTIVE);
        }

        return [
            "operation" => "success",
            "token" => $accessToken->token_value,
            "total_attempt"=> $accessToken->total_attempt,
            "token_status" => $accessToken->token_status,//if in-active show 2-step auth page in front
            "inspector_uuid" => $user->inspector_uuid,
            "name" => $user->inspector_name,
            "email" => $user->inspector_email
        ];
    }
}
