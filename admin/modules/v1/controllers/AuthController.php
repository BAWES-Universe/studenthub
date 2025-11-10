<?php

namespace admin\modules\v1\controllers;

use admin\models\AdminToken;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\Cors;
use admin\models\Admin;
use yii\web\UnauthorizedHttpException;


/**
 * Auth controller provides the initial access token that is required for further requests
 * It initially authorizes via Http Basic Auth using a base64 encoded username and password
 * 
 * @OA\Info(
 *     title="StudentHub Admin API",
 *     version="1.0.0",
 *     description="API for admin management and authentication"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="basicAuth",
 *     type="http",
 *     scheme="basic",
 *     description="HTTP Basic Authentication using email and password"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Bearer token authentication for authenticated requests"
 * )
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
            'class' => Cors::class,
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
            
                $admin = Admin::findByEmail($email);
                
                if ($admin && !empty($password) && $admin->validatePassword($password)) {
                    return $admin;
                }

                return null;
            }
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        // also avoid for public actions like registration and password reset
        $behaviors['authenticator']['except'] = [
            'options',
            'login-two-step',
            'login-auth0',
            "login-by-google"
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
     * Two step authentication with OTP
     * 
     * @OA\Post(
     *     path="/auth/login-two-step",
     *     summary="Two-step authentication",
     *     description="Complete two-step authentication using token and OTP",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"token", "otp"},
     *             @OA\Property(property="token", type="string", description="Temporary token from initial login"),
     *             @OA\Property(property="otp", type="string", description="One-time password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="token", type="string", description="Bearer token"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token or OTP"
     *     )
     * )
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

        $admin = Admin::findIdentityByAccessToken(
            $token,
            HttpBearerAuth::class,
            \common\models\AdminToken::STATUS_INACTIVE,
            $otp);

        if (!$admin) {
            throw new UnauthorizedHttpException("Invalid token or OTP");
        }

        //passing token as want to keep same token as of token generated while login

        $adminToken = AdminToken::find()
            ->andWhere(['token_value' => $token])
            ->one();

        return $this->_loginResponse($admin, $adminToken);
    }

    /**
     * Admin login with Basic Auth
     * Perform validation on the admin account (check if he's allowed login to platform)
     * If everything is alright, returns the BEARER access token required for further requests to the API
     * 
     * @OA\Get(
     *     path="/auth/login",
     *     summary="Admin login",
     *     description="Login using HTTP Basic Authentication. Send email and password as Basic Auth credentials. Returns bearer token for authenticated requests.",
     *     tags={"Authentication"},
     *     security={{"basicAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="token", type="string", description="Bearer token"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="admin_limited_access", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
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

        $admin = Yii::$app->user->identity;

        return $this->_loginResponse($admin);
    }

    /**
     * Sign up with google login
     * 
     * @OA\Post(
     *     path="/auth/login-by-google",
     *     summary="Login with Google",
     *     description="Authenticate using Google ID token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"idToken"},
     *             @OA\Property(property="idToken", type="string", description="Google ID token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="token", type="string", description="Bearer token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or account not found"
     *     )
     * )
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
                'message' => 'Invalid Token'
            ];
        }

        $model = Admin::find()
            ->andWhere(['admin_email' => $response->email])
            ->one();

        if (!$model) {
            return [
                "operation" => "error",
                "code" => 2,
                "message" => "No account found with provided email, please contact us for assistance."
            ];
        }

        return $this->_loginResponse($model);
    }

    /**
     * Login with Auth0 token
     * 
     * @OA\Post(
     *     path="/auth/login-auth0",
     *     summary="Login with Auth0",
     *     description="Authenticate using Auth0 access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"accessToken"},
     *             @OA\Property(property="accessToken", type="string", description="Auth0 access token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="token", type="string", description="Bearer token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or account not found"
     *     )
     * )
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

        $user = Admin::find()
            ->andWhere(['admin_email' => $userInfo['email']])
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
     * @param $admin
     * @param $accessToken
     * @return array
     */
    private function _loginResponse($admin, $accessToken = null) {

        // Return Admin access token if everything valid
        if (!$accessToken) {
            // Two-step auth disabled - always use STATUS_ACTIVE
            // $accessToken = $admin->getAccessToken(
            //     $admin->enable_two_step_auth ? AdminToken::STATUS_INACTIVE: AdminToken::STATUS_ACTIVE
            // );
            $accessToken = $admin->getAccessToken(AdminToken::STATUS_ACTIVE);
        }

        return [
            "operation" => "success",
            "token" => $accessToken->token_value,
            "total_attempt"=> $accessToken->total_attempt,
            "token_status" => $accessToken->token_status,//if in-active show 2-step auth page in front
            "id" => $admin->admin_id,
            "name" => $admin->admin_name,
            "email" => $admin->admin_email,
            "admin_limited_access" => $admin->admin_limited_access
        ];
    }
}
