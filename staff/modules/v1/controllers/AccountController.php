<?php

namespace staff\modules\v1\controllers;

use staff\models\Staff;
use Yii;
use yii\rest\Controller;


/**
 *  Account controller - Manage account as staff
 */
class AccountController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
     * Updates password based on current password
     * @return array
     */
    public function actionUpdatePassword()
    {
        $staff = Yii::$app->user->identity;
        
        $password = Yii::$app->request->getBodyParam("password");

        if ($staff && !$staff->validatePassword($password)) {
            return [
                'operation' => 'error',
                'message' => 'Invalid current password'
            ];
        }

        $newPassword = Yii::$app->request->getBodyParam("newPassword");
        $confirmNewPassword = Yii::$app->request->getBodyParam("confirmNewPassword");

        if ($newPassword != $confirmNewPassword) {
            return [
                'operation' => 'error',
                'message' => 'confirm password does not match'
            ];
        }
        //update password 
        
        $staff->setPassword($newPassword);
        $staff->save(false);
        
        return [
            'operation' => 'success',
            'message' => 'Your password has been reset'
        ];
    }

    public function actionAccount()
    {
        $staff = Yii::$app->user->identity;

        // Return Staff access token if everything valid
        $accessToken = $staff->accessToken->token_value;

        return [
            "operation" => "success",
            "token" => $accessToken,
            "staff_id" => $staff->staff_id,
            "name" => $staff->staff_name,
            "email" => $staff->staff_email,
            "story" => $staff->currentStory,
            "role" => $staff->staff_role,
            "staff_job_title" => $staff->staff_job_title,
            "staff_notification" => $staff->staff_notification,
            "staff_photo" => $staff->staff_photo,
        ];
    }

    /**
     * Create a staff account
     */
    public function actionUpdate()
    {
        $model = Yii::$app->user->identity;

        $model->staff_name = Yii::$app->request->getBodyParam("name");
        $model->staff_notification = Yii::$app->request->getBodyParam("staff_notification");
        $model->staff_job_title = Yii::$app->request->getBodyParam("staff_job_title");

        $staff_photo = Yii::$app->request->getBodyParam('staff_photo');

        if($staff_photo)
            $model->setLogo($staff_photo);

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        if(YII_ENV == 'prod')
            Yii::$app->eventManager->setUser('staff' .$model->staff_id, [
                '$first_name' => $model->staff_name,
                '$email' => $model->staff_email
            ]);

        Yii::info('[Staff Account Updated] Staff "'.$model->staff_email.'" updated', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff account successfully updated"
        ];
    }


    /**
     * validate user password
     * @return mixed
     */
    public function actionValidateUserPassword() {
        $password = Yii::$app->request->getBodyParam("password");
        if ($password) {
            return Yii::$app->user->identity->validatePassword($password);
        }
    }
}
