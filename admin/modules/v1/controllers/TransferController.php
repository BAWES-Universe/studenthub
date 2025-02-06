<?php

namespace admin\modules\v1\controllers;


use common\models\TransferBankAdvice;
use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Invoice;
use admin\models\Admin;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use company\models\TranferExcel;
use kartik\mpdf\Pdf;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;


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
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $suspicious = Yii::$app->request->get('suspicious');

        $query = Transfer::find()
            ->isParentTransfer();

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
            $query->orFilterWhere(['LIKE','{{%transfer}}.transfer_id',$company_name]);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($suspicious) {
            $query->filterSuspicious();
        }

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
     * update transfer total from transfer file entry
     */
    public function actionUpdateTransferFromFile($id)
    {
        $transfer = $this->findModel ($id);

        $transaction = Yii::$app->db->beginTransaction ();

        $total = 0;

        foreach ($transfer->transferCandidates as $transferCandidate) {

            //calculate hourly rate used in transfer

            $candidate_hourly_rate = null;

            if($transferCandidate->transferFileEntry) {
                $candidate_hourly_rate = (
                        $transferCandidate->transferFileEntry->credit_amount -
                        $transferCandidate->bonus + $transferCandidate->bonus_commission
                    ) / $transferCandidate->hours +
                    ( (double) $transferCandidate->minutes / 60.0) +
                    ( (double) $transferCandidate->seconds / 3600);

                //- $transferCandidate['transfer_cost']
            }

            //if not processed + having same store

            if(!$candidate_hourly_rate && $transferCandidate->store_id == $transferCandidate->candidate->store_id) {
                $candidate_hourly_rate = $transferCandidate->candidate->candidate_hourly_rate;
            }

            //if store updated, keep old hourly rate

            if($candidate_hourly_rate) {
                $transferCandidate->candidate_hourly_rate = $candidate_hourly_rate;
            }

            if ((int)$transferCandidate['minutes'] > 0 || (int)$transferCandidate['seconds'] > 0 ||
                (int)$transferCandidate['hours'] > 0 || $transferCandidate['bonus'] > 0) {

                $transferCandidate->candidate_total = $transferCandidate['bonus'] - $transferCandidate['bonus_commission']
                    + ($transferCandidate['hours'] * $transferCandidate->candidate_hourly_rate)
                    + ($transferCandidate['minutes'] * ($transferCandidate->candidate_hourly_rate / 60))
                    + ($transferCandidate['seconds'] * ($transferCandidate->candidate_hourly_rate / 3600));
                    //+ $transferCandidate['transfer_cost'];

                //total amount we will pay to bank
                $total += $transferCandidate->candidate_total;
            }

            if (!$transferCandidate->save ()) {

                $transaction->rollBack ();

                return [
                    'operation' => 'error',
                    'message' => 'Error updating hourly rate for transfer candidate #' . $transferCandidate->tc_id
                ];
            }
        }

        $transfer->total = $total;

        if(!$transfer->save()) {
            $transaction->rollBack ();

            return [
                'operation' => 'error',
                'message' => 'Error updating total for transfer #' . $transfer->transfer_id
            ];
        }

        $transaction->commit ();

        return [
            'operation' => 'success',
            'message' => 'Transfer updated from transfer files #' . $transfer->transfer_id
        ];
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        // Candidates whose company paid to admin but admin have not paid yet
        $query = Transfer::find()
            ->andWhere(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->isParentTransfer();

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

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
            ->andWhere([
                'transfer_id' => $id
            ])    
            ->one();

        if(!$transfer) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $transfer;
    }

    /**
     * Cancel transfer
     * @param $id
     * @return array
     */
    public function actionCancel($id)
    {
        $transfer = $this->findModel ($id);

        try{
            $transfer->cancel();
        }
        catch(\Exception $e)
        {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        $info = '[Admin '.Yii::$app->user->identity->admin_name.' has cancel Transfer #'.$id.' ] ';
        $info .= 'for Company '. $transfer->company->company_name;

        Yii::info($info, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Transfer has been cancelled."
        ];
    }

    /**
     * Delete transfer with "Initiated" or "Locked" status
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel ($id);

        //delete data + child transfer
        if(Transfer::deleteTransfer($model))
        {
            $info = '[ Admin '.Yii::$app->user->identity->admin_name.' has Deleted Transfer #'.$id.' ] ';
            $info .= '[ for Company '. $model->company->company_name.'] ';
            $info .= 'Check for reason and ask if they require assistance.';

            Yii::info($info, __METHOD__);

            return [
                "operation" => "success",
                "message" => 'Transfer deleted as requested.'
            ];
        }

        return [
            "operation" => "error",
            "message" =>'Transfer status should be "Initiated" or "Locked" to delete it!'
        ];
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

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track(
                'Transfer Marked As Payment Received',
                [
                        'transfer_id' => $id,
                        'total' => $transfer->total,
                        'company_total' => $transfer->company_total,
                        'revenue' => $transfer->company_total - $transfer->total,
                        'currency' => $transfer->currency_code
                ]);

            Transfer::triggerPayableCandidateEvent();
        }

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
        $transfer = $this->findModel($id);

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

        if(YII_ENV == 'prod') {

//            Yii::$app->eventManager->track('Transfer UnLocked',
//                [
//                    'transfer_id' => $id
//                ]);
        }

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
        $transfer = $this->findModel($id);

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

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track(
                'Transfer Locked', [
                    'transfer_id' => $id
                ]);
        }

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

        $excelData  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        \yii\helpers\ArrayHelper::remove($excelData, '1');

        //second row will be key

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '2');

        if(empty($keys)) {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];
        }

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

            if($value['Status'] == 'FAIL') {

                $transferCandidate = TransferCandidate::find()
                    ->andWhere(['tc_id' => $value['Credit Narrative']])
                    ->one();

                if($transferCandidate && $transferCandidate->candidate) {

                    $transferCandidate->paid = TransferCandidate::UNPAID;
                    $transferCandidate->transfer_benef_iban = null;
                    $transferCandidate->transfer_benef_name = null;
                    $transferCandidate->bank_id = null;

                    if ($transferCandidate->save(false)) {

                        $transferCandidate->candidate->bank_id = null;
                        $transferCandidate->candidate->bank_account_name = null;
                        $transferCandidate->candidate->candidate_iban = null;
                        if ($transferCandidate->candidate->save(false)) {
                            $transferCandidate->unpaidNotification();
                        }
                    }
                }
            }

            if($value['Status'] == 'SUCCESS')
            {
                $transferCandidate = TransferCandidate::find()
                    ->andWhere(['tc_id' => $value['Credit Narrative']])
                    ->one();

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
                    "tc_created_at" => $transferCandidate->tc_created_at,
                    'candidate_name' => $transferCandidate->candidate->candidate_name,
                    "candidate_id" => $transferCandidate->candidate_id,
                    'total_amount' => $transferCandidate->totalPaidToCandidate,
                    "currency_code" => $transferCandidate->currency_code
                ];

                $total += $transferCandidate->totalPaidToCandidate;
            }
        }

        return [
            'operation' => 'success',
            'total' => $total,
            "bank" => "AUB",
            'candidates' => $candidatesTransfers
        ];
    }

    /**
     * @return void
     */
    public function actionImportBankStatementExcel() {

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 1,
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
                "errorCode" => 2,
                "message" => "Error reading file"
            ];
        }

        $excelData  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove 7 title row

        for ($i = 1; $i < 8; $i++) {
            \yii\helpers\ArrayHelper::remove($excelData, $i);
        }

        //8th row will be key

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '8');

        if(empty($keys)) {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 3,
                "message" => "Error reading file"
            ];
        }

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

        $errors = [];

        foreach ($data as $key => $value)
        {
            // Initialize a variable to store the extracted number
            $tc_id = null;

            //extract candidate transfer id

            // Define the regex pattern to match a number after "SALARY"
            $pattern = '/SALARY\s+(\d+)/';

            // Perform the regex match
            if (preg_match($pattern, $value['Description'], $matches)) {
                // The first capturing group contains the number after "SALARY"
                $tc_id = $matches[1];
            }

            // Define the regex pattern to match a number after "SALARY"
            if (!$tc_id) {
                $pattern = '/S\s+(\d+)/';

                // Perform the regex match
                if (preg_match($pattern, $value['Description'], $matches)) {
                    // The first capturing group contains the number after "SALARY"
                    $tc_id = $matches[1];
                }
            }

            if (!$tc_id) {
                continue;
            }

            $transferCandidate = TransferCandidate::find()
                ->andWhere([
                    'tc_id' => $tc_id,
                    "paid" => 0
                ])
                ->one();

                if(!$transferCandidate) {
                    /*return [
                        'operation' => 'error',
                        'message' => "No unpaid transfer found with ID: " . $tc_id . ". Statement Description: ". $value['Description'],
                        'errorCode' => 4
                    ];*/

                    $errors[]  = "No unpaid transfer found with ID: " . $tc_id . ". Statement Description: ". $value['Description'];

                    continue;
                }

                if (!$transferCandidate->candidate) {
                    /*return [
                        'operation' => 'error',
                        'message' => "No candidate profile found with ID: " . $tc_id . ". Statement Description: ". $value['Description'],
                        'errorCode' => 4
                    ];*/

                    $errors[]  = "No candidate profile found with ID: " . $tc_id . ". Statement Description: ". $value['Description'];

                    continue;
                }

                //get reference number
                //example: IB/LOCAL TRANSFER/O-000004206364/MARIAN AKRAM MAGDY HABIB/BILL SETTLEMENT/SALARY 88467 000004206364

                $data = $value['Description']? explode("/", $value['Description']): $value['Description'];

                $candidatesTransfers[] = [
                    'transfer_confirmation_id' => isset($data[2])? $data[2]: $data[0],
                    "paid" => $transferCandidate->paid,
                    'transfer_id' => $transferCandidate->transfer_id,
                    'tc_id' => $transferCandidate->tc_id,
                    "tc_created_at" => $transferCandidate->tc_created_at,
                    'candidate_name' => $transferCandidate->candidate->candidate_name,
                    "candidate_id" => $transferCandidate->candidate_id,
                    'total_amount' => (float) $transferCandidate->totalPaidToCandidate,
                    "currency_code" => $transferCandidate->currency_code,
                    "debited_amount" =>  isset($value['Debit']) ? (float)$value['Debit']: null,
                    "credited_amount" => isset($value['Credit']) ? (float) $value['Credit']: null,
                   // "error" =>
                ];

                $total += $transferCandidate->totalPaidToCandidate;
        }

        if (sizeof($candidatesTransfers)  == 0) {
            return [
                'operation' => 'error',
                'message' => 'Invalid excel',
                'errorCode' => 5
            ];
        }

        return [
            'operation' => 'success',
            'total' => $total,
            "bank" => "Bank Statement",
            'candidates' => $candidatesTransfers,
            "errors" => $errors
        ];
    }

    /**
     * import KFH bank excel to extract candidate data
     * @return type
     */
    public function actionImportKfhExcel() {
    
        $model = new TranferExcel;        
        $model->excel = Yii::$app->request->getBodyParam('excel');
        
        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 1,
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
                "errorCode" => 2,
                "message" => "Error reading file"
            ];
        } 

        $excelData  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove 8 title row

        for ($i = 1; $i < 9; $i++) {
            \yii\helpers\ArrayHelper::remove($excelData, $i);
        }

        //9th row will be key
        
        $keys = \yii\helpers\ArrayHelper::remove($excelData, '9');

        if(empty($keys)) {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 3,
                "message" => "Error reading file"
            ];
        }

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
            if(empty($value['Refrence Number'])) {
                continue;//ignore empty values
            }



            /* --------------- not having status on this bank's excel -----------------------

            if($value['Status'] == 'FAIL') {

                $transferCandidate = TransferCandidate::find()
                    ->andWhere(['tc_id' => $value['Credit Narrative']])
                    ->one();

                if($transferCandidate && $transferCandidate->candidate) {
                    
                    $transferCandidate->paid = TransferCandidate::UNPAID;
                    $transferCandidate->transfer_benef_iban = null;
                    $transferCandidate->transfer_benef_name = null;
                    $transferCandidate->bank_id = null;

                    if ($transferCandidate->save(false)) {

                        $transferCandidate->candidate->bank_id = null;
                        $transferCandidate->candidate->bank_account_name = null;
                        $transferCandidate->candidate->candidate_iban = null;
                        if ($transferCandidate->candidate->save(false)) {
                            $transferCandidate->unpaidNotification();
                        }
                    }
                }
            }*/

            // assuming every row showing successful transfer
            //if($value['Status'] == 'SUCCESS')
            //{
                $transferCandidate = TransferCandidate::find()
                    ->andWhere([
                        'transfer_benef_iban' => $value['Beneficiary Account'],
                        "candidate_total" => $value['Amount'],
                        "currency_code" => $value['Transfer Currency'], // good to have filter, if same bank account in 2 country
                        "paid" => 0
                    ])
                    // having latest transfern as can have same bank account (for duplicate profile), same amount + currency (for previous month's transfer),
                    ->orderBy("tc_id DESC")
                    ->one();

                if(!$transferCandidate) {

                    return [
                        'operation' => 'error',
                        'message' => "No unpaid transfer found with Beneficiary Account: " . $value['Beneficiary Account'].
                            " Amount: " . $value['Amount Deducted'],
                        'errorCode' => 4
                    ];
                }

                if (!$transferCandidate->candidate) {
                    return [
                        'operation' => 'error',
                        'message' => "No candidate profile found with Beneficiary Account: " . $value['Beneficiary Account'].
                            " Amount: " . $value['Amount Deducted'],
                        'errorCode' => 4
                    ];
                }

                $candidatesTransfers[] = [
                    'transfer_confirmation_id' => $value['Refrence Number'],
                    "paid" => $transferCandidate->paid,
                    'transfer_id' => $transferCandidate->transfer_id,
                    'tc_id' => $transferCandidate->tc_id,
                    "tc_created_at" => $transferCandidate->tc_created_at,
                    'candidate_name' => $transferCandidate->candidate->candidate_name,
                    "candidate_id" => $transferCandidate->candidate_id,
                    'total_amount' => $value['Amount'],//$transferCandidate->totalPaidToCandidate,
                    "currency_code" => $value['Transfer Currency'], //$transferCandidate->currency_code
                ];

                $total += $transferCandidate->totalPaidToCandidate;
            //}
        }

        if (sizeof($candidatesTransfers)  == 0) {
            return [
                'operation' => 'error',
                'message' => 'Invalid excel',
                'errorCode' => 5
            ];
        }

        return [
            'operation' => 'success',
            'total' => $total,
            "bank" => "KFH",
            'candidates' => $candidatesTransfers
        ];
    }

    /**
     * @return void
     * @throws ServerErrorHttpException
     * @throws \yii\db\Exception
     */
    public function actionExportGoogleExcel() {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $payableCandidate = [];
        $onlyPayable = Yii::$app->request->get('only-payable');

        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->payable();

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($onlyPayable) {
            $query->havingBankInfo()
                ->activeCivilId();
        }

        $candidates = $query
            ->all();

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile

        if ($onlyPayable) {
            //todo: use batch function to lower memory usage?
            foreach ($candidates as $candidate) {
                if (
                    $candidate->candidate &&
                    $candidate->candidate->isProfileCompleted &&
                    $candidate->candidate->bank_id &&
                    $candidate->transfer_benef_iban &&
                    $candidate->transfer_benef_name &&
                    $candidate->invoiceNumber
                ) {
                    $payableCandidate[] = $candidate;
                }
            }
        } else {
            $payableCandidate = $candidates;
        }

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.xlsx';

        $savePath = sys_get_temp_dir();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $payableCandidate,
            'savePath' => $savePath,
            'fileName' => $fileName,
            'columns' => [
                [
                    "attribute" => 'transfer_id',
                    "label" => "Transfer ID"
                ],
                [
                    "attribute" => 'tc_id',
                    "label" => "Candidate Transfer ID"
                ],
                [
                    "attribute" => 'candidate_id',
                    "label" => "Candidate ID"
                ],
                [
                    "attribute" => "candidate_total",
                    "label" => "Candidate Total"
                ],
                [
                    "attribute" => "currency_code",
                    "label" => "Currency Code"
                ],
                [
                    "label" => 'Paid',
                    "attribute" => "paid",
                    "value" => function() {
                        return "Yes";
                    }
                ],
                'Refrence Number',
            ]
        ]);

        // Save to S3

        $path = $savePath .DIRECTORY_SEPARATOR. $fileName;

        $s3Response = $transferBankAdvice->saveExcelFile($path, $fileName);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
            //     var_dump($transferBankAdvice->errors);
            //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        Yii::$app->response->sendFile($path);

        // Delete the file
        if (!unlink($path)) {
            Yii::error("File could not be deleted");
        }
    }

    /**
     * import manually generated excel
     * @return array
     */
    public function actionImportGoogleExcel() {

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 1,
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
                "errorCode" => 2,
                "message" => "Error reading file"
            ];
        }

        $excelData  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //1st row will be key

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '1');

        if(empty($keys)) {
            return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 3,
                "message" => "Error reading file"
            ];
        }

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
            if(empty($value['Reference Number']) || !in_array(trim($value['Paid']), ["Yes", "YES"])) {
                continue;//ignore empty values
            }

            /* --------------- not having status on this bank's excel -----------------------

            if($value['Status'] == 'FAIL') {

                $transferCandidate = TransferCandidate::find()
                    ->andWhere(['tc_id' => $value['Credit Narrative']])
                    ->one();

                if($transferCandidate && $transferCandidate->candidate) {

                    $transferCandidate->paid = TransferCandidate::UNPAID;
                    $transferCandidate->transfer_benef_iban = null;
                    $transferCandidate->transfer_benef_name = null;
                    $transferCandidate->bank_id = null;

                    if ($transferCandidate->save(false)) {

                        $transferCandidate->candidate->bank_id = null;
                        $transferCandidate->candidate->bank_account_name = null;
                        $transferCandidate->candidate->candidate_iban = null;
                        if ($transferCandidate->candidate->save(false)) {
                            $transferCandidate->unpaidNotification();
                        }
                    }
                }
            }*/

            // assuming every row showing successful transfer
            //if($value['Status'] == 'SUCCESS')
            //{

            $query = TransferCandidate::find()
                ->joinWith(['candidate'])
                ->andWhere([
                    //'transfer_benef_iban' => $value['Beneficiary Account'],
                    "transfer_candidate.candidate_id" => trim($value['Candidate ID']),
                    "transfer_candidate.candidate_total" => trim($value['Candidate Total']),
                    "transfer_candidate.currency_code" => trim($value['Currency Code']), // good to have filter, if same bank account in 2 country
                    "transfer_candidate.paid" => 0
                ]);

            /*if ($query->count() > 1) {

                Yii::error("Found more than one unpaid transfer with Candidate Account: #" . $value['Candidate ID'].
                    " Amount: " . $value['Candidate Total']);

                //. ", Store: " . $value['Store Name'] . "@" . $value['Company Name']
                
                /*return [
                    'operation' => 'error',
                    'message' => "Found more than one unpaid transfer with Candidate Account: #" . $value['Candidate ID'].
                        " Amount: " . $value['Candidate Total'],
                    'errorCode' => 4
                ];*
            }*/

            $transferCandidate = $query
                // having latest transfern as can have same bank account (for duplicate profile), same amount + currency (for previous month's transfer),
                ->orderBy("tc_id DESC")
                ->one();

            if(!$transferCandidate) {
                return [
                    'operation' => 'error',
                    'message' => "No unpaid transfer found with Candidate Account: #" . $value['Candidate ID'].
                        " Amount: " . $value['Candidate Total'],
                    'errorCode' => 4
                ];
            }

            if (!$transferCandidate->candidate) {
                return [
                    'operation' => 'error',
                    'message' => "No candidate profile found with Candidate Account: " . $value['Candidate ID'].
                        " Amount: " . $value['Candidate Total'],
                    'errorCode' => 4
                ];
            }

            $candidatesTransfers[] = [
                'transfer_confirmation_id' => trim($value['Reference Number']),
                "paid" => $transferCandidate->paid,
                'transfer_id' => $transferCandidate->transfer_id,
                'tc_id' => $transferCandidate->tc_id,
                "tc_created_at" => $transferCandidate->tc_created_at,
                'candidate_name' => $transferCandidate->candidate->candidate_name,
                "candidate_id" => $transferCandidate->candidate_id,
                'total_amount' => $value['Candidate Total'],//$transferCandidate->totalPaidToCandidate,
                "currency_code" => $value['Currency Code'], //$transferCandidate->currency_code
            ];

            $total += $transferCandidate->totalPaidToCandidate;
            //}
        }

        if (sizeof($candidatesTransfers)  == 0) {
            return [
                'operation' => 'error',
                'message' => 'Invalid excel',
                'errorCode' => 5
            ];
        }

        return [
            'operation' => 'success',
            'total' => $total,
            "bank" => "",
            'candidates' => $candidatesTransfers
        ];
    }

    /**
     * pay candidate by wallet
     * @param $id
     * @return string[]
     * @throws NotFoundHttpException
     */
    public function actionPayByWallet($id)
    {
        $initTransfer = Yii::$app->request->getBodyParam('init_transfer');//true;

        $model = $this->findModel($id);

        $transferCandidates = $model->getTransferCandidates()
            ->filterUnpaid()
            ->all();

        //todo: transaction not working as balance component already using
        //$transaction = Yii::$app->db->beginTransaction();

        foreach ($transferCandidates as $transferCandidate)
        {
            $result = TransferCandidate::markPaid(
                $transferCandidate->tc_id, null, true,
                $initTransfer, $transferCandidate, false);

            if($result['operation'] == 'error') {
               // $transaction->rollBack();
                return $result;
            }
        }

        //update transfer status

        Transfer::markTransferCompleteOnCandidatePaid($model->transfer_id);

       // $transaction->commit();

        /*if(YII_ENV == 'prod') {
            Transfer::triggerPayableCandidateEvent();
        }*/

        return [
            'operation' => 'success',
            'message' => 'Transfer paid by wallet'
        ];
    }

    /**
     * Method linked with payable candidate
     * section option to mark all candidate at one time
     */
    public function actionMarkPaidAll()
    {
        $candidate_ids = Yii::$app->request->getBodyParam('candidates');
        $bank =  Yii::$app->request->getBodyParam('bank');
        $excel = Yii::$app->request->getBodyParam('excel');

        if(!is_array($candidate_ids) || sizeof($candidate_ids) == 0) {
            return [
                'operation' => 'error',
                "code" => 1,
                'message' => 'Invalid request'
            ];
        }
        
        /*$model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');
        
        //validate given excel 
        
        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "code" => 2,
                "message" => $model->getErrors()
            ];
        }*/
        
        //save file used to mark transfers as paid 

        //try {
                
            //save file used to mark transfers as paid 
             
            $tc_ids = \yii\helpers\ArrayHelper::getColumn($candidate_ids, 'tc_id');

            $tf = \admin\models\TransferFile::saveFile($tc_ids, $excel, $bank);
            
            if(!$tf || !$tf->transfer_file_id) {

                return [
                    "operation" => "error",
                    "code" => 2,
                    "message" => 'Error on trying to save transfer file'
                ];
            }


        /*} catch (\Exception $e) {
            $transaction->rollBack();
            
            return [
                "operation" => "error",
                'message' => 'Invalid request',
                "code" => 6,
                'error' => $e
            ];

        } catch (\Throwable $e) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 7,
                'message' => 'Invalid request',
                'error' => $e
            ];
        }*/

        return [
            'operation' => 'success',
            'message' => 'File uploaded, will be processed soon!',
            //'message' => count($candidate_ids). ' candidates have been marked as paid',
        ];
    }

    /**
     * Return a Excel Containing Payable Candidates
     */
    public function actionExportPayableCandidates()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $payableCandidate = [];
        $onlyPayable = Yii::$app->request->get('only-payable');

        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->payable();

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($onlyPayable) {
            $query->havingBankInfo()
                ->activeCivilId();
        }

        $candidates = $query
            ->all();

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile

        if ($onlyPayable) {
            //todo: use batch function to lower memory usage?
            foreach ($candidates as $candidate) {
                if (
                    $candidate->candidate &&
                    $candidate->candidate->isProfileCompleted &&
                    $candidate->candidate->bank_id &&
                    $candidate->transfer_benef_iban &&
                    $candidate->transfer_benef_name &&
                    $candidate->invoiceNumber
                ) {
                    $payableCandidate[] = $candidate;
                }
            }
        } else {
            $payableCandidate = $candidates;
        }

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.xlsx';

        $savePath = sys_get_temp_dir();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $payableCandidate,
            'savePath' => $savePath,
            'fileName' => $fileName,
            'columns' => [
                'tc_id',
                'transfer_id',
                'candidate_id',
                'candidate.candidate_name',
                [
                    'attribute'=>'Beneficiary name',
                    'label'=>'Beneficiary name',
                    'value'=>function($data) {
                        return $data->candidate? $data->candidate->bank_account_name: $data->transfer_benef_name;
                    }
                ],
                'candidate.candidate_email',
                'candidate.store.company.company_name',
                'candidate.store.store_name',
                'hours',
                "minutes",
                "seconds",
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
                'candidate.bank.bank_name',
                "currency_code"
            ]
        ]);

        // Save to S3

        $path = $savePath .DIRECTORY_SEPARATOR. $fileName;

        $s3Response = $transferBankAdvice->saveExcelFile($path, $fileName);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
            //     var_dump($transferBankAdvice->errors);
            //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        Yii::$app->response->sendFile($path);

        // Delete the file
        if (!unlink($path)) {
            Yii::error("File could not be deleted");
        }
    }

    /**
     * Return a Excel Containing Payable Candidates for ABK bank
     */
    public function actionDownloadPaymentAdviceForAbk()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $payableCandidate = [];
        $onlyPayable = Yii::$app->request->get('only-payable');
        
        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->payable();

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($onlyPayable) {
            $query->havingBankInfo()
                ->activeCivilId();
        }
        
        $candidates = $query
            ->all();

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile

        if ($onlyPayable) {
            //todo: use batch function to lower memory usage?
            foreach ($candidates as $candidate) {
                if (
                    $candidate->candidate &&
                    $candidate->candidate->isProfileCompleted &&
                    $candidate->candidate->bank_id &&
                    $candidate->transfer_benef_iban &&
                    $candidate->transfer_benef_name &&
                    $candidate->invoiceNumber
                ) {
                    $payableCandidate[] = $candidate;
                }
            }
        } else {
            $payableCandidate = $candidates;
        }

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.xlsx';

        $savePath = sys_get_temp_dir();

        header('Access-Control-Allow-Origin: *');
        
        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $payableCandidate,
            'savePath' => $savePath,
            'fileName' => $fileName,
            'columns' => [
                [
                    'attribute'=> 'DEBIT ACCOUNT',
                    'label'=> 'DEBIT ACCOUNT',
                    'value'=> function($data) {
                        return "0603022881001";
                    }
                ],
                [
                    'attribute'=> 'Name',
                    'label'=> 'BENEFICIARY NAME',
                    'value'=>function($data) {
                        return $data->candidate? $data->candidate->bank_account_name: $data->transfer_benef_name;
                    }
                ],

                [
                    "attribute" => 'candidate.candidate_iban',
                    "label" => "BENEFICIARY ACCOUNT NUMBER",
                    "value" => function($data) {
                        return $data->bank->bank_iban_code == "ABKK" ?
                            TransferCandidate::extractAccountNumber($data->transfer_benef_iban):
                                $data->transfer_benef_iban;
                    }
                ],
                [
                    'attribute'=> 'BENEFICIARY BANK ADDRESS',
                    'label'=> 'BENEFICIARY BANK ADDRESS',
                    'value'=> function($data) {
                        return "KW";
                    }
                ],
                [
                    "attribute" => 'BENEFICIARY BANK NAME',
                    "label" => "BENEFICIARY BANK NAME",
                    'value' => function($data){
                        return $data->candidate->bank? str_pad($data->candidate->bank->bank_code_abk, 3, '0', STR_PAD_LEFT): null;
                    }
                ],
                [
                    'attribute'=> 'BENEFICIARY BANK BRANCH ID',
                    'label'=> 'BENEFICIARY BANK BRANCH ID',
                    'value'=> function($data) {
                        return "KW";
                    }
                ],

                [
                    'attribute'=> 'BENEFICIARY CITY',
                    'label'=> 'BENEFICIARY CITY',
                    'value'=> function($data) {
                        return "KW";
                    }
                ],

                [
                    'attribute'=> 'BENEFICIARY COUNTRY',
                    'label'=> 'BENEFICIARY COUNTRY',
                    'value'=> function($data) {
                        return "Kuwait";
                    }
                ],

                [
                    'attribute'=> 'BENEFICIARY ADDRESS 1',
                    'label'=> 'BENEFICIARY ADDRESS 1',
                    'value'=> function($data) {
                        return "KW";
                    }
                ],

                [
                    'attribute'=> 'BENEFICIARY ADDRESS 2',
                    'label'=> 'BENEFICIARY ADDRESS 2',
                    'value'=> function($data) {
                        return "KW";
                    }
                ],

                [
                    'attribute'=> 'TXN CURRENCY',
                    'label'=> 'TXN CURRENCY',
                    'value'=> function($data) {
                        return "KWD";
                    }
                ],

                [
                    "label" => "Amount",
                    'attribute'=>'Amount',
                    'value' => function($data){
                        return $data->candidate_total;
                    }
                ],

                [
                    'attribute'=> 'Type',
                    'label'=> 'Type',
                    'value'=> function($data) {
                        return $data->bank->bank_iban_code == "ABKK" ? "Within Bank": "Local Transfers";
                    }
                ],

                [
                    'attribute'=> 'BENEFICIARY BANK IDENTIFIER',
                    'label'=> 'BENEFICIARY BANK IDENTIFIER',
                    'value'=> function($data) {
                        return $data->bank->bank_swift_code . "XXX";
                    }
                ],

                [
                    'attribute'=> 'PURPOSE OF TRANSFER',
                    'label'=> 'PURPOSE OF TRANSFER',
                    'value'=> function($data) {
                        return "Bill Settlement";
                    }
                ],

                [
                    'attribute'=> 'PAYMENT DETAILS',
                    'label'=> 'PAYMENT DETAILS',
                    'value'=> function($data) {
                        return "S " . $data->tc_id;//$data->company_name.
                    }
                ],

                [
                    'attribute'=> 'Charge',
                    'label'=> 'Charge',
                    'value'=> function($data) {
                        return "OUR";
                    }
                ],

                /*[
                    "attribute" => "candidate.candidate_civil_id",
                    "label" => "Civil ID",
                ],*/
            ]
        ]);

        // Save to S3

        $path = $savePath .DIRECTORY_SEPARATOR. $fileName;

        $s3Response = $transferBankAdvice->saveExcelFile($path, $fileName);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
            //     var_dump($transferBankAdvice->errors);
            //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        Yii::$app->response->sendFile($path);

        // Delete the file
        if (!unlink($path)) {
            Yii::error("File could not be deleted");
        }
    }

    /**
     * @return void
     * @throws ServerErrorHttpException
     * @throws \yii\db\Exception
     */
    public function actionDownloadTextPaymentAdviceForAbk()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $payableCandidates = [];
        //$onlyPayable = Yii::$app->request->get('only-payable');

        // Candidates whose company paid to admin but admin have not paid yet
        $query = TransferCandidate::find()
            ->payable();

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        if ($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        //if ($onlyPayable) {
            $query->havingBankInfo()
                ->activeCivilId();
        //}

        $transferCandidates = $query
            ->all();

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile

        $totalAmount = 0;

        //if ($onlyPayable) {
            //todo: use batch function to lower memory usage?

            foreach ($transferCandidates as $transferCandidate) {
                if (
                    $transferCandidate->candidate &&
                    $transferCandidate->candidate->isProfileCompleted &&
                    $transferCandidate->candidate->bank &&
                    $transferCandidate->candidate->bank_id &&
                    $transferCandidate->transfer_benef_iban &&
                    $transferCandidate->transfer_benef_name &&
                    $transferCandidate->invoiceNumber
                ) {
                    $payableCandidates[] = $transferCandidate;

                    $totalAmount += $transferCandidate->totalPaidToCandidate;
                }
            }
        /*} else {
            $payableCandidate = $candidates;
        }*/

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.txt'; //BAWS-ADV-'.date('dmY').

        $s1 = 'FHR,'.$batchId.','.date("m/d/Y") . ','. sizeof($payableCandidates) . ','
            . number_format($totalAmount, 3, '.', '') .";". PHP_EOL; // header line

        $s2 = '';

        foreach ($payableCandidates as $payableCandidate) {

            //Payment Type (WIB/KASIP/SWI)

            $paymentType = $payableCandidate->bank->bank_iban_code == "ABKK" ? "WIB": "KASIP";

            $accountNumber = $payableCandidate->bank->bank_iban_code == "ABKK" ?
                TransferCandidate::extractAccountNumber($payableCandidate->transfer_benef_iban): $payableCandidate->transfer_benef_iban;

            $s2 .= "APO,0603022881001,603," . $payableCandidate->transfer_benef_name . "," . $accountNumber . ",KW,"
                . $payableCandidate->bank->bank_iban_code . ","
                . "KW,KW,KW,KW,KW," . $payableCandidate->currency_code . ","
                . number_format($payableCandidate->totalPaidToCandidate, 3, '.', '') . ","
                . $paymentType. ","
                . $payableCandidate->bank->bank_swift_code . "XXX,OBS,"
                . "S " . $payableCandidate->tc_id .",O,,,,,,,;"
                . PHP_EOL;

            //$payableCandidate->company_name.
        }

        $sAll = $s1.$s2;

        $path = sys_get_temp_dir() .DIRECTORY_SEPARATOR. $fileName;

        $handle = fopen($path, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        // Save to S3

        $s3Response = $transferBankAdvice->saveFile($fileName, $sAll);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
       //     var_dump($transferBankAdvice->errors);
       //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        Yii::$app->response->sendFile($path);

        // Delete the file
        if (!unlink($path)) {
            Yii::error("File could not be deleted");
        }
    }

    /**
     * method to generate text file for all unpaid candidates
     * @return array
     */
    public function actionText()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $s1 = 'S1,11622216,,MXD,M,,'.date('d/m/Y').','.date('dmY').'-01'.PHP_EOL; // header line
        $s2 = '';

        $candidates = TransferCandidate::getPayableCandidateListFormat($currency, $offset, $limit);

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

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.txt'; //BAWS-PAY-'.date('dmY').'-01.txt';

        $path = sys_get_temp_dir() .DIRECTORY_SEPARATOR. $fileName;

        $handle = fopen($path, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        // Save to S3

        $s3Response = $transferBankAdvice->saveFile($fileName, $sAll);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
            //     var_dump($transferBankAdvice->errors);
            //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        return Yii::$app->response->sendFile($path);
    }

    /**
     * Payment Advice file
     */
    public function actionDownloadPaymentAdvice()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $transferBankAdvice = new TransferBankAdvice();
        $transferBankAdvice->tba_uuid =  'tba_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
        $transferBankAdvice->serial_no = TransferBankAdvice::find()->count() + 1;

        $batchId = "T". $transferBankAdvice->serial_no . "V1";// 'BAWS-PAY-'.date('dmY').'-01.txt';

        $fileName = $batchId. '.txt'; //BAWS-PAY-'.date('dmY').'-01.txt';

        //$fileName = 'BAWS-ADV-'.date('dmY').'-01.txt';
        //$batchId = 'BAWS-PAY-'.date('dmY').'-01.txt';

        //todo: replace time() with reference number
        $s1 = 'H,'.$batchId.','.time().PHP_EOL; // header line
        $s2 = '';

        $candidates = TransferCandidate::getPayableCandidateAdvice($currency, $offset, $limit);

        if(!$candidates) {
            return [
                "operation" => "error",
                "message" => 'No Payable Candidates!'
            ];
        }

        foreach ($candidates['candidate_list'] as $detail) {
            $s2 .=  implode(',',$detail).",".PHP_EOL;
        }

        $s3 = 'T,'.count($candidates['candidate_list']).','.$candidates['total_amount']; // Footer
        $sAll = $s1.$s2.$s3;

        $path = sys_get_temp_dir() .DIRECTORY_SEPARATOR. $fileName;

        $handle = fopen($path, "w");
        fwrite($handle, $sAll);
        fclose($handle);

        // Save to S3

        $s3Response = $transferBankAdvice->saveFile($fileName, $sAll);

        if (!$s3Response) {
            throw new ServerErrorHttpException('Error processing your request.');
        }

        $transferBankAdvice->file_path = basename($s3Response['ObjectURL']);//$s3Response['Key'];

        if(!$transferBankAdvice->save()) {
            throw new ServerErrorHttpException("error to save doc". json_encode($transferBankAdvice->errors));
            //     var_dump($transferBankAdvice->errors);
            //     die();
        }

        Yii::$app->response->headers->add('filename', basename($s3Response['ObjectURL']));

        Yii::$app->response->sendFile($path);

        // Delete the file
        if (!unlink($path)) {
            Yii::error("File could not be deleted");
        }
    }

    /**
     * Export Transfer detail as Excel
     * @param $id
     * @return array
     */
    public function actionExport($id)
    {
        //validate
        $this->findModel($id);

        $offset = Yii::$app->request->get("offset");
        $limit = Yii::$app->request->get("limit");

        $query = TransferCandidate::find()
            ->candidatesByTransfer($id);

        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $candidates = $query
            ->all();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                [
                    "attribute" => 'transfer_id',
                    "label" => "Transfer ID"
                ],
                [
                    "attribute" => 'tc_id',
                    "label" => "Candidate Transfer ID"
                ],
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
                "minutes",
                "seconds",
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
                "currency_code"
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
     * @return mixed|ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionSuspiciousList()
    {
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = Transfer::find()
            ->joinWith([
                'transferCandidates' => function($query) {
                    return $query
                        ->joinWith('candidate')
                        ->andWhere('`candidate`.`store_id` = `transfer_candidate`.`store_id` ')
                        ->andWhere('`transfer_candidate`.`candidate_hourly_rate` != `candidate`.`candidate_hourly_rate`');

                }
            ]);

        $query->isParentTransfer();

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
            $query->orFilterWhere(['LIKE','{{%transfer}}.transfer_id',$company_name]);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');

//        return $query->createCommand()->getRawSql();
        return new ActiveDataProvider([
            'query' => $query
        ]);
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
