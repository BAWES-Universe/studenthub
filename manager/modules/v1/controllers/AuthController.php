<?php

namespace manager\modules\v1\controllers;

use common\models\CompanyRequest;
use common\models\StoreManager;
use common\models\ManagerToken;
//use common\models\StoreManagerEmailVerifyAttempt;
use staff\models\Staff;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\Cors;
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

                $contact = StoreManager::findByEmail($email);

                if ($contact && !empty($password) && $contact->validatePassword($password)) {
                    return $contact;
                }

                return null;
            }
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        // also avoid for public actions like registration and password reset
        $behaviors['authenticator']['except'] = [
            'options',
            'update-password',
            'request-reset-password',
            'create-account',
            'update-email',
            'signup',
            'resend-verification-email',
            'verify-email',
            'is-email-verified',
            'login-auth0',
            'login-by-google',
            'login-by-key',
            "locate"
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
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionLoginByKey() {

        $auth_key = Yii::$app->request->getBodyParam('auth_key');

        $contact = StoreManager::find()
            ->andWhere(['auth_key' => $auth_key])
            ->one();

        if(!$contact) {
            throw new NotFoundHttpException('The requested page does not exist.');
            /*return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Not found")
            ];*/
        }

        $contact->auth_key = "";
        $contact->save(false);

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        /*if ($contact->email_verification != StoreManager::EMAIL_VERIFIED) {

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

        return $this->_loginResponse($contact);
    }

    /**
     * return user location detail by user ip address
     * @return type
     */
    public function actionLocate() {
        return Yii::$app->ipstack->locate();
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

        $contact = StoreManager::find()
            ->andWhere(['email' => $response->email])
            ->one();

        if(!$contact)
        {
            return [
                "operation" => "error",
                "code" => 2,
                "message" => Yii::t('app', "No account found with provided email, please contact us for assistance."),
            ];
            /*
            $transaction = Yii::$app->db->beginTransaction();

            $contact = new StoreManager();
            $contact->setScenario('signupAuth0');

            $contact->name = $userInfo['name'];
            $contact->email = $userInfo['email'];
            $contact->receive_email = true;
            $contact->email_verification = true;
            $contact->utm_uuid = Yii::$app->request->getBodyParam("utm_uuid");

            if (!$contact->signUp(true)) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $contact->errors
                ];
            }

            $company_name = $userInfo['name'] . "'s company";

            $company = new Company();
            $company->company_name = $company_name;
            $company->company_common_name_en = $company_name;
            $company->company_common_name_ar = $company_name;
            $company->company_email = $contact->email;
            $company->company_bonus_commission = 0;
            $company->company_approved_to_hire = false;
            $company->company_followup = true;
            $company->company_followup_interval_weeks = 1;
            $company->company_last_followup_datetime = date('Y-m-d', strtotime ('-7 days'));
            $company->company_status_override = Company::STATUS_UNDER_REVIEW;

            if (!$company->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $company->errors
                ];
            }

            $companyStoreManager = new CompanyStoreManager();
            $companyStoreManager->company_id = $company->company_id;
            $companyStoreManager->uuid = $contact->uuid;
            //$companyStoreManager->position = Yii::$app->request->getBodyParam("position");
            $companyStoreManager->allow_access = true;

            if (!$companyStoreManager->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $companyStoreManager->errors
                ];
            }

            $company->notifyUnderReview();

            $transaction->commit();*/
        }

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        if ($contact->email_verification != StoreManager::EMAIL_VERIFIED) {

            //$contact->generateOtp();
            //$contact->save(false);

            if($response->email_verified && $response->email_verified == "true") {
                $contact->email_verification = StoreManager::EMAIL_VERIFIED;
                $contact->save(false);
            } else {
                return [
                    "data" => $response,
                    "operation" => "error",
                    "errorType" => "email-not-verified",
                    "message" => Yii::t('company', "Please click the verification link sent to you by email to activate your account"),
                    "unVerifiedToken" => $this->_loginResponse($contact)
                ];
            }
        }

        return $this->_loginResponse($contact);
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
                "message" => Yii::t('company', "We've faced a problem creating your account, please contact us for assistance."),
            ];
        }

        $contact = StoreManager::find()
            ->andWhere(['email' => $userInfo['email']])
            ->one();

        if(!$contact)
        {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Account not found, please contact us for assistance."),
            ];
            /*
            $transaction = Yii::$app->db->beginTransaction();

            $contact = new StoreManager();
            $contact->setScenario('signupAuth0');

            $contact->name = $userInfo['name'];
            $contact->email = $userInfo['email'];
            $contact->receive_email = true;
            $contact->email_verification = true;
            $contact->utm_uuid = Yii::$app->request->getBodyParam("utm_uuid");

            if (!$contact->signUp(true)) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $contact->errors
                ];
            }

            $company_name = $userInfo['name'] . "'s company";

            $company = new Company();
            $company->company_name = $company_name;
            $company->company_common_name_en = $company_name;
            $company->company_common_name_ar = $company_name;
            $company->company_email = $contact->email;
            $company->company_bonus_commission = 0;
            $company->company_approved_to_hire = false;
            $company->company_followup = true;
            $company->company_followup_interval_weeks = 1;
            $company->company_last_followup_datetime = date('Y-m-d', strtotime ('-7 days'));
            $company->company_status_override = Company::STATUS_UNDER_REVIEW;

            if (!$company->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $company->errors
                ];
            }

            $companyStoreManager = new CompanyStoreManager();
            $companyStoreManager->company_id = $company->company_id;
            $companyStoreManager->uuid = $contact->uuid;
            //$companyStoreManager->position = Yii::$app->request->getBodyParam("position");
            $companyStoreManager->allow_access = true;

            if (!$companyStoreManager->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $companyStoreManager->errors
                ];
            }

            $company->notifyUnderReview();

            $transaction->commit();*/
        }

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        if ($contact->email_verification != StoreManager::EMAIL_VERIFIED) {

            //$contact->generateOtp();
            //$contact->save(false);

            if(isset($userInfo['email_verified']) && $userInfo['email_verified']) {
                $contact->email_verification = StoreManager::EMAIL_VERIFIED;
                $contact->save(false);
            } else {
                return [
                    "data" => $userInfo,
                    "operation" => "error",
                    "errorType" => "email-not-verified",
                    "message" => Yii::t('company', "Please click the verification link sent to you by email to activate your account"),
                    "unVerifiedToken" => $this->_loginResponse($contact)
                ];
            }
        }

        return $this->_loginResponse($contact);
    }

    /**
     * Perform validation on the company account (check if he's allowed login to platform)
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

        $contact = Yii::$app->user->identity;

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        /*if ($contact->email_verification != StoreManager::EMAIL_VERIFIED) {

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

        return $this->_loginResponse($contact);
    }

    /**
     * Updates password based on passed token
     * @return array
     */
    public function actionUpdatePassword()
    {
        $token = Yii::$app->request->getBodyParam("token");
        $newPassword = Yii::$app->request->getBodyParam("newPassword");

        $model = StoreManager::findByPasswordResetToken($token);

        if(!$model) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Invalid password reset token. Please request another password reset email')
            ];
        }

        if(!$newPassword) {
            return [
                'operation' => 'error',
                'message' => Yii::t("company",'Password field required')
            ];
        }

        $model->setPassword($newPassword);
        $model->removePasswordResetToken();
        $model->save(false);

        return [
            'operation' => 'success',
            'message' => Yii::t("company",'Your password has been reset')
        ];
    }

    /**
     * Sends password reset email to user
     * @return array
     */
    public function actionRequestResetPassword()
    {
        $emailInput = Yii::$app->request->getBodyParam("email");

        $model = new \manager\models\PasswordResetRequestForm();
        $model->email = $emailInput;

        if ($model->validate()) {

            $contact = StoreManager::findOne([
                'email' => $model->email,
            ]);

            $contact->sendPasswordResetEmail();

        } else {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => Yii::t('company','Reset password token sent on your email address.')
        ];
    }

    private function _loginResponse($contact, $company = false) {
        // Return Company access token if everything valid
        $accessToken = $contact->accessToken->token_value;

        if(!$company) {
            $company = $contact->getCompany()->one();
        }

        return [
            "operation" => "success",
            "token" => $accessToken,
            "company_id" => $company? $company->company_id: null,
            "profile_name" => $contact->name,
            "email" => $contact->email,
            "currency_pref" => $company? $company->currency_code: "KWD",
            "active_request_count" => $company? $company->getRequests()->activeRequest()->count() : 0
        ];
    }

    /**
     * Creates new Agent Account
     * @return array
     */
    public function actionCreateAccount() {

        //$invitationOtp = Yii::$app->request->getBodyParam("otp");

        //$transaction = Yii::$app->db->beginTransaction ();

        /*$model = new StoreManager();

        $model->name = ucfirst(Yii::$app->request->getBodyParam("name"));
        $model->email = Yii::$app->request->getBodyParam("email");
        $model->password_hash = Yii::$app->request->getBodyParam("password");
        $model->receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->utm_uuid = Yii::$app->request->getBodyParam("utm_uuid");*/

        //Generate OTP for Candidate
        //$model->generateOTP();

        /*$invitation = StoreManagerInvitation::find()
            ->andWhere([
                'email_to_invite' => $model->email,
                'otp' => $invitationOtp
            ])
            ->one();

        if($invitation) {
            $model->position = $invitation->role;
        }*/

        /*
        if (!$model->signUp(true)) {

            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }*/

        //create new request for staff to review

        $company_name = Yii::$app->request->getBodyParam("company_name");
        $password = Yii::$app->request->getBodyParam("password");
        $name = Yii::$app->request->getBodyParam("name");

        $companyRequest = new CompanyRequest();
        $companyRequest->company_name = $company_name?ucfirst($company_name): $company_name;
        $companyRequest->company_email = Yii::$app->request->getBodyParam("email");
        $companyRequest->position = Yii::$app->request->getBodyParam("position");
        $companyRequest->requesting_for = Yii::$app->request->getBodyParam("requesting_for");

        $companyRequest->name = $name?ucfirst($name): $name;
        $companyRequest->password_hash = $password? Yii::$app->security->generatePasswordHash($password): $password;
        $companyRequest->receive_email = Yii::$app->request->getBodyParam("receive_email");
        $companyRequest->phone_number = Yii::$app->request->getBodyParam("phone_number");
        $companyRequest->currency_code = Yii::$app->request->getBodyParam("currency_code");
        $companyRequest->country_id = Yii::$app->request->getBodyParam("country_id");
        $companyRequest->utm_uuid = Yii::$app->request->getBodyParam("utm_uuid");

        if (!$companyRequest->save()) {
            //$transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $companyRequest->errors
            ];
        }

        /*$company = new Company();
        $company->company_name = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->company_common_name_en = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->company_common_name_ar = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->currency_code = Yii::$app->request->getBodyParam("currency_code");
        $company->company_email = Yii::$app->request->getBodyParam("email");
        $company->company_bonus_commission = 0;
        $company->company_approved_to_hire = false;
        $company->company_followup = true;
        $company->company_followup_interval_weeks = 1;
        $company->company_last_followup_datetime = date('Y-m-d', strtotime ('-7 days'));
        $company->company_status_override = Company::STATUS_UNDER_REVIEW;

        if (!$company->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $company->errors
            ];
        }

        $companyStoreManager = new CompanyStoreManager();
        $companyStoreManager->company_id = $company->company_id;
        $companyStoreManager->uuid = $model->uuid;
        $companyStoreManager->position = Yii::$app->request->getBodyParam("position");
        $companyStoreManager->allow_access = true;

        if (!$companyStoreManager->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $companyStoreManager->errors
            ];
        }

        $contactPhone = new StoreManagerPhone;
        $contactPhone->uuid = $model->uuid;
        $contactPhone->phone_number = Yii::$app->request->getBodyParam("phone_number");

        if (!$contactPhone->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $contactPhone->errors
            ];
        }

        $transaction->commit();*/

        /*$company->notifyUnderReview();

        if(YII_ENV == 'prod')
        {
            Yii::$app->eventManager->track('Company Profile Created',
                [
                    'uuid' => $model->uuid,
                    'name' => $model->name,
                    'email' => $model->email,
                    'company_id' => $company->company_id,
                    'company_name' => $company->company_name,
                    'company_email' => $company->company_email,
                    'phone_number' => $contactPhone->phone_number
                ]);
        }*/

        if(YII_ENV == 'prod')
        {
            Yii::$app->eventManager->track('Company Registration Request Created',
                [
                    'name' => $companyRequest->name,
                    'company_request_uuid' => $companyRequest->company_request_uuid,
                    'company_name' => $companyRequest->company_name,
                    'company_email' => $companyRequest->company_email,
                    //'phone_number' => $contactPhone->phone_number
                ]);
        }

        return [
            "operation" => "success",
            "company_request_uuid" => $companyRequest->company_request_uuid,
            "message" => Yii::t('company', "Our sales team will contact you soon!"),
            //"unVerifiedToken" => $this->_loginResponse($model)
        ];

        /*if($invitation) {

            //accept invitation

            $invitation->accepted = StoreManagerInvitation::ACCEPTED_TRUE;
            $invitation->save();

            //add agent to team

            $companyStoreManager = new CompanyStoreManager();
            $companyStoreManager->company_id = $invitation->company_id;
            $companyStoreManager->uuid = $model->uuid;
            $companyStoreManager->role = $invitation->role;
            $companyStoreManager->save(false);

            // to remove "expression": "NOW()", issue with login
            $contactModel = StoreManager::findOne(['uuid'=>$model->uuid]);
            Yii::$app->user->setIdentity($contactModel);
            return $this->_loginResponse($contactModel);
        }*/
    }

    /**
     * Process email verification
     * @return array
     */
    public function actionVerifyEmail() {

        $code = Yii::$app->request->getBodyParam("code");
        $email = Yii::$app->request->getBodyParam("email");

        //check limit reached

        /*$totalInvalidAttempts = StoreManagerEmailVerifyAttempt::find()
            ->andWhere([
                'email' => $email,
                'ip_address' => Yii::$app->getRequest()->getUserIP()
            ])
            ->andWhere(new \yii\db\Expression("created_at >= DATE_SUB(NOW(),INTERVAL 1 HOUR)"))//last 1 hour
            ->count();

        if ($totalInvalidAttempts > 4) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'You reached your limit to verify email. Please try again after an hour.')
            ];
        }*/

        /*should not be in use

        $exists = StoreManager::find()
            ->andWhere(['email' => $email])
            ->exists();

        if($exists) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "StoreManager new email address already registered")
            ];
        }*/
        $result = StoreManager::verifyEmail($email, $code);
        /*

        if(!$model)
        {
            //add entry for invalid attempt

            $model = new StoreManagerEmailVerifyAttempt;
            $model->code = $code;
            $model->email = $email;
            $model->ip_address = Yii::$app->getRequest()->getUserIP();
            $model->save();

            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Invalid email verification code.')
            ];
        }
        else */

        if ($result['success'])
        {
            //remove otp

            //$model->otp = null;
            //$model->save(false);

            //remove old email verification attempts

           /* StoreManagerEmailVerifyAttempt::deleteAll([
                'email' => $email,
                'ip_address' => Yii::$app->getRequest()->getUserIP()
            ]);*/

            return $this->_loginResponse($result['data']);
        }
        else
        {
            return [
                'operation' => 'error',
                'message' => isset($result['data']) ? $result['data']->getErrors(): $result['message']
            ];
        }
    }

    /**
     * Check if email already verified
     */
    public function actionIsEmailVerified() {
        $token = Yii::$app->request->getBodyParam("token");

        $model = ManagerToken::find()
            ->andWhere(['token_value' => $token])
            ->one();

        if (!$model || !$model->manager) {
            return [
                'status' => 0
            ];
        }

        return [
            'status' => $model->manager->new_email ? 0 : $model->manager->email_verification
        ];
    }

    /**
     * Update contact email address
     * @return type
     */
    public function actionUpdateEmail() {
        $unVerifiedToken = Yii::$app->request->getBodyParam("unVerifiedToken");
        $new_email = Yii::$app->request->getBodyParam("newEmail");

        $contact = StoreManager::findIdentityByUnVerifiedTokenToken($unVerifiedToken);

        if (!$contact) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "StoreManager new email address required")
            ];
        }

        if ($new_email == $contact->email || $new_email == $contact->new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "StoreManager new email address is same as old email")
            ];
        }

        //should not be in use

        $exists = StoreManager::find()
            ->andWhere(['email' => $new_email])
            ->exists();

        if($exists) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "StoreManager new email address already registered")
            ];
        }

        /**
         * Opt will expiry after 60 minutes, so user have to login back to update
         * email
         *
        if (!$contact->findByOtp($contact->otp, 60)) {
            return [
                "operation" => "error-session-expired",
                "message" => Yii::t('company', "Session expired, please log back in")
            ];
        }*/

        $contact->scenario = "updateEmail";

        $contact->new_email = $new_email;

        if ($contact->save()) {

            //extend otp to fix: https://www.pivotaltracker.com/story/show/169037267

            //$contact->generateOtp();

            //to verify new email address

            $contact->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('company', "StoreManager Account Info Updated Successfully, please check email to verify new email address"),
                "unVerifiedToken" => $this->_loginResponse($contact)
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $contact->errors
            ];
        }
    }

    /**
     * Re-send manual verification email to contact
     * @return array
     */
    public function actionResendVerificationEmail() {

        $emailInput = Yii::$app->request->getBodyParam("email");

        $contact = StoreManager::findOne([
            'email' => $emailInput,
        ]);

        $errors = [];
        $errorCode = null; //error code

        if ($contact) {

            if ($contact->email_verification == StoreManager::EMAIL_VERIFIED) {
                return [
                    'operation' => 'error',
                    'errorCode' => 1,
                    'message' => Yii::t('company', 'You have verified your email')
                ];
            }

            //Check if this user sent an email in past few minutes (to limit email spam)
            $emailLimitDatetime =$contact->limit_email? new \DateTime($contact->limit_email): new \DateTime();
            date_add($emailLimitDatetime, date_interval_create_from_date_string('1 minutes'));
            $currentDatetime = new \DateTime();

            if ($contact->limit_email && $currentDatetime < $emailLimitDatetime) {
                $difference = $currentDatetime->diff($emailLimitDatetime);
                $minuteDifference = (int) $difference->i;
                $secondDifference = (int) $difference->s;

                $errorCode = 2;

                $errors = Yii::t('company', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                    'numMinutes' => $minuteDifference,
                    'numSeconds' => $secondDifference,
                ]);
            } else if ($contact->email_verification == StoreManager::EMAIL_NOT_VERIFIED) {
                $contact->sendVerificationEmail();
            }
        } else {
            $errorCode = 3;
            $errors['email'] = [Yii::t('company', 'StoreManager Account not found')];
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
            'message' => Yii::t('company', 'Please click on the link sent to you by email to verify your account'),
        ];
    }
}
