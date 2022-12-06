<?php

namespace staff\modules\v1\controllers;

use staff\models\CandidateWorkingHour;
use Yii;
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
}
