<?php

namespace candidate\modules\v1\controllers;

use candidate\models\Candidate;
use Yii;
use yii\rest\Controller;
use yii\data\ArrayDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends Controller
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
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }
    
    /**
     * Update candidate email address 
     * @return type
     */
    public function actionUpdateEmail() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        $new_email = Yii::$app->request->getBodyParam("email");

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

        $candidate->scenario = "updateEmail";

        $candidate->candidate_new_email = $new_email;

        if ($candidate->save()) {

            $candidate->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('candidate', "Candidate Account Info Updated Successfully, please check email to verify new email address"),
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }
    }

    /**
     * Set language preference 
     */
    public function actionLanguagePref() {
        $language_pref = Yii::$app->request->getBodyParam('language_pref');

        $model = Yii::$app->user->identity;
        $model->candidate_language_pref = $language_pref;

        $model->scenario = 'updateLanguagePref';

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }
    
    /**
     * Return a List of Salary transfers
     */
    public function actionSalary()
    {
        $currentUser = Yii::$app->user->identity;

        return new ArrayDataProvider([
            'allModels' => array_reverse($currentUser->paidTransferCandidate),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    public function actionChangePassword()
    {
        $model = Yii::$app->user->identity;

        $oldPassword = Yii::$app->request->getBodyParam("old_password");
        $newPassword = Yii::$app->request->getBodyParam("new_password");

        if (empty($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Empty old password"
            ];
        } else if (empty($newPassword)) {
            return [
                "operation" => "error",
                "message" => "Empty new password"
            ];
        }

        if ($oldPassword === $newPassword) {
            return [
                "operation" => "error",
                "message" => "New password should not be same as old password"
            ];
        }

        if (!$model->validatePassword($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Invalid Old Password"
            ];
        }

        if (strlen($newPassword) < 5) {
            return [
                "operation" => "error",
                "message" => "New password length should be great then equal to 5"
            ];
        }

        $candidate = Candidate::findOne($model->getId());
        $candidate->setPassword($newPassword);        
        if ($candidate->save(false)) {
            return [
                "operation" => "success",
                "message" => "Password changed successfully!"
            ];
        }
    }
}
