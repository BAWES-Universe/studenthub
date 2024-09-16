<?php

namespace candidate\modules\v1\controllers;

use common\models\RequestInterview;
use staff\models\Request;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;


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
        $result = [];
        $user = Yii::$app->user->identity;
        $stats = $user->accountStatistic;

        $totalHours = (int)$stats['hours'];
        $totalMinutes = (int)$stats['minutes'];
        $totalSeconds = (int)$stats['seconds'];

        $totalPaid  = (int)$stats['paid'];
        $totalBonus = (int)$stats['bonus'];

        $result['total_hours'] = number_format($totalHours);
        $result['total_minutes'] = number_format($totalMinutes);
        $result['total_seconds'] = number_format($totalSeconds);
        $result['total_paid'] = $totalPaid;
        $result['total_bonus'] = $totalBonus;
        $result['total_earning'] = $totalPaid + $totalBonus;
        $result['candidate'] = $user;

        $result['totalInterviewScheduled'] = RequestInterview::find()
            ->joinWith(['request'])
            ->andWhere([
                'candidate_id' => Yii::$app->user->getId(),
                'request_interview.status' => RequestInterview::STATUS_SCHEDULED
            ])
            ->andWhere(new Expression('interview_at > NOW()'))
            ->andWhere(['NOT IN', 'request.request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_FINISHED,
                Request::STATUS_CANCELLED
            ]])
            ->count();

        return $result;
    }
}
