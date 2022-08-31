<?php

namespace status\modules\v1\controllers;

use common\models\Expense;
use Yii;
use admin\models\Candidate;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use admin\models\University;
use common\models\StaffSalary;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;


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
        $payableDetail = Candidate::getTotalPayableCandidate();
        // Candidates
        $totalCandidate = Candidate::candidateCountByCondition();
        $totalAssignedToWork = Candidate::candidateCountByCondition('assigned');
        $approved = Candidate::candidateCountByCondition('approved');

        $result['candidates']['total_candidate'] = $totalCandidate;
        $result['candidates']['total_assigned'] = $totalAssignedToWork;
        $result['candidates']['total_unapproved'] = $totalCandidate - $approved;
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

        $result['totalSalaryPaid'] = (double) fStaffSalary::find()->sum('salary');

        $result['totalTransferCandidate'] = TransferCandidate::find()
            ->joinWith(['transfer'])
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
            //->filterPaid()
            ->count();

        $result['totalPaymentAmountReceived'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->filterPaymentReceived()
            ->sum('company_total');

        $result['totalBelongingToCandidates'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            //->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
            ->filterPaymentReceived()
            ->sum('total');

        $result['totalProfit'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->filterPaymentReceived()
            ->sum('company_total - total');

        $result['totalExpense'] = (double) Expense::find()
            ->sum('amount');

        $result['totalNetProfit'] = (double) $result['totalProfit'] - $result['totalExpense'] - $result['totalSalaryPaid'];

        return $result;
    }

    /**
     * transfer stats
     */
    public function actionTransfer() {

        $data = [];

        $data['totalTransferCandidate'] = TransferCandidate::find()
            ->joinWith(['transfer'])
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
            //->filterPaid()
            ->count();

        $data['totalPaymentAmountReceived'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->filterPaymentReceived()
            ->sum('company_total');

        $data['totalBelongingToCandidates'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            //->andWhere(['!=', 'transfer_status', Transfer::STATUS_INITIATED])//no draft
            ->filterPaymentReceived()
            ->sum('total');

        $data['totalProfit'] = Transfer::find()
            //ignore duplicate entries of child transfers
            ->andWhere('transfer.parent_transfer_id IS NULL')
            ->filterPaymentReceived()
            ->sum('company_total - total');

        $data['graphData'] = Transfer::getTotalsByMonths(12);

        return $data;
    }
}