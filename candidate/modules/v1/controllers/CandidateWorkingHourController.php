<?php
namespace candidate\modules\v1\controllers;

use candidate\models\CandidateWorkingHour;
use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHourAppeal;
use common\models\CandidateWorkingHourAppealUpdates;
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
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = CandidateWorkingHour::find();
        $query->addSelect('sum(total_time) as total_time,date, store_id, candidate_id');
        $query->groupBy('date');
        $query->andWhere(['candidate_id'=>Yii::$app->user->getId()]);
        $query->orderBy('date DESC');

        if ($start_date) {
            $query->filterFrom($start_date);
        }

        if ($end_date) {
            $query->filterTo($end_date);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $date
     * @return array|\yii\db\ActiveRecord|null
     */
    public function actionDateDetail($date) {
       // $date = Yii::$app->request->get('date');
        //$candidate_id = Yii::$app->request->get('candidate_id');
        //$store_id= Yii::$app->request->get('store_id');

        return CandidateWorkingDate::find()
            ->andWhere([
                "date" => $date,
                "candidate_id" => Yii::$app->user->getId(),
                //"store_id" => $store_id
            ])
            ->one();
    }

    /**
     * @return array
     */
    public function actionStats()
    {
        $date = Yii::$app->request->get('date');

        $firstSession = CandidateWorkingHour::find()
            ->andWhere(['date' => $date])
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            //->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at ASC')
            ->one();

        $lastSession = CandidateWorkingHour::find()
            ->andWhere(['date' => $date])
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at DESC')
            ->one();

        $totalTime = CandidateWorkingHour::find()
            ->andWhere(['date' => $date])
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(new Expression("end_time IS NOT NULL"))
            ->sum("total_time");

        $checkIn = $firstSession ? $firstSession->start_time: null;
        $checkOut = $lastSession ? $lastSession->end_time: null;
        $status = $lastSession ? $lastSession->status: null;

        return [
            "checkIn" => $checkIn,
            "checkOut" => $checkOut,
            "totalTime" => $totalTime,
            "status" => $status
        ];
    }

    /**
     * add manually
     * @return array
     */
    public function actionAddHour() {

        $model = \common\models\CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(['store_id' => Yii::$app->user->identity->store_id])
            ->andWhere('end_time is null')
            ->one();

        if ($model) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', 'You are already working')
            ];
        }

        $start_time = strtotime(Yii::$app->request->getBodyParam("start_time"));
        $end_time = strtotime(Yii::$app->request->getBodyParam("end_time"));
        $date = Yii::$app->request->getBodyParam("date");

        $model = new CandidateWorkingHour();
        $model->start_time = date('Y-m-d H:i:s', $start_time);
        $model->end_time = date('Y-m-d H:i:s', $end_time);
        $model->note = Yii::$app->request->getBodyParam("note");
        $model->status = CandidateWorkingHour::STATUS_PENDING;
        $model->candidate_id = Yii::$app->user->getId();
        $model->store_id = Yii::$app->user->identity->store_id;
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
     * Return a List of Invitation
     * @return ActiveDataProvider
     */
    public function actionListHour()
    {
        $date = Yii::$app->request->get('date');
        $appeal_uuid = Yii::$app->request->get('appeal_uuid');

        $query = CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            //->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at ASC');

        if ($date) {
            $query->andWhere(['date' => $date]);
        }

        if ($appeal_uuid) {
            $query->andWhere(['appeal_uuid' => $appeal_uuid]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionWorkingDates() {

        $candidate = Yii::$app->user->identity;
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");

        $query = $candidate->getCandidateWorkingDates()
            ->orderBy('date DESC');

        if ($start_date && $end_date) {
            $query->filterByDateRange($start_date, $end_date);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $date
     * @return array|\yii\db\ActiveRecord|null
     */
    public function actionHoursDetail($date)
    {
        return CandidateWorkingHour::find()
            ->addSelect('*, sum(total_time) as total_time')
            ->andWhere(['date'=>$date])
            ->andWhere(['candidate_id'=>Yii::$app->user->getId()])
            ->one();
    }

    public function actionMarkReadAppealUpdate($id) {
        $model = CandidateWorkingHourAppealUpdates::find()
            ->andWhere(['appeal_update_uuid' => $id])
            ->one();

        $model->is_new = false;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation"  => "success",
        ];
    }

    /**
     * @param $id
     * @return array
     */
    public function actionAppeal($id) {
        $model = new CandidateWorkingHourAppeal();
        $model->candidate_id = Yii::$app->user->getId();
        $model->candidate_working_hour_uuid = $id;
        $model->reason = Yii::$app->request->getBodyParam("reason");

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation"  => "success",
            "message" => "Appeal received",
            //"appeal" => $this->findAppeal($model->appeal_uuid)
        ];
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
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
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
        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_working_hour_uuid' => $id])
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException("record not found");
        }

        return $model;
    }
}
