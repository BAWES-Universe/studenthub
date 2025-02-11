<?php

namespace admin\modules\v1\controllers;

use common\models\Candidate;
use common\models\Loan;
use Yii;
use yii\rest\Controller;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


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
            'class' => \yii\filters\Cors::class,
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
            'class' => HttpBearerAuth::class,
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

    public function actionPayableCandidatesStats()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->filterUnpaid()
            ->joinWith(['transfer'])
            ->andWhere(['transfer.transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->andWhere('transfer.parent_transfer_id IS NULL');

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

        return [
            "totalUnpaid" => (double) (clone $query)->sum('candidate_total'),
            "totalPayable" => (double) (clone $query)->havingBankInfo()
                ->activeCivilId()
                ->completeProfile()
                ->sum('candidate_total'),
            "totalOfMissingBankInfo" =>
                (double) (clone $query)->missingBankInfo()
                    ->sum('candidate_total'),
            "totalOfExpiredCivil" =>
                (double) (clone $query)->civilIdExpired()
                    ->sum('candidate_total'),
            "totalOfIncompleteProfile" => (double) (clone $query)
                ->incompleteProfile()
                ->sum('candidate_total')
        ];
    }

    public function actionPayableCandidates()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");
        $searchName = Yii::$app->request->get("searchName");
        $candidateTransferStatus = Yii::$app->request->get("candidateTransferStatus");

        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->filterUnpaid()
            ->joinWith(['transfer'])
            ->andWhere(['transfer.transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->andWhere('transfer.parent_transfer_id IS NULL');

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

        if ($searchName) {
            $query->joinWith(['candidate'])
                ->andWhere([
                    "OR",
                    ['like', 'candidate.candidate_name', $searchName],
                    ['like', 'candidate.candidate_name_ar', $searchName]
                ]);
        }

        switch ($candidateTransferStatus) {
            case "active-profile":
                $query->havingBankInfo()
                    ->activeCivilId()
                    ->completeProfile();
                break;
            case "missing-bank-info":
                $query->missingBankInfo();
                break;
            case  "civil-expired":
                $query->civilIdExpired();
                break;
            case "incomplete-profile":
                $query->incompleteProfile();
                break;
            default:
                break;
        }

        return new \yii\data\ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Transfer.
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");
        $tc_id = Yii::$app->request->get('tc_id');

        $transfer_confirmation_id = Yii::$app->request->get('transfer_confirmation_id');

        $query = TransferCandidate::find()
            ->with('candidate')
            ->payableWithPaid();

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($tc_id) {
            $transferCandidateRecords = array_diff(explode(",", $tc_id),[""]);
            $query->andWhere(['in', 'tc_id', $transferCandidateRecords]);
        }
        
        if($transfer_confirmation_id) {
            $query->andWhere(['transfer_confirmation_id' => $transfer_confirmation_id]);
        }
        
        return new \yii\data\ActiveDataProvider([
            'pagination' => false,
            'query' => $query
        ]);
    }

    /**
     * load candidate transfer entries by transfer id
     * @param number $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionByTransfer($id) 
    {
        $transfer = $this->findTransfer($id);
        
        $query = $transfer->getTransferCandidates()
            ->orderBy('transfer_candidate.store_id');//to group it by store on infinite scrolling listing
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * load candidate transfer entries by transfer file id
     * @param number $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionByTransferFile($id) 
    {
        $transfer = $this->findTransferFile($id);
        
        $query = $transfer->getTransferCandidates()
            ->orderBy('transfer_candidate.store_id');//to group it by store on infinite scrolling listing
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * view candidate transfer detail
     * @param type $id
     * @return type
     */
    public function actionView($id)
    { 
        $model =  TransferCandidate::find()
            ->with('candidate')
            ->andWhere(['tc_id' => $id])
            ->payableWithPaid()
            ->one();
         
        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        
        return $model;
    }

    public function actionReplace($id) {

        $model =  TransferCandidate::find()
            ->andWhere(['tc_id' => $id])
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->prev_candidate_id = $model->candidate_id;
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        $candidate = Candidate::find()
            ->andWhere(['candidate_id' => $model->candidate_id])
            ->one();

        if(!$candidate) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->bank_id = $candidate->bank_id;
        $model->transfer_benef_name = $candidate->bank_account_name;
        $model->transfer_benef_iban = $candidate->candidate_iban;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        //reload

        $model =  TransferCandidate::find()
            ->andWhere(['tc_id' => $id])
            ->one();

        return [
            "operation" => "success",
            "transfer" => $model,
            "candidate" => [
                "candidate_personal_photo" => $model->candidate->candidate_personal_photo,
                "candidate_name" => $model->candidate->candidate_name,
                "candidate_name_ar" => $model->candidate->candidate_name_ar,
                "candidate_id" => $model->candidate_id,
                "bank_id" => $model->candidate->bank_id,
                "civilExpired" => $model->candidate->candidate_civil_expiry_date && (strtotime($model->candidate->candidate_civil_expiry_date) <
                        strtotime(date('Y-m-d'))),
                "isProfileCompleted" => $model->candidate->isProfileCompleted()
            ]
        ];
    }

    /**
     * @param $id
     * @return array
     */
    public function actionUnpaid($id)
    {
        return TransferCandidate::markUnpaid($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionPaid($id)
    {
        $transfer_confirmation_id = Yii::$app->request->getBodyParam('transfer_confirmation_id');

        return TransferCandidate::markPaid($id, $transfer_confirmation_id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionPayByWallet($id)
    {
        //todo: make sure wallet/customer's profile currency is same as transfer currency

        $transfer_confirmation_id = Yii::$app->request->getBodyParam('transfer_confirmation_id');
        $initTransfer = Yii::$app->request->getBodyParam('init_transfer');

        return TransferCandidate::markPaid($id, $transfer_confirmation_id, true, $initTransfer);
    }

    /**
     * @return array
     */
    public function actionMarkPaidAll()
    {
        $transferCandidateIds = Yii::$app->request->getBodyParam('transferCandidate');

        return TransferCandidate::markAllPaid($transferCandidateIds);
    }

    /**
     * @return array
     */
    public function actionMarkUnpaidAll()
    {
        $transferCandidateIds = Yii::$app->request->getBodyParam('transferCandidate');
        return TransferCandidate::markAllUnpaid($transferCandidateIds);
    }
    
    /**
     * Finds the Transfer file model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TransferFile the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTransferFile($id)
    {
        if (($model = \admin\models\TransferFile::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    /**
     * Finds the Transfer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTransfer($id)
    {
        if (($model = Transfer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
