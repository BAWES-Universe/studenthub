<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;

use common\models\Agent;

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

        // Basic Auth accepts Base64 encoded username/password and decodes it for you
        $behaviors['authenticator'] = [
            'class' => HttpBasicAuth::className(),
            'except' => ['options'],
            'auth' => function ($email, $password) {
                $agent = Agent::findByEmail($email);
                if ($agent && $agent->validatePassword($password)) {
                    return $agent;
                }

                return null;
            }
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        // also avoid for public actions like registration and password reset
        $behaviors['authenticator']['except'] = [
            'options',
            'verify-email',
            'validate',
            'update-password',
            'create-account',
            'request-reset-password',
            'resend-verification-email'
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
     * Perform validation on the agent account (check if he's allowed login to platform)
     * If everything is alright,
     * Returns the BEARER access token required for futher requests to the API
     * @return array
     */
    public function actionLogin()
    {
        $agent = Yii::$app->user->identity;

        // Email and password are correct, check if his email has been verified
        // If agent email has been verified, then allow him to log in
        if($agent->agent_email_verified != Agent::EMAIL_VERIFIED){
            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => "Please click the verification link sent to you by email to activate your account",
            ];
        }

        // Return agent access token if everything valid
        $accessToken = $agent->accessToken->token_value;
        return [
            "operation" => "success",
            "token" => $accessToken,
            "agentId" => $agent->agent_id,
            "name" => $agent->agent_name,
            "email" => $agent->agent_email
        ];
    }

    /**
     * Creates new agent account manually
     * @return array
     */
    public function actionCreateAccount()
    {
        $model = new \common\models\Agent();
        $model->scenario = "manualSignup";

        $model->agent_name = Yii::$app->request->getBodyParam("fullname");
        $model->agent_email = Yii::$app->request->getBodyParam("email");
        $model->agent_password_hash = Yii::$app->request->getBodyParam("password");

        if (!$model->signup())
        {
            if(isset($model->errors['agent_email'])){
                return [
                    "operation" => "error",
                    "message" => $model->errors['agent_email']
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating your account, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Please click on the link sent to you by email to verify your account"
        ];
    }

    /**
     * Re-send manual verification email to agent
     * @return array
     */
    public function actionResendVerificationEmail()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $agent = Agent::findOne([
            'agent_email' => $emailInput,
        ]);

        $errors = false;

        if ($agent) {
            //Check if this user sent an email in past few minutes (to limit email spam)
            $emailLimitDatetime = new \DateTime($agent->agent_limit_email);
            date_add($emailLimitDatetime, date_interval_create_from_date_string('2 minutes'));
            $currentDatetime = new \DateTime();

            if ($currentDatetime < $emailLimitDatetime) {
                $difference = $currentDatetime->diff($emailLimitDatetime);
                $minuteDifference = (int) $difference->i;
                $secondDifference = (int) $difference->s;

                $errors = Yii::t('app', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                            'numMinutes' => $minuteDifference,
                            'numSeconds' => $secondDifference,
                ]);
            } else if ($agent->agent_email_verified == Agent::EMAIL_NOT_VERIFIED) {
                $agent->sendVerificationEmail();
            }
        }

        // If errors exist show them
        if($errors){
            return [
                'operation' => 'error',
                'message' => $errors
            ];
        }

        // Otherwise return success
        return [
            'operation' => 'success',
            'message' => Yii::t('register', 'Please click on the link sent to you by email to verify your account')
        ];
    }

    /**
     * Process email verification
     * @return array
     */
    public function actionVerifyEmail()
    {
        $code = Yii::$app->request->getBodyParam("code");
        $verify = Yii::$app->request->getBodyParam("verify");

        //Code is his auth key, check if code is valid
        $agent = Agent::findOne(['agent_auth_key' => $code, 'agent_id' => (int) $verify]);
        if ($agent) {
            //If not verified
            if ($agent->agent_email_verified == Agent::EMAIL_NOT_VERIFIED) {
                //Verify this agents  email
                $agent->agent_email_verified = Agent::EMAIL_VERIFIED;
                $agent->save(false);
            }

            return [
                'operation' => 'success',
                'message' => 'You have verified your email'
            ];
        }

        //inserted code is invalid
        return [
            'operation' => 'error',
            'message' => 'Invalid email verification code. Account might already be activated. Please try to login again.'
        ];
    }

    /**
     * Sends password reset email to user
     * @return array
     */
    public function actionRequestResetPassword()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $model = new \api\models\PasswordResetRequestForm();
        $model->email = $emailInput;

        $errors = false;

        if ($model->validate()){

            $agent = Agent::findOne([
                'agent_email' => $model->email,
            ]);

            if ($agent) {
                //Check if this user sent an email in past few minutes (to limit email spam)
                $emailLimitDatetime = new \DateTime($agent->agent_limit_email);
                date_add($emailLimitDatetime, date_interval_create_from_date_string('2 minutes'));
                $currentDatetime = new \DateTime();

                if ($currentDatetime < $emailLimitDatetime) {
                    $difference = $currentDatetime->diff($emailLimitDatetime);
                    $minuteDifference = (int) $difference->i;
                    $secondDifference = (int) $difference->s;

                    $errors = Yii::t('app', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                                'numMinutes' => $minuteDifference,
                                'numSeconds' => $secondDifference,
                    ]);

                } else if (!$model->sendEmail($agent)) {
                    $errors = Yii::t('agent', 'Sorry, we are unable to reset password for email provided.');
                }
            }
        }else if(isset($model->errors['email'])){
            $errors = $model->errors['email'];
        }

        // If errors exist show them
        if($errors){
            return [
                'operation' => 'error',
                'message' => $errors
            ];
        }

        // Otherwise return success
        return [
            'operation' => 'success',
            'message' => Yii::t('agent', 'Password reset link sent, please check your email for further instructions.')
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

        $agent =  Agent::findByPasswordResetToken($token);
        if(!$agent || !$newPassword){
            return [
                'operation' => 'error',
                'message' => 'Invalid password reset token. Please request another password reset email.'
            ];
        }

        $agent->setPassword($newPassword);
        $agent->removePasswordResetToken();
        $agent->save(false);

        return [
            'operation' => 'success',
            'message' => 'Your password has been reset.'
        ];
    }


    /**
     * Validate Google auth id_token sent from mobile
     * after a successful google login
     * @return array
     */
    public function actionValidate()
    {
        $idToken = Yii::$app->request->getBodyParam("id_token");
        $displayName = Yii::$app->request->getBodyParam("displayName");

        // Android and Web Auth Client ID
        $clientId1 = "882152609344-ahm24v4mttplse2ahf35ffe4g0r6noso.apps.googleusercontent.com";
        // iOS Auth Client ID
        $clientId2 = "882152609344-thtlv6jpmuc2ugrmnnfe3g1rb0ba5ess.apps.googleusercontent.com";

        $clientRegular = new \Google_Client(['client_id' => $clientId1]);
        $payload = $clientRegular->verifyIdToken($idToken);
        if(!$payload){
            $clientApple =  new \Google_Client(['client_id' => $clientId2]);
            $payload = $clientApple->verifyIdToken($idToken);
        }

        if ($payload)
        {
            $email = $payload['email'];
            $displayName = $displayName?$displayName:$email;
            $fullname = isset($payload['name'])?$payload['name']:$displayName;

            $existingAgent = Agent::find()->where(['agent_email' => $email])->one();
            if ($existingAgent) {
                //There's already an agent with this email, update his details
                $existingAgent->agent_name = $fullname;
                $existingAgent->agent_email_verified = Agent::EMAIL_VERIFIED;
                $existingAgent->generatePasswordResetToken();

                // On Save, Log him in / Send Access Token
                if ($existingAgent->save()) {
                    Yii::info("[Agent Login Google Native] ".$existingAgent->agent_email, __METHOD__);

                    $accessToken = $existingAgent->accessToken->token_value;
                    return [
                        "operation" => 'success',
                        "token" => $accessToken,
                        "agentId" => $existingAgent->agent_id,
                        "name" => $existingAgent->agent_name,
                        "email" => $existingAgent->agent_email
                    ];
                }

                // If Unable to Update
                return [
                    'operation' => 'error',
                    'message' => 'Unable to update your account. Please contact us for assistance.'
                ];
            } else {
                //Agent Doesn't have an account, create one for him
                $agent = new Agent([
                    'agent_name' => $fullname,
                    'agent_email' => $email,
                    'agent_email_verified' => Agent::EMAIL_VERIFIED,
                    'agent_limit_email' => new Expression('NOW()')
                ]);
                $agent->setPassword(Yii::$app->security->generateRandomString(6));
                $agent->generateAuthKey();
                $agent->generatePasswordResetToken();

                if ($agent->save()) {
                    //Log agent signup
                    Yii::info("[New Agent Signup Google Native] ".$agent->agent_email, __METHOD__);
                    // Log him in / Send Access Token
                    $accessToken = $agent->accessToken->token_value;
                    return [
                        "operation" => 'success',
                        "token" => $accessToken,
                        "agentId" => $agent->agent_id,
                        "name" => $agent->agent_name,
                        "email" => $agent->agent_email
                    ];
                }

                return [
                    'operation' => 'error',
                    'message' => 'Unable to create your account. Please contact us for assistance.'
                ];
            }
        }

        // Default Error
        return [
            'operation' => 'error',
            'message' => 'Invalid ID token. Please contact us if this issue persists.'
        ];
    }
}
