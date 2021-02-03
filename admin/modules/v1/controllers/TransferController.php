<?php

namespace admin\modules\v1\controllers;


use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Invoice;
use common\models\Admin;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use company\models\TranferExcel;
use kartik\mpdf\Pdf;
use yii\web\NotFoundHttpException;

/**
 * Transfer controller - Manage Transfer
 */
class TransferController extends Controller
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
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
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
     */
    public function actionList()
    {
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = Transfer::find()
            ->isParentTransfer();

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * list invoices
     * @param type $id
     * @return ActiveDataProvider
     */
    public function actionInvoices($id) 
    {
        $transfer = $this->findModel($id);
        
        return new ActiveDataProvider([
            'query' => $transfer->getInvoices(),
            'pagination' => false
        ]);
    }

    /**
     * Return a List of all Payable Candidates with invoice status paid
     */
    public function actionPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet
        $query = Transfer::find()
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->isParentTransfer();
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
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
            //    'transferCandidates.candidate.store', 
            //    'transferCandidates.candidate.company', 
            //    'transferCandidates.candidate.bank',
            //    'transferCandidates.candidate.university'
            ])
            ->where([
                'transfer_id' => $id
            ])    
            ->one();

        if(!$transfer) {
            throw new NotFoundHttpException('The requested page does not exist.');
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
        $transfer = $this->findModel($id);

        try {
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
        $transfer = $this->findModel((int)$id);

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
        $transfer = $this->findModel((int)$id);

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
     * import bank excel to extract candidate data
     * @return type
     */
    public function actionImportExcel() {
    
        $model = new TranferExcel;        
        $model->excel = Yii::$app->request->getBodyParam('excel');
        
        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }
        
        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($model->excel); 

        //save in temp folder to process 
        
        $tmpFile = sys_get_temp_dir() . '/' . $model->excel;
                
        if(!file_put_contents($tmpFile, file_get_contents($fileUrl))) { 
            return [
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];
        } 

        $excelData  = \moonland\phpexcel\Excel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row 
        
        \yii\helpers\ArrayHelper::remove($excelData, '1');

        //second row will be key 
        
        $keys = \yii\helpers\ArrayHelper::remove($excelData, '2');

        //create array with key to read data 
        
        $data = [];

        foreach ($excelData as $values)
        {
            $data[] = array_combine($keys, $values);
        } 
            
        //no need file anymore 
        
        @unlink($tmpFile);
        
        //remove empty rows 

        $total = 0;
                
        $candidatesTransfers = [];

        foreach ($data as $key => $value) 
        {
            if(empty($value['Status'])) {
                return [
                    'operation' => 'error',
                    'message' => 'Invalid excel',
                    'errorCode' => 1
                ];
            }
            
            if($value['Status'] == 'FAIL')
                continue;

            $transferCandidate = TransferCandidate::find()->where(['tc_id' => $value['Credit Narrative']])->one();
            
            if(!$transferCandidate || !$transferCandidate->candidate) {
                return [
                    'operation' => 'error',
                    'message' => 'Invalid excel',
                    'errorCode' => 2
                ];
            }
                    
            $candidatesTransfers[] = [
                'transfer_confirmation_id' => $value['Status Description'], 
                'transfer_id' => $value['Debit Narrative'],  
                'tc_id' => $value['Credit Narrative'],  
                'candidate_id' => $transferCandidate->candidate->candidate_id, 
                'candidate_name' => $transferCandidate->candidate->candidate_name, 
                'total_amount' => $transferCandidate->totalPaidToCandidate 
            ];
            
            $total += $transferCandidate->totalPaidToCandidate;
        }
        
        return [
            'total' => $total,
            'candidates' => $candidatesTransfers
        ];
    }
    
    /**
     * Method linked with payable candidate
     * section option to mark all candidate at one time
     */
    public function actionMarkPaidAll()
    {
        $candidate_ids = Yii::$app->request->getBodyParam('candidates');
      
        if(!is_array($candidate_ids) || sizeof($candidate_ids) == 0) {
            return [
                'operation' => 'error',
                'message' => 'Invalid request'
            ];
        }
        
        $model = new TranferExcel;        
        $model->excel = Yii::$app->request->getBodyParam('excel');
        
        //validate given excel 
        
        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }
        
        //save file used to mark transfers as paid 
         
        $transaction = Yii::$app->db->beginTransaction();

        try {
                
            //save file used to mark transfers as paid 
             
            $tc_ids = \yii\helpers\ArrayHelper::getColumn($candidate_ids, 'tc_id');
            
            $transfer_file_id = \common\models\TransferFile::saveFile($tc_ids, $model->excel);
            
            if(!$transfer_file_id) {
                return [
                    "operation" => "error",
                    "message" => 'Error on trying to save transfer file'
                ];
            }

            //mark candidates as paid 
            
            $transferCandidates = TransferCandidate::find()
                ->filterWhere(['in', 'tc_id', $tc_ids])
                ->all();
            
            $transferCandidatesMapped = \yii\helpers\ArrayHelper::index($transferCandidates, 'tc_id');
            
            foreach ($candidate_ids as $value)
            {
                if(empty($transferCandidatesMapped[$value['tc_id']]))
                {
                    return [
                        "operation" => "error",
                        'message' => 'Invalid request'
                    ];
                }
                
                $tc = $transferCandidatesMapped[$value['tc_id']];
                
                $tc->paid = 1;
                $tc->transfer_file_id = $transfer_file_id;
                $tc->transfer_confirmation_id = $value['transfer_confirmation_id'];
                
                if(!$tc->save())
                {
                    return [
                        "operation" => "error",
                        "message" => $tc->getErrors()
                    ];
                }
            }

            // Check if all paid, mark transfer as complete

            $transfer_ids = array_unique(
                \yii\helpers\ArrayHelper::getColumn($candidate_ids, 'transfer_id')
            );
            
            foreach($transfer_ids as $transfer_id) {
                Transfer::markTransferCompleteOnCandidatePaid($transfer_id);
            }

            $transaction->commit();

            Yii::info('[' . count($candidate_ids) . ' candidates have been marked as paid]  By '.Yii::$app->user->identity->admin_name, __METHOD__);

        } catch (\Exception $e) {
            $transaction->rollBack();
            
            return [
                "operation" => "error",
                'message' => 'Invalid request',
                'error' => $e
            ];

        } catch (\Throwable $e) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                'message' => 'Invalid request',
                'error' => $e
            ];
        }


        return [
            'operation' => 'success',
            'message' => count($candidate_ids). ' candidates have been marked as paid',
        ];
    }

    /**
     * Return a Excel Containing Payable Candidates
     */
    public function actionExportPayableCandidates()
    {
        $payableCandidate = [];
        $onlyPayable = Yii::$app->request->get('only-payable');
        
        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->payable();

        if($onlyPayable) {
            $query->andWhere(new \yii\db\Expression('transfer_candidate.bank_id IS NOT NULL'));    
        }
        
        $candidates = $query
            ->all();

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile
        foreach ($candidates as $candidate) {
            if (
                $candidate->candidate->isProfileCompleted &&
                $candidate->candidate->bank_id &&
                $candidate->transfer_benef_iban &&
                $candidate->transfer_benef_name &&
                $candidate->invoiceNumber) {
                $payableCandidate[] = $candidate;
            }
        }

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $payableCandidate,
            'columns' => [
                'tc_id',
                'transfer_id',
                'candidate_id',
                'candidate.candidate_name',
                [
                    'attribute'=>'Beneficiary name',
                    'label'=>'Beneficiary name',
                    'value'=>function($data) {
                        return $data->candidate->bank_account_name;
                    }
                ],
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'hours',
                'candidate_hourly_rate',
                [
                    'attribute'=>'bonus',
                    'label'=>'Candidate Bonus',
                    'value' => function($data){
                        return $data->bonus - $data->bonus_commission;
                    }
                ],
                'transfer_cost',
                [
                    'attribute'=>'candidate_total',
                    'value' => function($data){
                        return $data->totalPaidToCandidate;
                    }
                ],
                'candidate.candidate_iban',
                'candidate.bank.bank_name'
            ]
        ]);
    }

    /**
     * method to generate text file for all unpaid candidates
     * @return array
     */
    public function actionText()
    {
        $s1 = 'S1,11622216,,MXD,M,,'.date('d/m/Y').','.date('dmY').'-01'.PHP_EOL; // header line
        $s2 = '';

        $candidates = TransferCandidate::getPayableCandidateListFormat();

        if(!$candidates) {
            return [
                "operation" => "error",
                "message" => 'No Payable Candidates!'
            ];
        }

        foreach ($candidates['candidate_list'] as $detail) {
            $s2 .=  implode(',',$detail).",".PHP_EOL;
        }

        $s3 = 'S3,'.count($candidates['candidate_list']).','.$candidates['total_amount']; // Footer
        $sAll = $s1.$s2.$s3;

        $fileName = 'BAWS-PAY-'.date('dmY').'-01.txt';

        $path = sys_get_temp_dir() .DIRECTORY_SEPARATOR. $fileName;

        $handle = fopen($path, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        Yii::$app->response->headers->add('filename', $fileName);

        return Yii::$app->response->sendFile($path);
    }

    /**
     * Export Transfer detail as Excel
     * @param $id
     * @return array
     */
    public function actionExport($id)
    {
        $transfer = $this->findModel((int)$id);

        $candidates = TransferCandidate::find()
            ->candidatesByTransfer($id)
            ->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                [
                    'attribute'=>'Candidate Name',
                    'value'=>function($data) {
                        return $data->candidate->candidate_name;
                    }
                ],
                [
                    'attribute'=>'Beneficiary name',
                    'label'=>'Beneficiary name',
                    'value'=>function($data) {
                        return $data->candidate->bank_account_name;
                    }
                ],
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'hours',
                'candidate_hourly_rate',
                [
                    'attribute'=>'bonus',
                    'label'=>'Candidate Bonus',
                    'value' => function($data){
                        return $data->bonus - $data->bonus_commission;
                    }
                ],
                'transfer_cost',
                [
                    'attribute'=>'candidate_total',
                    'value'=>function($data) {
                        return $data->totalPaidToCandidate;
                    }
                ],
                'candidate.candidate_iban',
                [
                    'attribute'=>'Bank Name',
                    'label'=>'Bank Name',
                    'value'=>function($data) {
                        if($data->candidate->bank)
                            return $data->candidate->bank->bank_name;
                    }
                ],
                [
                    'attribute' => 'paid',
                    'value' => function($model) {
                        if($model->paid)
                            return 'Yes';
                        else
                            return 'No';
                    },
                ],
            ]
        ]);
    }

    /**
     * Download Transfer as PDF
     * @param $id
     * @param $type
     * @return array|mixed
     */
    public function actionPdf($id, $type)
    {
        $invoice = Invoice::find()
            ->withTransfer($id)
            ->one();

        if(!$invoice) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        $status = $invoice->transfer->parentTransfer ?
            $invoice->transfer->parentTransfer->transfer_status: $invoice->transfer->transfer_status;

        if(
            Yii::$app->user->identity->admin_limited_access == Admin::ACCESS_LIMITED &&
            $invoice->transfer &&
            in_array(
                $status,
                [Transfer::STATUS_PAYMENT_SENT,Transfer::STATUS_LOCK,Transfer::STATUS_INITIATED]
            )
        ) {
            return [
                "operation" => "error",
                "message" => 'Transfer Not available for download',
            ];
        }

        $this->layout = 'pdf';
        $content = $this->render(($type == 'receipt') ? 'receipt' : 'invoice', [
            'invoice' => $invoice,
        ]);

        $pdf = new Pdf([
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:38px}',
            // set mPDF properties on the fly
            'options' => [],//['title' => 'Booking #'.$id],
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }
    
    /**
     * Finds the Transfer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Transfer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
