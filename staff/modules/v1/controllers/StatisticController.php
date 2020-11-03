<?php

namespace staff\modules\v1\controllers;

use common\models\Request;
use staff\models\Company;
use Yii;
use yii\rest\Controller;
use staff\models\Candidate;


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

    	$result['totalExpiredCards'] =  Candidate::find()
            ->idExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted()
            ->count();

        $result['assignedExpiredCivilID'] =  Candidate::find()
            ->civilIdExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted()
            ->count();

    	// # of candidates that need id generated

	    /*$result['id_need_generated'] = Candidate::find()
            ->notDeleted()
            ->filterAssigned()
            ->idNeedGenerated()
            ->count();*/

        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $result['profileApprovalRequire'] = Candidate::find()
            ->notDeleted()
            ->byApprovalStatus(0)
            ->completedProfileWithoutApproval()
            ->count();

        //Candidates are assigned to work but have incomplete profiles.

        $result['incompleteAssignedToWork'] = Candidate::find()
            ->filterAssigned()
            ->notDeleted()
            ->incompletedProfile()
            ->count();

        $result['missingBankInfo'] = Candidate::withoutBankInfoOrWithPayment()->count();

        $result['requireFollowup'] = Company::companyFollowupCount();

        $result['totalPendingRequests'] = Request::find()
            ->filterWhere(['request_status' => Request::STATUS_PENDING])
            ->count();

        $result['activeRequests'] = Request::find()
            ->filterWhere(['request_status' => Request::STATUS_STARTED])
            ->count();

        $result['assignedIdleCandidates'] = Candidate::getAssignedIdleCandidate()->count();

        return $result;
    }
}

