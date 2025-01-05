<?php

namespace company\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use company\models\CandidateWorkingHour;
use Yii;
use company\models\Store;
use company\models\CandidateWorkLogFeedback;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CandidateWorkLogFeedbackController extends Controller
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
            'class' => HttpBearerAuth::class,
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
     * undo feedback
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUndo($id) {
        //make sure store belongs to login user

        $company = Yii::$app->companyManager->getCompany();

        if (isset($company->subCompanies) && count($company->subCompanies)>0) {
            $storeQuery = $company
                ->getSubCompanyStores()
                ->select('store_id');
//                ->getSubCompanies();
        } else {
            $storeQuery = $company
                ->getStores()->select('store_id');
        }

        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_working_hour_uuid' => $id])
            ->andWhere(["IN", "store_id", $storeQuery])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException("not found!");
        }

        $model->status = CandidateWorkingHour::STATUS_PENDING;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
        ];
    }

    /**
     * save session feedback
     * @return array|string[]
     */
    public function actionSave() {
        $date = Yii::$app->request->getBodyParam("date");

        $model = new CandidateWorkLogFeedback();
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->date = $date? date("Y-m-d", strtotime($date)): null;
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

    /**
     * @throws \yii\db\Exception
     * @throws NotFoundHttpException
     */
    public function actionBulkSave() {
        $arr_cwd_uuid = Yii::$app->request->getBodyParam("arr_cwd_uuid");
        $status = Yii::$app->request->getBodyParam("status");
        $note = Yii::$app->request->getBodyParam("note");
        $reason = Yii::$app->request->getBodyParam("reason");
        $rating = Yii::$app->request->getBodyParam("rating");
        $is_public = (int) Yii::$app->request->getBodyParam("is_public");

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($arr_cwd_uuid as $cwd_uuid) {

            $cwd = CandidateWorkingDate::findOne($cwd_uuid);

            if(!$cwd) {
                $transaction->rollBack();
                throw new NotFoundHttpException('The requested page does not exist.');
            }

            $model = new CandidateWorkLogFeedback();
            $model->candidate_id = $cwd->candidate_id;
            $model->store_id = $cwd->store_id;
            $model->company_id = $cwd->company_id;
            $model->date = $cwd->date;
            $model->status = $status;
            $model->note = $note;
            $model->reason = $reason;
            $model->rating = $rating;
            $model->is_public = $is_public;

            //add sessions/ hours will be updated
            //$model->candidate_working_hour_uuid = Yii::$app->request->getBodyParam("candidate_working_hour_uuid");

            if (!$model->company_id) {
                $store = Store::find()->andWhere(['store_id' => $model->store_id])->one();
                $model->company_id = $store->company_id;
            }

            if(!$model->save()) {
                $transaction->rollBack();
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }
        }

        $transaction->commit();

        return [
            "operation" => "success"
        ];
    }
}