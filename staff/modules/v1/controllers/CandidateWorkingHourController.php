<?php

namespace staff\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHourAppeal;
use staff\models\CandidateWorkingHour;
use Yii;
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
     * @param $id
     * @return array
     */
    public function actionAppealUpdate($id) {
        $model = $this->findAppeal($id);
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
