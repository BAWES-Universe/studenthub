<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use admin\models\Candidate;

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
     * Return a List of Candidate Accounts assigned to
     * Specific Store.
     */
    public function actionFilter()
    {
        $store_id = Yii::$app->request->getBodyParam("store_id");

        $query = Candidate::find();

        if($store_id) {
            $query->where(['store_id' => $store_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Review candidate accounts
     */
    public function actionReview()
    {
        $query = Candidate::find()
            ->where(['approved' => 0]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Approve candidate account
     */
    public function actionApprove($id)
    {
        $model = Candidate::findOne((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->approved = 1;
        
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

        Yii::info("[Candidate Account Approved] ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account approved successfully"
        ];
    }
}
