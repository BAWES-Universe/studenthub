<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use common\models\Transfer;
use candidate\models\Candidate;

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
        $return = [];
        
        $user = Yii::$app->user->identity;
        
        $totalHours = 0;
        $totalPaid = 0;
        $totalBonus = 0;
        
        foreach($user->transferCandidate as $transfer) 
        {
            $totalHours += $transfer->hours;

            if (
                $transfer->invoice && 
                $transfer->invoice->invoice_status == 'paid'
            ) {
                $totalPaid += ($transfer->hours * $transfer->company_hourly_rate);
                $totalBonus += $transfer->bonus;
            }
        }
        
        $return['total_hours'] = number_format($totalHours);
        $return['total_paid'] = $totalPaid;
        $return['total_bonus'] = $totalBonus;
        $return['total_earning'] = $totalPaid + $totalBonus;
        $return['candidate'] = $user;
        
        return $return;
    }
}
