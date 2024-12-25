<?php

namespace staff\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHourAppeal;
use common\models\CandidateWorkingHourAppealUpdates;
use staff\models\CandidateWorkingHour;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * CandidateWorkingHour controller - Manage Invitation as Candidate
 */
class CandidateWorkingHourController extends Controller
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
            'options',
            'log'
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
     * Return a List of Invitation
     * @return ActiveDataProvider
     */
    public function actionListDate()
    {
        $candidate_id = Yii::$app->request->get('candidate_id', null);
        $query = CandidateWorkingHour::find();
        $query->addSelect('sum(total_time) as total_time,date, store_id, candidate_id');
        $query->groupBy('date');
        $query->orderBy('date DESC');

        if ($candidate_id && $candidate_id != 'null') {
            $query->andWhere(['candidate_id'=>$candidate_id]);
        }
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }


    /**
     * Return a List of Invitation
     * @return ActiveDataProvider
     */
    public function actionListHour()
    {
        $candidate_id = Yii::$app->request->get('candidate_id', null);
        $date = Yii::$app->request->get('date', null);
        $query = CandidateWorkingHour::find();

        $query->orderBy('created_at DESC');

        if ($candidate_id && $candidate_id != 'null') {
            $query->andWhere(['candidate_id'=>$candidate_id]);
        }

        if ($date && $date != 'null') {
            $query->andWhere(['date'=>$date]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $date
     * @param $candidateId
     * @return array|\yii\db\ActiveRecord|null
     */
    public function actionHoursDetail($date,$candidateId)
    {
        return CandidateWorkingHour::find()
            ->addSelect('*,sum(total_time) as total_time')
            ->andWhere(['date'=>$date])
            ->andWhere(['candidate_id'=>$candidateId])
            ->one();
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteDay($id) {
        $model = CandidateWorkingDate::find()
            ->andWhere(['cwd_uuid' => $id])
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Work Log removed successfully"),
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteSession($id) {
        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_working_hour_uuid' => $id])
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Session removed successfully"),
        ];
    }

    /**
     * add manually
     * @return array
     */
    public function actionAddHour($id) {

        $appeal = $this->findAppeal($id);

        $start_time = strtotime(Yii::$app->request->getBodyParam("start_time"));
        $end_time = strtotime(Yii::$app->request->getBodyParam("end_time"));
        $date = Yii::$app->request->getBodyParam("date");

        $model = new \candidate\models\CandidateWorkingHour();
        $model->start_time = date('Y-m-d H:i:s', $start_time);
        $model->end_time = date('Y-m-d H:i:s', $end_time);
        $model->note = Yii::$app->request->getBodyParam("note");
        $model->status = CandidateWorkingHour::STATUS_APPROVED;

        $model->candidate_id = $appeal->candidate_id;
        $model->store_id  = $appeal->originalHour->store_id;
        $model->appeal_uuid = $appeal->appeal_uuid;
        $model->date  = $date ? date('Y-m-d', strtotime($date)): date('Y-m-d');
        $model->total_time = $end_time - $start_time;
        $model->via = "Manual Log";

        //$model->start_location_lat = $lat;
        //$model->start_location_long = $long;

        if (!$model->save()) {

            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Session saved successfully")
        ];
    }

    /**
     * @param $id
     * @return array
     */
    public function actionAppealUpdate($id) {
        $model = new CandidateWorkingHourAppealUpdates();
        $model->appeal_uuid = $id;
        $model->update =  Yii::$app->request->getBodyParam("update");
        $model->detail = Yii::$app->request->getBodyParam("detail");

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation"  => "success",
            "message" => "Appeal update posted"
        ];
    }

    public function actionAppealUpdateStatus($id) {
        $model = $this->findAppeal($id);
        $model->status =  Yii::$app->request->getBodyParam("status");

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation"  => "success",
            "message" => "Appeal status updated"
        ];
    }

    /**
     * @return ActiveDataProvider
     */
    public  function actionAppealList()
    {
        $from = Yii::$app->request->get("from");
        $to = Yii::$app->request->get("to");
        $status = Yii::$app->request->get("status");
        $q = Yii::$app->request->get("q");

        $query = CandidateWorkingHourAppeal::find()
            ->joinWith(['candidate'])
            ->orderBy("candidate_working_hour_appeal.created_at DESC");

        if ($from) {
            $query->andWhere(new Expression("DATE(created_at) >= DATE('".date("y-m-d", strtotime($from) )."')"));
        }

        if ($to) {
            $query->andWhere(new Expression("DATE(created_at) <= DATE('".date("y-m-d", strtotime($to) )."')"));
        }

        if (in_array($status, [10,1,2,3])) {
            $query->andWhere(['candidate_working_hour_appeal.status' => $status]);
        }

        if ($q) {
            $query->andWhere([
                "OR",
                ['like', 'candidate_name', $q],
                ['like', 'candidate_name_ar', $q],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    public function actionAppealDetail($id) {
        return $this->findAppeal($id);
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    public function findAppeal($id) {
        $model = CandidateWorkingHourAppeal::find()
            ->andWhere(['appeal_uuid' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException("record not found");
        }

        return $model;
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    public function findModel($id) {
        $model = \candidate\models\CandidateWorkingHour::find()
            ->andWhere(['candidate_working_hour_uuid' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException("record not found");
        }

        return $model;
    }
}
