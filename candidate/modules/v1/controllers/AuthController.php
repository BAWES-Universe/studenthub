<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\filters\Cors;
use yii\base\DynamicModel;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use candidate\models\Candidate;
use candidate\models\CandidateToken;
use candidate\models\CandidateEmailVerifyAttempt;
use yii\web\NotFoundHttpException;


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
            'signup',
            'request-reset-password',
            'update-email',
            'resend-verification-email',
            'login-by-apple',
            'login-by-google',
            'verify-email',
            'is-email-verified',
            'name-by-civil-id',
            'login-auth0'
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
        if($candidate->candidate_email_verification != Candidate::EMAIL_VERIFIED) {
            
            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => Yii::t('candidate',"Please click the verification link sent to you by email to activate your account"),
                "unVerifiedToken" => $this->_loginResponse($candidate)
            ];
        }

        // Return candidate access token if everything valid

        return $this->_loginResponse($candidate);
    }

    /**
     * login with auth0 token
     * @return array
     */
    public function actionLoginAuth0()
    {
        $lang = Yii::$app->request->headers->get('language');

        $accessToken = Yii::$app->request->getBodyParam('accessToken');

        $response = Yii::$app->auth0->getUserInfo($accessToken);

        if(!$response->isOk) {
            return self::response('error',"Invalid access token");
        }

        $userInfo = $response->data;

        if(!$userInfo || !$userInfo['email'])
        {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "We've faced a problem creating your account, please contact us for assistance."),
            ];
        }

        $candidate = Candidate::find()
            ->andWhere(['candidate_email' => $userInfo['email']])
            ->one();

        if(!$candidate)
        {
            $candidate = new Candidate();

            $candidate->scenario = "signupAuth0";

            if ($lang == 'ar') {
                $candidate->candidate_name_ar = $userInfo['name'];
                $candidate->candidate_name = null;
            } else  {
                $candidate->candidate_name = $userInfo['name'];
                $candidate->candidate_name_ar = null;
            }

            $candidate->candidate_email = $userInfo['email'];
            $candidate->candidate_phone = isset($userInfo['phone_number'])? $userInfo['phone_number']: '';
            $candidate->candidate_status = \candidate\models\Candidate::STATUS_ACTIVE;
            $candidate->approved = true;
            
            if (!$candidate->signup()) {
                if (isset($candidate->errors)) {
                    return [
                        "operation" => "error",
                        "message" => $candidate->errors,
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => Yii::t('candidate', "We've faced a problem creating your account, please contact us for assistance.")
                    ];
                }
            }
        }

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        /*if ($candidate->contact_email_verification != Candidate::EMAIL_VERIFIED) {

            //$candidate->generateOtp();
            //$candidate->save(false);

            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => Yii::t('candidate', "Please click the verification link sent to you by email to activate your account"),
                "unVerifiedToken" => $this->_loginResponse($candidate)
            ];
        }*/

        return $this->_loginResponse($candidate);
    }

    /**
     * Check if candidate email already verified 
     */
    public function actionIsEmailVerified() {
        
        $token = Yii::$app->request->getBodyParam("token");

        $model = CandidateToken::find()
                ->andWhere(['token_value' => $token])
                ->one();

        if (!$model || !$model->candidate) {
            return [
                'status' => 0
            ];
        }

        return [
            'status' => $model->candidate->candidate_new_email ? 0 : $model->candidate->candidate_email_verification
        ];
    }

    /**
     * Update candidate email address
     * @return type
     */
    public function actionUpdateEmail() {
        $unVerifiedToken = Yii::$app->request->getBodyParam("unVerifiedToken");
        $new_email = Yii::$app->request->getBodyParam("newEmail");

        $candidate = Candidate::findIdentityByUnVerifiedTokenToken($unVerifiedToken);

        if (!$candidate) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Candidate new email address required")
            ];
        }

        if ($new_email == $candidate->candidate_email || $new_email == $candidate->candidate_new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Candidate new email address is same as old email")
            ];
        }

        /**
         * Opt will expiry after 60 minutes, so user have to login back to update 
         * email 
         *
        if (!$candidate->findByOtp($candidate->otp, 60)) {
            return [
                "operation" => "error-session-expired",
                "message" => Yii::t('employer', "Session expired, please log back in")
            ];
        }*/

        $candidate->scenario = "updateEmail";

        if ($candidate->candidate_status == Candidate::STATUS_PENDING) {
            $candidate->candidate_email = $new_email;
            $candidate->candidate_new_email = null;
        } else  {
            $candidate->candidate_new_email = $new_email;
        }

        if ($candidate->save()) {

            //extend otp to fix: https://www.pivotaltracker.com/story/show/169037267

            //$candidate->generateOtp();

            //to verify new email address 

            $candidate->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('candidate', "Candidate Account Info Updated Successfully, please check email to verify new email address"),
                "unVerifiedToken" => $this->_loginResponse($candidate)
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }
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
        $errorCode = null; //error code

        if ($candidate) {

            if ($candidate->candidate_email_verification == Candidate::EMAIL_VERIFIED) {
                return [
                    'operation' => 'error',
                    'errorCode' => 1,
                    'message' => Yii::t('candidate', 'You have verified your email')
                ];
            }

            //Check if this user sent an email in past few minutes (to limit email spam)
            $emailLimitDatetime = new \DateTime($candidate->candidate_limit_email);
            date_add($emailLimitDatetime, date_interval_create_from_date_string('1 minutes'));
            $currentDatetime = new \DateTime();

            if ($candidate->candidate_limit_email && $currentDatetime < $emailLimitDatetime) {
                $difference = $currentDatetime->diff($emailLimitDatetime);
                $minuteDifference = (int) $difference->i;
                $secondDifference = (int) $difference->s;

                $errorCode = 2;

                $errors = Yii::t('candidate', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                            'numMinutes' => $minuteDifference,
                            'numSeconds' => $secondDifference,
                ]);
            } else if ($candidate->candidate_email_verification == Candidate::EMAIL_NOT_VERIFIED) {
                $candidate->sendVerificationEmail();
            }
        } else {
            $errorCode = 3;
            $errors['email'] = [Yii::t('candidate', 'Candidate Account not found')];
        }

        // If errors exist show them

        if ($errors) {
            return [
                'errorCode' => $errorCode,
                'operation' => 'error',
                'message' => $errors
            ];
        }

        // Otherwise return success
        return [
            'operation' => 'success',
            'message' => Yii::t('candidate', 'Please click on the link sent to you by email to verify your account'),
        ];
    }

    /**
     * Process email verification
     * @return array
     */
    public function actionVerifyEmail() {
        
        $code = Yii::$app->request->getBodyParam("code");
        $email = Yii::$app->request->getBodyParam("email");

        //check limit reached

        $totalInvalidAttempts = CandidateEmailVerifyAttempt::find()
                ->andWhere([
                    'candidate_email' => $email,
                    'ip_address' => Yii::$app->getRequest()->getUserIP()
                ])
                ->andWhere(new \yii\db\Expression("created_at >= DATE_SUB(NOW(),INTERVAL 1 HOUR)"))//last 1 hour 
                ->count();

        if ($totalInvalidAttempts > 4) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'You reached your limit to verify email. Please try again after an hour.')
            ];
        }

        $candidate = Candidate::verifyEmail($email, $code);

        if ($candidate['success'] == false) {
            return [
                'operation' => 'error',
                'message' => $candidate['message']
            ];
        }

        if ($candidate['success'] == true) {
            //remove old email verification attempts

            CandidateEmailVerifyAttempt::deleteAll([
                'candidate_email' => $email,
                'ip_address' => Yii::$app->getRequest()->getUserIP()
            ]);

            //remove otp

            //$candidate->otp = null;
            //$candidate->save(false);

            return $this->_loginResponse($candidate['data']);
        } else {
            //add entry for invalid attempt

            $model = new CandidateEmailVerifyAttempt;
            $model->code = $code;
            $model->candidate_email = $email;
            $model->ip_address = Yii::$app->getRequest()->getUserIP();
            $model->save();

            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Invalid email verification code.')
            ];
        }
    }
    
    /**
     * Sends password reset email to user
     * @return array
     */
    public function actionRequestResetPassword() {

        $emailInput = Yii::$app->request->getBodyParam("email");

        $model = new \candidate\models\PasswordResetRequestForm();
        $model->email = $emailInput;
        
        $errors = null;

        if (!$model->validate()) {
            return [
                'operation' => 'error',
                'message' => isset($model->errors['candidate_email'])?isset($model->errors['candidate_email']): $model->errors
            ];
        }

        $candidate = Candidate::findOne([
            'candidate_email' => $model->email,
        ]);

        if (!$candidate) {
            return [
                'operation' => 'error',
                'message' => 'candidate not found'
            ];
        }

        //Check if this user sent an email in past few minutes (to limit email spam)
        $emailLimitDatetime = new \DateTime($candidate->candidate_limit_email);
        date_add($emailLimitDatetime, date_interval_create_from_date_string('1 minutes'));
        $currentDatetime = new \DateTime('now');

        if ($candidate->candidate_limit_email && $currentDatetime < $emailLimitDatetime) {
            $difference = $currentDatetime->diff($emailLimitDatetime);
            $minuteDifference = (int) $difference->i;
            $secondDifference = (int) $difference->s;

            $errors = Yii::t('candidate', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                        'numMinutes' => $minuteDifference,
                        'numSeconds' => $secondDifference,
            ]);
        } else if (!$candidate->sendPasswordResetEmail()) {
            $errors = Yii::t('candidate', 'Sorry, we are unable to reset a password for email provided.');
        }

        if($errors) {
            return [
                'operation' => 'error',
                'message' => $errors
            ];
        }
        
        Yii::info("[Student Password Reset Request] by Candidate, Candidate Email: ".$candidate->candidate_email, __METHOD__);
        
        // Otherwise return success
        return [
            'operation' => 'success',
            'message' => Yii::t('candidate', 'Please check the link sent to you on your email to set new password.')
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

        if(!$candidate) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate','Invalid password reset token. Please request another password reset email')
            ];
        }

        if(!$newPassword) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate','Password field required')
            ];
        }

        $candidate->scenario = 'changePassword';
        
        $candidate->setPassword($newPassword);
        
        $candidate->removePasswordResetToken();
        
        /**
         * as password reset token will be sent to email and user will update password
         * from that link so if user have token he have valid email
         */
        
        $candidate->candidate_email_verification = Candidate::EMAIL_VERIFIED;

        if (!$candidate->save()) {
            return [
                "operation" => "error",
                "message" => $candidate->getErrors()
            ];
        }
        
        return [
            "operation" => "success",
            'message' => Yii::t('candidate','Your password has been reset'),
            "accessToken" => $this->_loginResponse($candidate)
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
        $lang = Yii::$app->request->getBodyParam('lang');

        if (!$firstname) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Name is required")
            ];
        }

        if ($lang == 'ar') {
            $model->candidate_name_ar = $firstname;
            $model->candidate_name = null;
        } else  {
            $model->candidate_name = $firstname;
            $model->candidate_name_ar = null;
        }

        $model->candidate_email = Yii::$app->request->getBodyParam('email');
        $model->candidate_phone = Yii::$app->request->getBodyParam('phone');
        $model->candidate_language_pref = $lang;
        $model->candidate_password_hash = Yii::$app->request->getBodyParam('password');
        $model->candidate_status = \candidate\models\Candidate::STATUS_PENDING;
        $model->approved = false;


        if (!$model->signup()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => Yii::t('candidate', "We've faced a problem creating your account, please contact us for assistance.")
                ];
            }
        }

        if (YII_ENV == 'prod') {
            $param = [
                'email' => Yii::$app->request->getBodyParam('email'),
                'password' => Yii::$app->request->getBodyParam('password'),
                'name' => $firstname,
                'nickname' => $firstname,
                'user_metadata' => ['app' => 'SH-candidate', 'user_id' => $model->candidate_id]
            ];
            Yii::$app->auth0->createUser($param);
        }

        return [
            "operation" => "success",
            "candidate_uuid" => $model->candidate_id,
            "message" => Yii::t('app', "Please click on the link sent to you by email to verify your account"),
            "unVerifiedToken" => $this->_loginResponse($model)
        ];
    }

    /**
     * Return candidate data after successful login
     * @param type $candidate
     * @return type
     */
    private function _loginResponse($candidate) {

        // Return Candidate access token if everything valid

        $accessToken = $candidate->accessToken->token_value;

        return [
            "operation" => "success",
            "token" => $accessToken,
            "id" => $candidate->candidate_id,
            "name" => $candidate->candidate_name,
            "email" => $candidate->candidate_email,
            "language_pref" => $candidate->candidate_language_pref,
            "approved" => $candidate->approved,
            "isProfileCompleted" => $candidate->isProfileCompleted(),
            "pending" => ($candidate->pendingProfile) ? array_keys($candidate->pendingProfile) : null
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

    /**
     * Sign up with google login
     */
    public function actionLoginByGoogle() {
        $token = Yii::$app->request->getBodyParam("idToken");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));

        if (empty($response->email)) {
            return [
                'operation' => 'error',
                "code" => 1,
                'message' => Yii::t('job', 'Invalid Token')
            ];
        }

        $model = Candidate::find()->where([
            'candidate_email' => $response->email
        ])->one();

        if (!$model) {

            $model = new Candidate;
            $model->scenario = "signupGoogle";

            $candidate_name = $response->given_name;

            if(isset($response->family_name))
                $candidate_name .= ' ' .$response->family_name;

            $data = [
                'candidate_email' => $response->email,
                'candidate_name' => $candidate_name ,
                //'candidate_name_ar' => $candidate_name,
                'candidate_email_verification' => Candidate::EMAIL_VERIFIED,
                'candidate_status' => Candidate::STATUS_ACTIVE,
                'approved' => 1
            ];

            $model->setAttributes($data);

            if(isset($response->picture)) {
                $model->setProfileByUrl(str_replace('s96', 's250', $response->picture));
            }

            if (!$model->signup(false)) {
                if (isset($model->errors)) {
                    return [
                        "operation" => "error",
                        "code" => 2,
                        "message" => $model->errors,
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "code" => 3,
                        "message" => Yii::t('job', "We've faced a problem creating your account, please contact us for assistance."),
                    ];
                }
            }
        }

        return $this->_loginResponse($model);
    }

    /**
     *
     * Sign up with apple login
     */
    public function actionLoginByApple() {

        try {

            $jwt = Yii::$app->request->getBodyParam("identityToken");

            //will throw error on invalid token

            $payload = Yii::$app->jwt->decode($jwt);

        } catch(\ErrorException $e) {

            return [
                'operation' => 'error',
                'message' => $e->getMessage()
            ];

        }

        if(empty($payload->email)) {
            return [
                'operation' => 'error',
                'message' => Yii::t('job', 'Invalid Token')
            ];
        }

        $email = $payload->email;

        $familyName = Yii::$app->request->getBodyParam("familyName");
        $givenName = Yii::$app->request->getBodyParam("givenName");

        $candidate = Candidate::find()
            ->andWhere(['candidate_email' => $email])
            ->one();

        if (!$candidate) {
            $candidate = new Candidate();

            $candidate->scenario = "signupAuth0";
            $candidate->candidate_name = $givenName." ".$familyName;
            $candidate->candidate_name_ar = null;

            $candidate->candidate_email = $email;
            $candidate->candidate_status = \candidate\models\Candidate::STATUS_ACTIVE;
            $candidate->approved = 1;

            if (!$candidate->signup()) {
                if (isset($candidate->errors)) {
                    return [
                        "operation" => "error",
                        "message" => $candidate->errors,
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => Yii::t('candidate', "We've faced a problem creating your account, please contact us for assistance.")
                    ];
                }
            }
        }

        return $this->_loginResponse($candidate);
    }
}
