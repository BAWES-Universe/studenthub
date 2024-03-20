<?php

namespace staff\modules\v1\controllers;

use common\models\CompanyRequest;
use common\models\Contact;
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $refresh = (bool) Yii::$app->request->get('refresh');

        if($refresh) {
            try {
                Yii::$app->cache->flush();
            } catch (Throwable $e) {
                //todo: show in admin?
            }
        }

        $cacheDuration = 60 * 60 * 24; // 1 day then delete from cache

        $candidateCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `candidate` where currency_code="'.$currency.'"',
        ]);

        $companyCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `company` where currency_code="'.$currency.'"',
        ]);

        $transferCandidateCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `transfer_candidate` where currency_code="'.$currency.'"',
        ]);

        $requestCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `request` left join `company` on `request`.`company_id` = `company`.`company_id`  
                where `company`.currency_code="'.$currency.'"',
        ]);

        $companyRequestCacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM `company_request`  where currency_code="'.$currency.'"',
        ]);

        // # of candidates requiring ID card to be renewed

        $result = null;

        $result['refresh'] = $refresh;

        $result['totalUnverifiedEmails'] = Contact::find()
            ->joinWith(['companies'])
            ->andWhere([
                'contact_email_verification' => Contact::EMAIL_NOT_VERIFIED,
                'company.currency_code' => $currency
            ])
            ->count();

    	$result['totalExpiredCards'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::totalExpiredCards()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['assignedExpiredCivilID'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::assignedExpiredCivilID()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

    	// # of candidates that need id generated

	    $result['id_need_generated'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return Candidate::find()
                ->filterAssigned()
                ->notDeleted()
                ->idNeedGenerated()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $result['profileApprovalRequire'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::profileApprovalRequire()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        //Candidates are assigned to work but have incomplete profiles.

        $result['incompleteAssignedToWork'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::incompleteAssignedToWork()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['missingBankInfo'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::withoutBankInfoOrWithPayment()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['requireFollowup'] = Company::getDb()->cache(function ($db) use ($currency){
            return (int) Company::companyFollowupCount($currency);
        }, $cacheDuration, $companyCacheDependency);

        $result['activeRequests'] = Company::getDb()->cache(function ($db) use ($currency){
            return (int) Request::activeRequestCount($currency);
        }, $cacheDuration, $companyCacheDependency);

        $result['totalRequests'] = Request::getDb()->cache(function ($db) use ($currency){
            return (int) Request::totalRequestCount($currency);
        }, $cacheDuration, $requestCacheDependency);

        $result['assignedIdleCandidates'] = Candidate::getDb()->cache(function ($db) use ($currency) {
            return (int) Candidate::getAssignedIdleCandidate()
                ->andWhere(['candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $candidateCacheDependency);

        $result['companyMoreThen40DaysWithoutPayment'] = Company::getDb()->cache(function ($db)use ($currency) {
            return (int)  Company::companiesCountWithNoPaymentIn40Days($currency);
        }, $cacheDuration, $companyCacheDependency);

        $result['last40daysNoRequest'] = Company::getDb()->cache(function ($db) use ($currency){
            return (int)  Company::last40daysWithoutRequest($currency);
        }, $cacheDuration, $companyCacheDependency);

        /*$result['companyUnderReview'] = return (int) Company::find()
            ->andWhere(['company_status_override' => Company::STATUS_UNDER_REVIEW])
            ->count();*/

        $result['companyUnderReview'] = CompanyRequest::getDb()->cache(function ($db) use ($currency){

            return (int)CompanyRequest::find()
                ->andWhere(['status' => CompanyRequest::STATUS_PENDING])
                //->joinWith(['company'])
                ->andWhere(['company_request.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $companyRequestCacheDependency);

        $result['transfersWithNoProfitInProgress'] = TransferCandidate::getDb()->cache(function ($db) use ($currency){
            return (int)TransferCandidate::find()
                ->filterNoProfit()
                ->filterUnpaid()
                ->select('transfer_id')
                ->distinct()
                ->andWhere(['transfer_candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $transferCandidateCacheDependency);

        $result['transfersWithSameRateInProgress'] = TransferCandidate::getDb()->cache(function ($db) use ($currency){
            return (int)TransferCandidate::find()
                ->filterSameRate()
                ->filterUnpaid()
                ->select('transfer_id')
                ->distinct()
                ->andWhere(['transfer_candidate.currency_code' => $currency])
                ->count();
        }, $cacheDuration, $transferCandidateCacheDependency);

        return $result;
    }
}

