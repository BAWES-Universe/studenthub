<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use admin\models\Company;
use admin\models\TransferCandidate;
use admin\models\University;
use common\models\CandidateStats;
use common\models\CandidateWorkHistory;
use common\models\CompanyStats;
use common\models\Invitation;
use common\models\Staff;
use common\models\StaffLeave;
use common\models\StaffSalary;
use common\models\StaffWorkSession;
use common\models\StoryActivity;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use admin\models\Transfer;


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
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
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
     * @return void
     */
    public function actionClearCache() {
        Yii::$app->cache->flush();
    }

    /**
     * Return Statistic Details
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $startDate = Yii::$app->request->get('start_date', null);
        $endDate = Yii::$app->request->get('end_date', null);

        $payableDetail = Candidate::getTotalPayableCandidate($currency);
        // Candidates
        $totalCandidate = Candidate::candidateCountByCondition(false, $startDate, $endDate, $currency);
        $totalAssignedToWork = Candidate::candidateCountByAssigned($startDate, $endDate, $currency);
        $approved = Candidate::candidateCountByCondition('approved', $startDate, $endDate, $currency);

        $result['candidates']['total_candidate'] = $totalCandidate;
        $result['candidates']['total_assigned'] = $totalAssignedToWork;
        $result['candidates']['total_unapproved'] = $totalCandidate - $approved;
        $result['candidates']['invited'] = Candidate::invited($startDate, $endDate, $currency);
        $result['candidates']['suggested'] = Candidate::suggested($startDate, $endDate, $currency);

        //retention after 1 year

        $totalRetained = CandidateWorkHistory::find()
            ->andWhere(new Expression("DATEDIFF(end_date, start_date) > 365"))
            //->filterByJoiningDate($startDate, $endDate)
            ->count();

        $totalHired = CandidateWorkHistory::find()
            //->filterByJoiningDate($startDate, $endDate)
            ->count();

        $result['candidates']['retentionRatio'] =
            $totalHired > 0? (100 * $totalRetained / $totalHired): 0;

        //Recruitment Yield Ratio
        // This metric compares the number of successful hires to the number of Invitations sent,
        // offering insights into the effectiveness of sourcing methods and recruitment strategies.

        $invitationSent = Invitation::find()
            ->count();

        $result['candidates']['recruitmentYieldRatio'] =
            $invitationSent > 0 ? ($totalHired * 100/$invitationSent): 0;

        $result['company']['activeClient'] = Company::getCompanyByCondition('status',$startDate, $endDate, $currency);
        $result['company']['all'] = Company::getCompanyByCondition(null, $startDate, $endDate, $currency);
        $result['company']['request']['all'] = Company::request(null,$startDate, $endDate, $currency);
        $result['company']['request']['delivered'] = Company::request('delivered', $startDate, $endDate, $currency);
        $result['payable']['total'] = $payableDetail['payable'];
        $result['payable']['amount'] = $payableDetail['amount'];

        $result['totalUniversitiesToFix'] = University::find()
            ->andWhere(new Expression('university_name_en IS NULL OR university_name_ar IS NULL OR 
                university_name_en = university_name_ar'))
            ->count();

        // Transfers
        $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK, $startDate, $endDate, $currency);
        $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT, $startDate, $endDate, $currency);

        $result['transfers'] = [];
        $result['transfers']['locked'] = [
            "code" => Transfer::STATUS_LOCK,
            "total" => (isset($lockedTransfers['total']))? (int) $lockedTransfers['total'] : 0
        ];

        $result['transfers']['paymentSent'] = [
            "code" => Transfer::STATUS_PAYMENT_SENT,
            "total" => (isset($paymentSentTransfers['total']))? (int) $paymentSentTransfers['total'] : 0
        ];

        $salaryQuery = StaffSalary::find();

        if($startDate) {
            $salaryQuery->andWhere(new Expression("DATE(salary_date) >= DATE('" . $startDate . "')"));
        }
        if($endDate) {
            $salaryQuery->andWhere(new Expression("DATE(salary_date) <= DATE('" . $endDate . "')"));
        }

        $result['totalSalaryPaid'] = (double) $salaryQuery->sum('salary');

        $result['absent'] = (int) StaffLeave::find()
            ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"))
            ->count();

        $result['attended'] = (int) StaffWorkSession::find()
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."')"))
            ->count();

        $totalStaff = Staff::find()
            ->andWhere(['staff_status' => Staff::STATUS_ACTIVE])
            ->count();

        $result['didnt_attended'] = (int) ($totalStaff - $result['attended'] - $result['absent']);

        $result['lastCronRun'] = Yii::$app->cache->get("lastCronRun");

        return $result;
    }

    /**
     * transfer stats
     */
    public function actionTransfer() {

        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $startDate = Yii::$app->request->get('start_date', null);
        $endDate = Yii::$app->request->get('end_date', null);

        $data = [];

        $data['totalTransferCandidate'] = (int) TransferCandidate::find()
            ->andWhere(['transfer_candidate.currency_code' => $currency])
            ->joinWith(['transfer'])
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->andWhere(['NOT IN', 'transfer_status', [Transfer::STATUS_INITIATED, Transfer::STATUS_CANCEL]])//no draft
            //->filterPaid()
            ->count();

        $totalPaymentAmountReceived = Transfer::find()            //ignore duplicate entries of child transfers
            ->andWhere(['transfer.currency_code' => $currency])
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->filterPaymentReceived();

        if($startDate) {
            $totalPaymentAmountReceived->andWhere(new Expression("DATE(start_date) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $totalPaymentAmountReceived->andWhere(new Expression("DATE(end_date) <= DATE('" . $endDate . "')"));
        }

        $data['totalPaymentAmountReceived'] = (double) $totalPaymentAmountReceived->sum('company_total');

        $totalBelongingToCandidates = Transfer::find()//ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            //->andWhere(['NOT IN', 'transfer_status', [Transfer::STATUS_INITIATED, Transfer::STATUS_CANCEL]])//no draft
            ->andWhere(['transfer.currency_code' => $currency])
            ->filterPaymentReceived();

        if($startDate) {
            $totalBelongingToCandidates->andWhere(new Expression("DATE(start_date) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $totalBelongingToCandidates->andWhere(new Expression("DATE(end_date) <= DATE('" . $endDate . "')"));
        }

        $data['totalBelongingToCandidates'] = (double) $totalBelongingToCandidates->sum('total');

        $totalProfit = Transfer::find()
            ->andWhere(['transfer.currency_code' => $currency])
            ->andWhere('transfer.parent_transfer_id IS NULL')//ignore duplicate entries of child transfers
            ->filterPaymentReceived();

        if($startDate) {
            $totalProfit->andWhere(new Expression("DATE(start_date) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $totalProfit->andWhere(new Expression("DATE(end_date) <= DATE('" . $endDate . "')"));
        }

        $data['totalProfit'] = (double) $totalProfit->sum('company_total - total');

        return $data;
    }

    /**
     * @return array
     */
    public function actionRevenue() {

        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $result = [];

        $result['total_company'] = Company::find()->count();

        $result['total_candidate'] = Candidate::find()->count();

        $result['company_stats'] = CompanyStats::find()
            ->andWhere(['company_stats.currency_code' => $currency])
            ->select("SUM(total_revenue) as total_revenue, MIN(total_revenue) as min_revenue, 
                MAX(total_revenue) as max_revenue")
            ->asArray()
            ->one();

        $result['candidate_stats'] = CandidateStats::find()
            ->andWhere(['candidate_stats.currency_code' => $currency])
                ->select("SUM(total_revenue) as total_revenue, MIN(total_revenue) as min_revenue, 
                MAX(total_revenue) as max_revenue")
            ->asArray()
            ->one();

        //CLV - customer lifetime value

        /*Candidate::find()
            ->joinWith(['candidateWorkHistory', 'candidateStats'])//, false, "inner join"
            ->andWhere(new Expression("candidate.store_id IS NULL AND candidate_work_history.id IS NOT NULL"))
            ->andWhere(['currency_code' => $currency])
            ->average("total_revenue");*/

        $result['candidate_clv'] = CandidateStats::find()
            ->joinWith(['candidateWorkHistories', 'candidate'])//, false, "inner join"
             //started work but not working anymore
            ->andWhere(new Expression("candidate.store_id IS NULL AND candidate_work_history.end_date IS NOT NULL"))
            ->andWhere(['candidate_stats.currency_code' => $currency])
            ->average("total_revenue");

        //last month

        //todo: add currency support

        $noOfHired = (int) CandidateWorkHistory::find()
            //->andWhere(new Expression("MONTH(candidate_work_history.start_date) = MONTH(CURRENT_DATE) -1 AND
            //    YEAR(candidate_work_history.start_date) = YEAR(CURRENT_DATE)"))
            //->andWhere(new Expression("candidate_work_history.start_date >= DATEADD(month, DATEDIFF(month, 0, GETDATE()) - 1, 0)
            //    AND candidate_work_history.start_date < DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0)"))
            ->andWhere(new Expression("candidate_work_history.start_date >= DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL -1 MONTH)
                AND candidate_work_history.start_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')"))
            ->filterCurrency($currency)
            //->andWhere(['currency' => $currency])
            ->count();

        $salaryPaid = (double) Staff::find()
            ->andWhere(['staff_role' => Staff::ROlE_RECRUITER])
            ->andWhere(['staff_salary_currency' => $currency])
            ->sum("staff_salary");

        $costPerCandidate = $salaryPaid? $salaryPaid / $noOfHired: 0;

        //how much we normally earning per assignment

        $monthlyEarningPerAssignment = (double) TransferCandidate::find()
            ->andWhere(['currency_code' => $currency])
            ->andWhere(new Expression("tc_created_at >= DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL -1 MONTH)
                AND tc_created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')"))
            //->andWhere(new Expression("tc_created_at >= DATEADD(month, DATEDIFF(month, 0, GETDATE()) - 1, 0)
            //    AND tc_created_at < DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0)"))
            ->average(new Expression("company_total - candidate_total"));
        //((company_hourly_rate - candidate_hourly_rate) * hours) + transfer_cost + bonus_commission
        //avg profit in candidate transfer in last month

        $averageMonthDurationPerAssignment = (int) CandidateWorkHistory::find()
            ->limit(100)
            ->orderBy('id DESC')//latest first
            ->filterCurrency($currency)
            //->andWhere(['currency' => $currency])
            ->average(new Expression("TIMESTAMPDIFF(MONTH, start_date, end_date)"));
            //->average(new Expression("DATEDIFF(MONTH, start_date, end_date)"));

        $possibleEarningPerAssignment = $monthlyEarningPerAssignment * $averageMonthDurationPerAssignment;

        $result['recruitment_cost_ratio'] = [
            "noOfHired" => $noOfHired,
            "salaryPaid" => $salaryPaid,
            "costPerCandidate" => $costPerCandidate,
            "monthlyEarningPerAssignment" => $monthlyEarningPerAssignment,
            "averageMonthDurationPerAssignment" => $averageMonthDurationPerAssignment,
            "possibleEarningPerAssignment" => $possibleEarningPerAssignment
        ];

        $result['timeToCompleteStory'] = (int) StoryActivity::find()
            ->limit(100)
            ->orderBy('activity_last_updated_at DESC')//latest first
            ->andWhere(['activity_status' => StoryActivity::STATUS_DELIVERED])
            ->average('story_activity.activity_time_spent');

        return $result;
    }

    /**
     * @return void
     */
    public function actionInvitationGraphData()
    {
        return Invitation::getDataByMonths();
    }
}
