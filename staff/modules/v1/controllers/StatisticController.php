<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use staff\models\Candidate;
use staff\models\CandidateIdCard;

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
        // # of candidates requiring ID card to be renewed

    	$result['id_expired'] = CandidateIdCard::find()
            ->idExpired();

		// # of candidates that need id generated

		$result['id_need_generated'] = Candidate::find()
            ->notDeleted()
            ->filterAssigned()
    		->idNeedGenerated();

    	// Total Candidates

		$result['total_candidates'] = Candidate::find()
			->notDeleted()
            ->count();

		// Total assigned

		$result['total_candidates_assigned'] = Candidate::find()
            ->notDeleted()
			->totalAssigned();

		// Total unassigned

        $result['total_candidates_unassigned'] = Candidate::find()
            ->notDeleted()
			->totalUnassigned();

		return $result;
    }
}

