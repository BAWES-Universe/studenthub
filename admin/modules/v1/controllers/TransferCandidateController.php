<?php

namespace admin\modules\v1\controllers;

use admin\models\Candidate;
use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Invoice;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use yii\filters\auth\HttpBearerAuth;
/**
 * Transfer controller - Manage Transfer
 */
class TransferCandidateController extends Controller
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
                'Access-Control-Request-Method' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'filename',
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
        $behaviors['authenticator']['except'] = ['options','text'];

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
            'collectionOptions' => ['POST'],
            'resourceOptions' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Transfer.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $tc_id          = Yii::$app->request->get('tc_id');
        $status         = Yii::$app->request->get('status');
        $candidate_id   = Yii::$app->request->get('candidate_id');

        $query = TransferCandidate::find();

        if ($status) {
            $query->totalPaymentStatus($status);
        }

        if ($candidate_id) {
            $query->filterCandidate($candidate_id);
        }

        if ($tc_id) {
            $query->filterByPrimaryKey($tc_id);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of all Payable Candidates with invoice status paid
     */
    public function actionPayableCandidates()
    {
        $result = [];

        // Candidates whose company paid to admin but admin have not paid yet
        $transfers = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer()
            ->all();

        foreach ($transfers as $transfer)
        {
            $candidates = $transfer->getTransferCandidates()
                ->with([
                    'candidate', 
                    'candidate.store', 
                    'candidate.company', 
                    'candidate.bank',
                    'candidate.university'
                ])        
                ->where(['paid' => '0'])
                ->all();

            if($candidates)
            {
                $totalAmount = Candidate::calculateRemainingPaymentTransferTotal($candidates);

                $result[] = [
                    'transfer_id' => $transfer->transfer_id,
                    'candidates' => $candidates,
                    'total' => $totalAmount
                ];
            }
        }

        return $result;
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $transfer = Transfer::find()
            ->with([
                'transferCandidates', 
                'transferCandidates.candidate', 
                'transferCandidates.candidate.store', 
                'transferCandidates.candidate.company', 
                'transferCandidates.candidate.bank',
                'transferCandidates.candidate.university'
            ])
            ->where([
                'transfer_id' => $id
            ])    
            ->one();

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found'
            ];
        }

        return $transfer;
    }

    /**
     * Mark Transfer as Payment Received
     * @param $id
     * @return array
     */
    public function actionPaymentReceivedDistributing($id)
    {
        $transfer = Transfer::findOne($id);

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        try{
            $transfer->paymentReceived();
        }
        catch(Exception $e){
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        Yii::info('[Transfer #'.$id.' marked as "Payment Received"] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        // Sending receipt to company via email
        $transfer->notify('receipt');

        return [
            "operation" => "success",
            "message" => 'Transfer marked as "Payment Received" successfully'
        ];
    }

    /**
     * Return Transfer by mark as Initiated from Lock
     * @param $id
     * @return array
     */
    public function actionUnlock($id)
    {
        $transfer = Transfer::findOne((int)$id);

        if(!$transfer)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        try {
            $transfer->unlock();
        }
        catch(Exception $e)
        {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        Yii::info('[Transfer #'.$id.' unlocked] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => 'Transfer unlocked successfully'
        ];
    }

    /**
     * Return Transfer by mark as Lock from Payment Sent
     * @param $id
     * @return array
     */
    public function actionLock($id)
    {
        $transfer = Transfer::findOne((int)$id);

        if(!$transfer)
        {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        try{
            $transfer->lock();
        }
        catch(Exception $e)
        {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        Yii::info('[Transfer #'.$id.' reverted to locked] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Transfer status reverted to locked as requested."
        ];
    }

    /**
     * Method linked with payable candidate
     * section option to mark all candidate at one time
     */
    public function actionMarkPaidAll()
    {
        $candidate_ids = Yii::$app->request->getBodyParam('candidates');
        $main_transfer_id = 0;

        foreach ($candidate_ids as $value)
        {
            TransferCandidate::updateAll(
                ['paid' => 1],
                ['candidate_id' => $value['candidate_id'], 'transfer_id' => $value['transfer_id']]
            );

            // Check if all paid, mark transfer as complete
            $unpaid = TransferCandidate::find()
                ->where([
                    'paid' => 0
                ])
                ->andWhere(['transfer_id' => $value['transfer_id']])
                ->count();

            if (!$unpaid) {
                $transfer = Transfer::findOne($value['transfer_id']);
                $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
                $transfer->save();
            }
        }

        Yii::info('[' . count($candidate_ids) . ' candidates have been marked as paid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            'operation' => 'success',
            'message' => count($candidate_ids). ' candidates have been marked as paid',
        ];
    }
}
