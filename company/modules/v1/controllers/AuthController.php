<?php

namespace company\modules\v1\controllers;

use company\models\Company;
use company\models\Contact;
use company\models\ContactPhone;
use company\models\ContactToken;
use company\models\CompanyContact;
use common\models\ContactEmailVerifyAttempt;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
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

                $contact = Contact::findByEmail($email);
                
                if ($contact && $contact->validatePassword($password)) {
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
            'is-email-verified'
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
     * Perform validation on the company account (check if he's allowed login to platform)
     * If everything is alright,
     * Returns the BEARER access token required for futher requests to the API
     * @return array
     */
    public function actionLogin()
    {  
        $contact = Yii::$app->user->identity;

        // Email and password are correct, check if his email has been verified
        // If email has been verified, then allow him to log in
        if ($contact->contact_email_verification != Contact::EMAIL_VERIFIED) {

            //$contact->generateOtp();
            //$contact->save(false);

            return [
                "operation" => "error",
                "errorType" => "email-not-verified",
                "message" => Yii::t('company', "Please click the verification link sent to you by email to activate your account"),
                "unVerifiedToken" => $this->_loginResponse($contact)
            ];
        }

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

        $model = Contact::findByPasswordResetToken($token);

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

        $model = new \company\models\PasswordResetRequestForm();
        $model->email = $emailInput;

        if ($model->validate()) {

            $contact = Contact::findOne([
                'contact_email' => $model->email,
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
            $company = $contact->getManagedCompanies()->one();
        }

        return [
            "operation" => "success",
            "token" => $accessToken,
            "company_id" => $company->company_id,
            "profile_name" => $contact->contact_name,
            "email" => $contact->contact_email,
            "active_request_count" => $company->getRequests()->activeRequest()->count()
        ];
    }

    /**
     * Creates new Agent Account
     * @return array
     */
    public function actionCreateAccount() {

        //$invitationOtp = Yii::$app->request->getBodyParam("otp");

        $transaction = Yii::$app->db->beginTransaction ();

        $model = new Contact();

        $model->contact_name = ucfirst(Yii::$app->request->getBodyParam("name"));
        $model->contact_email = Yii::$app->request->getBodyParam("email");
        $model->contact_password_hash = Yii::$app->request->getBodyParam("password");
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");

        //Generate OTP for Candidate
        //$model->generateOTP();

        /*$invitation = ContactInvitation::find()
            ->andWhere([
                'email_to_invite' => $model->contact_email,
                'otp' => $invitationOtp
            ])
            ->one();

        if($invitation) {
            $model->contact_position = $invitation->role;
        }*/

        if (!$model->signUp(true)) {

            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        $company = new Company();
        $company->company_name = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->company_common_name_en = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->company_common_name_ar = ucfirst(Yii::$app->request->getBodyParam("company_name"));
        $company->company_email = Yii::$app->request->getBodyParam("email");
        $company->company_bonus_commission = 0;
        $company->company_approved_to_hire = false;
        $company->company_followup = true;
        $company->company_followup_interval_weeks = 1;
        $company->company_last_followup_datetime = date('Y-m-d', strtotime ('-7 days'));

        if (!$company->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $company->errors
            ];
        }

        $companyContact = new CompanyContact();
        $companyContact->company_id = $company->company_id;
        $companyContact->contact_uuid = $model->contact_uuid;
        $companyContact->contact_position = Yii::$app->request->getBodyParam("contact_position");
        $companyContact->allow_access = true;

        if (!$companyContact->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $companyContact->errors
            ];
        }

        $contactPhone = new ContactPhone;
        $contactPhone->contact_uuid = $model->contact_uuid;
        $contactPhone->phone_number = Yii::$app->request->getBodyParam("phone_number");

        if (!$contactPhone->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $contactPhone->errors
            ];
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "contact_uuid" => $model->contact_uuid,
            "message" => Yii::t('company', "Please click on the link sent to you by email to verify your account"),
            "unVerifiedToken" => $this->_loginResponse($model)
        ];

        /*if($invitation) {

            //accept invitation

            $invitation->accepted = ContactInvitation::ACCEPTED_TRUE;
            $invitation->save();

            //add agent to team

            $companyContact = new CompanyContact();
            $companyContact->company_id = $invitation->company_id;
            $companyContact->contact_uuid = $model->contact_uuid;
            $companyContact->role = $invitation->role;
            $companyContact->save(false);

            // to remove "expression": "NOW()", issue with login
            $contactModel = Contact::findOne(['contact_uuid'=>$model->contact_uuid]);
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

        $totalInvalidAttempts = ContactEmailVerifyAttempt::find()
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
        }

        $model = Contact::verifyEmail($email, $code);

        if ($model) {

            //remove otp

            //$model->contact_otp = null;
            //$model->save(false);

            //remove old email verification attempts

            ContactEmailVerifyAttempt::deleteAll([
                'email' => $email,
                'ip_address' => Yii::$app->getRequest()->getUserIP()
            ]);

            return $this->_loginResponse($model);

        } else {
            //add entry for invalid attempt

            $model = new ContactEmailVerifyAttempt;
            $model->code = $code;
            $model->email = $email;
            $model->ip_address = Yii::$app->getRequest()->getUserIP();
            $model->save();

            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Invalid email verification code.')
            ];
        }
    }

    /**
     * Check if email already verified
     */
    public function actionIsEmailVerified() {
        $token = Yii::$app->request->getBodyParam("token");

        $model = ContactToken::find()
            ->andWhere(['token_value' => $token])
            ->one();

        if (!$model || !$model->contact) {
            return [
                'status' => 0
            ];
        }

        return [
            'status' => $model->contact->contact_new_email ? 0 : $model->contact->contact_email_verification
        ];
    }

    /**
     * Update contact email address
     * @return type
     */
    public function actionUpdateEmail() {
        $unVerifiedToken = Yii::$app->request->getBodyParam("unVerifiedToken");
        $new_email = Yii::$app->request->getBodyParam("newEmail");

        $contact = Contact::findIdentityByUnVerifiedTokenToken($unVerifiedToken);

        if (!$contact) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Contact new email address required")
            ];
        }

        if ($new_email == $contact->contact_email || $new_email == $contact->contact_new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Contact new email address is same as old email")
            ];
        }

        /**
         * Opt will expiry after 60 minutes, so user have to login back to update
         * email
         *
        if (!$contact->findByOtp($contact->contact_otp, 60)) {
            return [
                "operation" => "error-session-expired",
                "message" => Yii::t('company', "Session expired, please log back in")
            ];
        }*/

        $contact->scenario = "updateEmail";

        $contact->contact_new_email = $new_email;

        if ($contact->save()) {

            //extend otp to fix: https://www.pivotaltracker.com/story/show/169037267

            //$contact->generateOtp();

            //to verify new email address

            $contact->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('company', "Contact Account Info Updated Successfully, please check email to verify new email address"),
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

        $emailInput = Yii::$app->request->getBodyParam("contact_email");

        $contact = Contact::findOne([
            'contact_email' => $emailInput,
        ]);

        $errors = false;
        $errorCode = null; //error code

        if ($contact) {

            if ($contact->contact_email_verification == Contact::EMAIL_VERIFIED) {
                return [
                    'operation' => 'error',
                    'errorCode' => 1,
                    'message' => Yii::t('company', 'You have verified your email')
                ];
            }

            //Check if this user sent an email in past few minutes (to limit email spam)
            $emailLimitDatetime = new \DateTime($contact->contact_limit_email);
            date_add($emailLimitDatetime, date_interval_create_from_date_string('1 minutes'));
            $currentDatetime = new \DateTime();

            if ($contact->contact_limit_email && $currentDatetime < $emailLimitDatetime) {
                $difference = $currentDatetime->diff($emailLimitDatetime);
                $minuteDifference = (int) $difference->i;
                $secondDifference = (int) $difference->s;

                $errorCode = 2;

                $errors = Yii::t('company', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                    'numMinutes' => $minuteDifference,
                    'numSeconds' => $secondDifference,
                ]);
            } else if ($contact->contact_email_verification == Contact::EMAIL_NOT_VERIFIED) {
                $contact->sendVerificationEmail();
            }
        } else {
            $errorCode = 3;
            $errors['email'] = [Yii::t('company', 'Contact Account not found')];
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
