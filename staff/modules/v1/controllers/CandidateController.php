<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use common\models\Candidate;

/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends Controller
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

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
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
     * Return a List of Candidate Accounts available.
     */
    public function actionList()
    {
        return Candidate::find()->all();
    }

    /**
     * Create a Candidate account
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Candidate();
        $model->scenario = "newAccount";

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_password_hash = Yii::$app->request->getBodyParam("password");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date");
        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        
        //candidate_auth_key
        
        if (!$model->signup())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Candidate account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a Candidate account
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Candidate::findOne((int) $id);

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_password_hash = Yii::$app->request->getBodyParam("password");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date");
        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");

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

        Yii::info("[Candidate Account Updated] ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
}
