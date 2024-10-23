<?php

namespace company\modules\v1\controllers;

use Yii;
use company\models\Store;
use company\models\CandidateWorkLogFeedback;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;

class CandidateWorkLogFeedbackController extends Controller
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
        $behaviors['authenticator']['except'] = [
            'options'
        ];

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
     * save session feedback
     * @return array|string[]
     */
    public function actionSave() {
        $model = new CandidateWorkLogFeedback();
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->date = date("Y-m-d", strtotime(Yii::$app->request->getBodyParam("date")));
        $model->status = Yii::$app->request->getBodyParam("status");
        $model->note = Yii::$app->request->getBodyParam("note");
        $model->reason = Yii::$app->request->getBodyParam("reason");
        $model->rating = Yii::$app->request->getBodyParam("rating");
        $model->is_public = (int) Yii::$app->request->getBodyParam("is_public");
        $model->candidate_working_hour_uuid = Yii::$app->request->getBodyParam("candidate_working_hour_uuid");

        if (!$model->company_id) {
            $store = Store::find()->andWhere(['store_id' => $model->store_id])->one();
            $model->company_id = $store->company_id;
        }

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        $message = $model->status == 1? "{candidate}’s working hours has been approved!":
            "{candidate}’s working hours has been rejected!";

        return [
            "operation" => "success",
            "message" => Yii::t("company", $message, [
                "candidate" => Yii::$app->language == "ar" ?
                    $model->candidate->candidate_name_ar: $model->candidate->candidate_name
            ])
        ];
    }
}