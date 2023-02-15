<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use admin\models\Company;
use admin\models\TransferCandidate;
use admin\models\University;
use common\models\Staff;
use common\models\StaffLeave;
use common\models\StaffSalary;
use common\models\StaffWorkSession;
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
        $startDate = Yii::$app->request->get('start_date', null);
        $endDate = Yii::$app->request->get('end_date', null);
        $payableDetail = Candidate::getTotalPayableCandidate();
        // Candidates
        $totalCandidate = Candidate::candidateCountByCondition(false, $startDate, $endDate);
        $totalAssignedToWork = Candidate::candidateCountByCondition('assigned', $startDate, $endDate);
        $approved = Candidate::candidateCountByCondition('approved', $startDate, $endDate);

        $result['candidates']['total_candidate'] = $totalCandidate;
        $result['candidates']['total_assigned'] = $totalAssignedToWork;
        $result['candidates']['total_unapproved'] = $totalCandidate - $approved;
        $result['candidates']['invited'] = Candidate::invited($startDate, $endDate);
        $result['candidates']['suggested'] = Candidate::suggested($startDate, $endDate);
        $result['company']['activeClient'] = Company::getCompanyByCondition('status',$startDate, $endDate);
        $result['company']['all'] = Company::getCompanyByCondition(null, $startDate, $endDate);
        $result['company']['request']['all'] = Company::request(null,$startDate, $endDate);
        $result['company']['request']['delivered'] = Company::request('delivered', $startDate, $endDate);
        $result['payable']['total'] = $payableDetail['payable'];
        $result['payable']['amount'] = $payableDetail['amount'];

        $result['totalUniversitiesToFix'] = University::find()
            ->andWhere(new Expression('university_name_en IS NULL OR university_name_ar IS NULL OR 
                university_name_en = university_name_ar'))
            ->count();

        // Transfers
        $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK);
        $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT);

        $result['transfers'] = [];
        $result['transfers']['locked'] = [
            "code" => Transfer::STATUS_LOCK,
            "total" => (isset($lockedTransfers['total']))? (int)$lockedTransfers['total'] : 0
        ];

        $result['transfers']['paymentSent'] = [
            "code" => Transfer::STATUS_PAYMENT_SENT,
            "total" => (isset($paymentSentTransfers['total']))? (int)$paymentSentTransfers['total'] : 0
        ];

        $result['totalSalaryPaid'] = StaffSalary::find()->sum('salary');

        $result['absent'] = StaffLeave::find()
            ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"))
            ->count();

        $result['attended'] = StaffWorkSession::find()
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."')"))
            ->count();

        $totalStaff = Staff::find()
            ->andWhere(['staff_status' => Staff::STATUS_ACTIVE])
            ->count();

        $result['didnt_attended'] = $totalStaff - $result['attended'] - $result['absent'];

        return $result;
    }

    /**
     * transfer stats
     */
    public function actionTransfer() {

        $startDate = Yii::$app->request->get('start_date', null);
        $endDate = Yii::$app->request->get('end_date', null);

        $data = [];

        $data['totalTransferCandidate'] = TransferCandidate::find()
            ->joinWith(['transfer'])
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
            //->filterPaid()
            ->count();

        $totalPaymentAmountReceived = Transfer::find();            //ignore duplicate entries of child transfers
        $totalPaymentAmountReceived->andWhere('transfer.parent_transfer_id IS NULL');
        $totalPaymentAmountReceived->filterPaymentReceived();
        if($startDate) {
            $totalPaymentAmountReceived->andWhere(new Expression("DATE(transfer_created_at) >= DATE('" . $startDate . "')"));
        }
        if($endDate) {
            $totalPaymentAmountReceived->andWhere(new Expression("DATE(transfer_created_at) <= DATE('" . $endDate . "')"));
        }
        $data['totalPaymentAmountReceived'] = $totalPaymentAmountReceived->sum('company_total');



        $totalBelongingToCandidates = Transfer::find();//ignore duplicate entries of child transfers
        $totalBelongingToCandidates->andWhere('transfer.parent_transfer_id IS NULL');
            //->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
        $totalBelongingToCandidates->filterPaymentReceived();
        if($startDate) {
            $totalBelongingToCandidates->andWhere(new Expression("DATE(transfer_created_at) >= DATE('" . $startDate . "')"));
        }
        if($endDate) {
            $totalBelongingToCandidates->andWhere(new Expression("DATE(transfer_created_at) <= DATE('" . $endDate . "')"));
        }
        $data['totalBelongingToCandidates'] = $totalBelongingToCandidates->sum('total');



        $totalProfit = Transfer::find();
        $totalProfit->andWhere('transfer.parent_transfer_id IS NULL');//ignore duplicate entries of child transfers
        $totalProfit->filterPaymentReceived();
        if($startDate) {
            $totalProfit->andWhere(new Expression("DATE(transfer_created_at) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $totalProfit->andWhere(new Expression("DATE(transfer_created_at) <= DATE('" . $endDate . "')"));
        }

        $data['totalProfit'] =$totalProfit->sum('company_total - total');


        return $data;
    }
}
