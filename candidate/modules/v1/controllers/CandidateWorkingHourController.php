<?php
namespace candidate\modules\v1\controllers;

use candidate\models\CandidateWorkingHour;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;

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
        $query = CandidateWorkingHour::find();
        $query->addSelect('sum(total_time) as total_time,date, store_id, candidate_id');
        $query->groupBy('date');
        $query->andWhere(['candidate_id'=>Yii::$app->user->getId()]);
        $query->orderBy('date DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

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

        $model = new CandidateWorkingHour();
        $model->start_time = date('Y-m-d H:i:s', $start_time);
        $model->end_time = date('Y-m-d H:i:s', $end_time);
        $model->note = Yii::$app->request->getBodyParam("note");
        $model->status = CandidateWorkingHour::STATUS_PENDING;
        $model->candidate_id = Yii::$app->user->getId();
        $model->store_id = Yii::$app->user->identity->store_id;
        $model->date  = date('Y-m-d');
        $model->total_time = $end_time - $start_time;

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

        $query = CandidateWorkingHour::find()
            ->andWhere(['date' => $date])
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(new Expression("end_time IS NOT NULL"))
            ->orderBy('created_at ASC');

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
}
