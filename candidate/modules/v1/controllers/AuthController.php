<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;

use candidate\models\Candidate;

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
     * Creates new candidate account manually
     * @return array
     */
    public function actionCreateAccount()
    {
        $model = new \common\models\Candidate();
        $model->scenario = "manualSignup";

        $model->candidate_name = Yii::$app->request->getBodyParam("fullname");
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_password_hash = Yii::$app->request->getBodyParam("password");

        if (!$model->signup())
        {
            if(isset($model->errors['candidate_email'])){
                return [
                    "operation" => "error",
                    "message" => $model->errors['candidate_email']
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
     * Re-send manual verification email to candidate
     * @return array
     */
    public function actionResendVerificationEmail()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $candidate = Candidate::findOne([
            'candidate_email' => $emailInput,
        ]);

        $errors = false;

        if ($candidate) {
            //Check if this user sent an email in past few minutes (to limit email spam)
            $emailLimitDatetime = new \DateTime($candidate->candidate_limit_email);
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
            } else if ($candidate->candidate_email_verified == Candidate::EMAIL_NOT_VERIFIED) {
                $candidate->sendVerificationEmail();
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
        $candidate = Candidate::findOne(['candidate_auth_key' => $code, 'candidate_id' => (int) $verify]);
        if ($candidate) {
            //If not verified
            if ($candidate->candidate_email_verified == Candidate::EMAIL_NOT_VERIFIED) {
                //Verify this candidates  email
                $candidate->candidate_email_verified = Candidate::EMAIL_VERIFIED;
                $candidate->save(false);
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

            $candidate = Candidate::findOne([
                'candidate_email' => $model->email,
            ]);

            if ($candidate) {
                //Check if this user sent an email in past few minutes (to limit email spam)
                $emailLimitDatetime = new \DateTime($candidate->candidate_limit_email);
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

                } else if (!$model->sendEmail($candidate)) {
                    $errors = Yii::t('candidate', 'Sorry, we are unable to reset password for email provided.');
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
            'message' => Yii::t('candidate', 'Password reset link sent, please check your email for further instructions.')
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
        if(!$candidate || !$newPassword){
            return [
                'operation' => 'error',
                'message' => 'Invalid password reset token. Please request another password reset email.'
            ];
        }

        $candidate->setPassword($newPassword);
        $candidate->removePasswordResetToken();
        $candidate->save(false);

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

            $existingCandidate = Candidate::find()->where(['candidate_email' => $email])->one();
            if ($existingCandidate) {
                //There's already an candidate with this email, update his details
                $existingCandidate->candidate_name = $fullname;
                $existingCandidate->candidate_email_verified = Candidate::EMAIL_VERIFIED;
                $existingCandidate->generatePasswordResetToken();

                // On Save, Log him in / Send Access Token
                if ($existingCandidate->save()) {
                    Yii::info("[Candidate Login Google Native] ".$existingCandidate->candidate_email, __METHOD__);

                    $accessToken = $existingCandidate->accessToken->token_value;
                    return [
                        "operation" => 'success',
                        "token" => $accessToken,
                        "candidateId" => $existingCandidate->candidate_id,
                        "name" => $existingCandidate->candidate_name,
                        "email" => $existingCandidate->candidate_email
                    ];
                }

                // If Unable to Update
                return [
                    'operation' => 'error',
                    'message' => 'Unable to update your account. Please contact us for assistance.'
                ];
            } else {
                //Candidate Doesn't have an account, create one for him
                $candidate = new Candidate([
                    'candidate_name' => $fullname,
                    'candidate_email' => $email,
                    'candidate_email_verified' => Candidate::EMAIL_VERIFIED,
                    'candidate_limit_email' => new Expression('NOW()')
                ]);
                $candidate->setPassword(Yii::$app->security->generateRandomString(6));
                $candidate->generateAuthKey();
                $candidate->generatePasswordResetToken();

                if ($candidate->save()) {
                    //Log candidate signup
                    Yii::info("[New Candidate Signup Google Native] ".$candidate->candidate_email, __METHOD__);
                    // Log him in / Send Access Token
                    $accessToken = $candidate->accessToken->token_value;
                    return [
                        "operation" => 'success',
                        "token" => $accessToken,
                        "candidateId" => $candidate->candidate_id,
                        "name" => $candidate->candidate_name,
                        "email" => $candidate->candidate_email
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
