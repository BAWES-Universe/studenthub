<?php

namespace staff\modules\v1\controllers;

use candidate\models\Candidate;
use inspector\models\Inspector;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use staff\models\Staff;
use common\models\StaffToken;
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

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
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

        // Basic Auth accepts Base64 encoded username/password and decodes it for you
        $behaviors['authenticator'] = [
            'class' => HttpBasicAuth::class,
            'except' => ['options'],
            'auth' => function ($email, $password) {
            
                $staff = Staff::findByEmail($email);
              
                if ($staff && !empty($password) && $staff->validatePassword($password)) {
                    return $staff;
                }

                return null;
            }
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        // also avoid for public actions like registration and password reset
        $behaviors['authenticator']['except'] = [
            'options',            
            'update-password',
            'login-auth0',
            'login-by-key',
            'login-by-google',
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
     * two step auth
     * @return array
     */
    public function actionLoginTwoStep() {

        $token = Yii::$app->request->headers->get("g-recaptcha-response");

        if(YII_ENV != 'test') {

            $response = Yii::$app->reCaptcha->verify($token);

            if (!$response->data || !$response->data['success']) {
                return [
                    "operation" => "error",
                    "code" => 0,
                    "message" => Yii::t('candidate', "Invalid captcha validation")
                ];
            }
        }

        $token = Yii::$app->request->getBodyParam("token");
        $otp = Yii::$app->request->getBodyParam("otp");

        //validate token + OTP

        $staff = Staff::findIdentityByAccessToken(
            $token,
            HttpBearerAuth::class,
            \common\models\StaffToken::STATUS_INACTIVE,
            $otp);

        if (!$staff) {
            throw new UnauthorizedHttpException("Invalid token or OTP");
        }

        //passing token as want to keep same token as of token generated while login

        $staffToken = StaffToken::find()
            ->andWhere(['token_value' => $token])
            ->one();

        return $this->_loginResponse($staff, $staffToken);
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionLoginByKey() {

        $auth_key = Yii::$app->request->getBodyParam('auth_key');

        $user = Staff::find()
            ->andWhere(['staff_auth_key' => $auth_key])
            ->one();

        if(!$user) {
            throw new NotFoundHttpException('The requested page does not exist.');
            /*return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Not found")
            ];*/
        }

        $user->staff_auth_key = "";
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

        if(YII_ENV != 'test') {
            $response = Yii::$app->reCaptcha->verify($token);

            if (!$response->data || !$response->data['success']) {
                return [
                    "operation" => "error",
                    "code" => 0,
                    "message" => Yii::t('candidate', "Invalid captcha validation")
                ];
            }
        }

        $staff = Yii::$app->user->identity;

        return $this->_loginResponse($staff);
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
                'message' => Yii::t('app', 'Invalid Token')
            ];
        }

        $model = Staff::find()->where([
            'staff_email' => $response->email
        ])->one();

        if (!$model) {
            return [
                "operation" => "error",
                "code" => 2,
                "message" => Yii::t('app', "No account found with provided email, please contact us for assistance."),
            ];
        }

        return $this->_loginResponse($model);
    }

    /**
     * login with auth0 token
     * @return array
     */
    public function actionLoginAuth0()
    {
        $accessToken = Yii::$app->request->getBodyParam('accessToken');

        $response = Yii::$app->auth0->getUserInfo($accessToken);

        if(!$response->isOk) {
            return [
                "operation" => "error",
                "message" => "Invalid access token"
            ];
        }

        $userInfo = $response->data;

        if(!$userInfo || !$userInfo['email'])
        {
            return [
                "operation" => "error",
                "message" => "We've faced a problem creating your account, please contact us for assistance.",
            ];
        }

        $user = Staff::find()
            ->andWhere(['staff_email' => $userInfo['email']])
            ->one();

        /**
         * redirect to signup page if no account
         */
        if(!$user)
        {
            return [
                "operation" => "error",
                "code" => 1,
                "message" => "Account not found"
            ];
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

        return $this->_loginResponse($user);
    }

    /**
     * login token details
     * @param $staff
     * @return array
     */
    private function _loginResponse($staff, $accessToken = null) {

        // Return staff access token if everything valid
        if (!$accessToken) {
            $accessToken = $staff->getAccessToken(
                $staff->enable_two_step_auth ? StaffToken::STATUS_INACTIVE: StaffToken::STATUS_ACTIVE
            );
        }

        return [
            "operation" => "success",
            "token" => $accessToken->token_value,
            "total_attempt"=> $accessToken->total_attempt,
            "token_status" => $accessToken->token_status,//if in-active show 2-step auth page in front
            "staff_id" => $staff->staff_id,
            "name" => $staff->staff_name,
            "email" => $staff->staff_email,
            "story" => $staff->currentStory,
            "role" => $staff->staff_role
        ];
    }

    /**
     * Sends password reset email to user
     * @return array
     */
    public function actionRequestResetPassword()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $model = new \staff\models\PasswordResetRequestForm();
        $model->email = $emailInput;

        $errors = false;

        if ($model->validate()) {

            $staff = Staff::findOne([
                'email' => $model->email,
            ]);

            if ($staff && !$model->sendEmail($staff)) {
                $errors = Yii::t('app', 'Sorry, we are unable to reset password for email provided.');
            }

        } else if (isset($model->errors['email'])) {
            $errors = $model->errors['email'];
        }

        // If errors exist show them
        if ($errors) {
            return [
                'operation' => 'error',
                'message' => $errors
            ];
        }

        // Otherwise return success
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

        $staff =  Staff::findByPasswordResetToken($token);

        if(!$staff){
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

        $staff->setPassword($newPassword);
        $staff->removePasswordResetToken();
        $staff->save(false);

        return [
            'operation' => 'success',
            'message' => 'Your password has been reset'
        ];
    }
}
