<?php

namespace company\modules\v1\controllers;

use company\models\CompanyContact;
use company\models\Contact;
use company\models\ContactInvitation;
use company\models\Request;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\Cors;
use company\models\Company;

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
                'message' => 'Invalid password reset token. Please request another password reset email'
            ];
        }

        if(!$newPassword) {
            return [
                'operation' => 'error',
                'message' => 'Password field required'
            ];
        }

        $model->setPassword($newPassword);
        $model->removePasswordResetToken();
        $model->save(false);

        return [
            'operation' => 'success',
            'message' => 'Your password has been reset',
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
            'message' => 'Reset password token sent on your email address.',
        ];
    }

    private function _loginResponse($contact) {
        // Return Company access token if everything valid
        $accessToken = $contact->accessToken->token_value;

        $company = Yii::$app->companyManager->getCompany();

        Yii::$app->companyManager->setCompanyId($company->company_id);

        return [
            "operation" => "success",
            "token" => $accessToken,
            "contact" => $contact,
            "company_id" => $company->company_id,
            "profile_name" => Yii::$app->user->identity->contact_name,
            "email" => $company->company_email,
            "active_request_count" => $company->getRequests()->andWhere(['request_status'=>Request::STATUS_STARTED])->count()
        ];
    }

    /**
     * Creates new Agent Account
     * @return array
     */
    public function actionCreateAccount() {

        //invitation otp

        $invitationOtp = Yii::$app->request->getBodyParam("otp");

        $model = new Contact();

        $model->contact_name = ucfirst(Yii::$app->request->getBodyParam("name"));
        $model->contact_email = Yii::$app->request->getBodyParam("email");
        $model->contact_password_hash = Yii::$app->request->getBodyParam("password");

        $invitation = ContactInvitation::find()
            ->where([
                'email_to_invite' => $model->contact_email,
                'otp' => $invitationOtp
            ])
            ->one();

        if($invitation) {
            $model->contact_position = $invitation->role;
        }

        if (!$model->signUp(true)) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        if($invitation) {

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
        }
    }
}
