<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use candidate\models\Candidate;
use yii\filters\Cors;


/**
 * Auth controller provides the initial access token that is required for further requests
 * It initially authorizes via Http Basic Auth using a base64 encoded username and password
 */
class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        // Basic Auth accepts Base64 encoded username/password and decodes it for you
        $behaviors['authenticator'] = [
            'class' => HttpBasicAuth::className(),
            'except' => ['options'],
            'auth' => function ($email, $password) {
                $candidate = Candidate::findByEmail($email);
                if ($candidate && $candidate->validatePassword($password)) {
                    return $candidate;
                }

                return null;
            }
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        // also avoid for public actions like registration and password reset
        $behaviors['authenticator']['except'] = [
            'options',
            'update-password',
            'email-check',
            'signup'
        ];

        return $behaviors;
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();

        // Return Header explaining what options are available for next request
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }


    /**
     * Perform validation on the candidate account (check if he's allowed login to platform)
     * If everything is alright,
     * Returns the BEARER access token required for futher requests to the API
     * @return array
     */
    public function actionLogin()
    {
        $candidate = Yii::$app->user->identity;

        // Email and password are correct, check if his email has been verified
        // If candidate email has been verified, then allow him to log in
        /*if($candidate->candidate_email_verified != Candidate::EMAIL_VERIFIED){
            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => "Please click the verification link sent to you by email to activate your account",
            ];
        }*/

        // Return candidate access token if everything valid
        $accessToken = $candidate->accessToken->token_value;

        return [
            "operation" => "success",
            "token" => $accessToken,
            "candidateId" => $candidate->candidate_id,
            "name" => $candidate->candidate_name,
            "email" => $candidate->candidate_email
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

        $candidate =  Candidate::findByPasswordResetToken($token);

        if(!$candidate){
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

        $candidate->setPassword($newPassword);
        $candidate->removePasswordResetToken();
        $candidate->save(false);

        return [
            'operation' => 'success',
            'message' => 'Your password has been reset'
        ];
    }


    /**
     * Mobile Check
     * @return User|null
     */
    public function actionEmailCheck() {

        $email = Yii::$app->request->getBodyParam('email');

        $model = DynamicModel::validateData(['email' => $email], [
            [['email'], 'email'],
        ]);

        if ($model->hasErrors()) {
            return self::response('error',$model->errors, 0);
        } else {
            $candidate = Candidate::findByEmail($email);
            if ($candidate) {
                return self::response('success',$candidate, 0);
            } else  {
                return self::response('success',false, 0);
            }

        }
    }

    /**
     * Signup by candidate, only firstname, lastname, email and password needed
     * @return array
     */
    public function actionSignup() {
        $model = new Candidate();
        $model->scenario = "signup";

        $firstname = ucfirst(Yii::$app->request->getBodyParam('name'));

        $model->candidate_name = $firstname;
        $model->candidate_name_ar = $firstname;
        $model->candidate_email = Yii::$app->request->getBodyParam('email');
        $model->candidate_phone = Yii::$app->request->getBodyParam('phone');
        $model->candidate_password_hash = Yii::$app->request->getBodyParam('password');
        $model->candidate_status = \common\models\Candidate::STATUS_PENDING;

        if (!$model->signup()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => Yii::t('job', "We've faced a problem creating your account, please contact us for assistance.")
                ];
            }
        }
//        $this->sendVerificationEmail();
        return [
            "operation" => "success",
            "candidate_uuid" => $model->candidate_id,
            "message" => Yii::t('app', "Please click on the link sent to you by email to verify your account"),
        ];
    }

    /**
     * @param $type
     * @param $msg
     * @param int $translate
     * @return array
     */
    public static function response($type, $msg, $translate = 1) {
        return [
            'operation' => $type,
            'message' => ($translate) ? Yii::t('user', $msg) : $msg
        ];
    }
}
