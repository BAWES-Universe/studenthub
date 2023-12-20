<?php

namespace staff\modules\v1\controllers;

use common\models\CompanyRequest;
use staff\models\Request;
use staff\models\Company;
use staff\models\TransferCandidate;
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
        $refresh = Yii::$app->request->get('refresh');

        if($refresh) {
            Yii::$app->cache->flush();
        }

        $cacheDuration = 60 * 60 * 24; // 1 day then delete from cache
        
        $candidateCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `candidate`',
        ]);

        $companyCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `company`',
        ]);

        $transferCandidateCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `transfer_candidate`',
        ]);

        $requestCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `request`',
        ]);

        $companyRequestCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `company_request`',
        ]);

        // # of candidates requiring ID card to be renewed

        $result = null;

    	$result['totalExpiredCards'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::totalExpiredCards()->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['assignedExpiredCivilID'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::assignedExpiredCivilID()->count();
        }, $cacheDuration, $candidateCacheDependency);

    	// # of candidates that need id generated

	    $result['id_need_generated'] = Candidate::getDb()->cache(function ($db) {
            return Candidate::find()
                ->filterAssigned()
                ->notDeleted()
                ->idNeedGenerated()
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $result['profileApprovalRequire'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::profileApprovalRequire()->count();
        }, $cacheDuration, $candidateCacheDependency);

        //Candidates are assigned to work but have incomplete profiles.

        $result['incompleteAssignedToWork'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::incompleteAssignedToWork()->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['missingBankInfo'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::withoutBankInfoOrWithPayment()->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['requireFollowup'] = Company::getDb()->cache(function ($db) {
            return (int) Company::companyFollowupCount();
        }, $cacheDuration, $companyCacheDependency);

        $result['activeRequests'] = Company::getDb()->cache(function ($db) {
            return (int) Request::activeRequestCount();
        }, $cacheDuration, $companyCacheDependency);

        $result['totalRequests'] = Request::getDb()->cache(function ($db) {
            return (int) Request::totalRequestCount();
        }, $cacheDuration, $requestCacheDependency);

        $result['assignedIdleCandidates'] = Candidate::getDb()->cache(function ($db) {
            return (int) Candidate::getAssignedIdleCandidate()->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['companyMoreThen40DaysWithoutPayment'] = Company::getDb()->cache(function ($db) {
            return (int)  Company::companiesCountWithNoPaymentIn40Days();
        }, $cacheDuration, $companyCacheDependency);

        $result['last40daysNoRequest'] = Company::getDb()->cache(function ($db) {
            return (int)  Company::last40daysWithoutRequest();
        }, $cacheDuration, $companyCacheDependency);

        /*$result['companyUnderReview'] = return (int) Company::find()
            ->andWhere(['company_status_override' => Company::STATUS_UNDER_REVIEW])
            ->count();*/

        $result['companyUnderReview'] = CompanyRequest::getDb()->cache(function ($db) {
            return (int)CompanyRequest::find()
                ->andWhere(['status' => CompanyRequest::STATUS_PENDING])
                ->count();
        }, $cacheDuration, $companyRequestCacheDependency);

        $result['transfersWithNoProfitInProgress'] = TransferCandidate::getDb()->cache(function ($db) {
            return (int)TransferCandidate::find()
                ->filterNoProfit()
                ->filterUnpaid()
                ->select('transfer_id')
                ->distinct()
                ->count();
        }, $cacheDuration, $transferCandidateCacheDependency);

        $result['transfersWithSameRateInProgress'] = TransferCandidate::getDb()->cache(function ($db) {
            return (int)TransferCandidate::find()
                ->filterSameRate()
                ->filterUnpaid()
                ->select('transfer_id')
                ->distinct()
                ->count();
        }, $cacheDuration, $transferCandidateCacheDependency);

        return $result;
    }
}

