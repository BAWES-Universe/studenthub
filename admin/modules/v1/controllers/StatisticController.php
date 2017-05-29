<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use Yii;
use yii\rest\Controller;
use common\models\Transfer;

/**
 * Statistic controller
 */
class StatisticController extends Controller
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
     * Return Statistic Details
     */
    public function actionList()
    {
        $arr_status = Transfer::statusList();
        $totalCandidate = Candidate::find()->count();
        $totalAssign = Candidate::find()->where('store_id is NOT NULL')->count();
        $approved = Candidate::find()->where(['approved'=>1])->count();
        $result['transfers'] = [];

        foreach ($arr_status as $key => $value) 
        {
            $count = Transfer::find()
                ->where(['transfer_status' => $key])
                ->count();

            $result['transfers'][] = [
                'transfer_status' => $value,
                'count' => $count,
                'status_code' => $key
            ];
        }

        $result['candidates']['total_candidate'] = $totalCandidate;
        $result['candidates']['total_assign'] = $totalAssign;
        $result['candidates']['total_unassign'] = $totalCandidate - $totalAssign;
        $result['candidates']['total_approved'] = $approved;
        $result['candidates']['total_unapproved'] = $totalCandidate - $approved;

        return $result;
    }
}
