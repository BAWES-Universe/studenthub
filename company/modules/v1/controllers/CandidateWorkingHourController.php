<?php

namespace company\modules\v1\controllers;

use admin\models\CandidateWorkingHour;
use company\models\Candidate;
use company\models\Store;
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
        if (!$candidate_id && $candidate_id == 'null') {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }
        $candidate = Candidate::findOne(['candidate_id'=>$candidate_id]);
        if (!$candidate->store_id) {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }

        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $store = Store::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->filterByStoreId($candidate->store_id)
            ->one();

        if (!$store) {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }

        $query = CandidateWorkingHour::find();
        $query->addSelect('sum(total_time) as total_time,date, store_id, candidate_id');
        $query->groupBy('date');
        $query->orderBy('date DESC');
        $query->andWhere(['candidate_id'=>$candidate_id]);
        $query->andWhere(['store_id'=>$candidate->store_id]);

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
        if (!$candidate_id && $candidate_id == 'null') {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }
        $candidate = Candidate::findOne(['candidate_id'=>$candidate_id]);
        if (!$candidate->store_id) {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }

        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $store = Store::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->filterByStoreId($candidate->store_id)
            ->one();

        if (!$store) {
            return [
                "operation" => "error",
                "message" => 'Invalid Access'
            ];
        }

        $date = Yii::$app->request->get('date', null);
        $query = CandidateWorkingHour::find();

        $query->orderBy('created_at DESC');

        if ($candidate_id && $candidate_id != 'null') {
            $query->andWhere(['candidate_id'=>$candidate_id]);
        }
        $query->andWhere(['store_id'=>$candidate->store_id]);

        if ($date && $date != 'null') {
            $query->andWhere(['date'=>$date]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
