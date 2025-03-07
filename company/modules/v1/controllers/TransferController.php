<?php

namespace company\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use common\models\CandidateWorkingHour;
use common\models\Contract;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use company\models\Transfer;
use company\models\TranferExcel;
use company\models\Invoice;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
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
            'collectionOptions' => ['POST'],
            'resourceOptions' => ['POST', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Transfer.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Yii::$app->companyManager->getCompany()
            ->getParentTransfers();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }


    /**
     * get approved hours by date range
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionApprovedWorkLog() {
        $startDate = Yii::$app->request->get("start_date");
        $endDate = Yii::$app->request->get("end_date");

        $company = Yii::$app->companyManager->getCompany();

        $data = [];

        foreach ($company->getCandidates()->batch(100) as $candidates) {

            foreach ($candidates as $candidate) {

                $seconds = CandidateWorkingDate::find()->andWhere([
                    "candidate_id" => $candidate->candidate_id,
                    "store_id" => $candidate->store_id, //filter by store, in case store changed in month
                    "status" => CandidateWorkingDate::STATUS_APPROVED
                ])
                    ->filterByDateRange($startDate, $endDate)
                    ->sum("total_time");

                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds - ($hours * 3600)) / 60);

                $data[] = [
                    "candidate_id" => $candidate->candidate_id,
                    "hours" => $hours,
                    "minutes" => $minutes,
                    "seconds" => $seconds - ($hours * 3600) - ($minutes * 60),
                    //"bonus" => 0
                ];
            }
        }

        return $data;
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array
     */
    public function actionView($id)
    {
        $company = Yii::$app->companyManager->getCompany();

        $transfer = $company
            ->getTransfers()
            ->filterTransfer($id)
            ->with([
                'transferCandidates', 
                'transferCandidates.candidate', 
                'transferCandidates.candidate.store', 
                'transferCandidates.candidate.company', 
                'transferCandidates.candidate.bank',
                'transferCandidates.candidate.university'
            ])    
            ->one();

        if (!$transfer)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $transfer;
    }

    /**
     * Initiate transfer.
     * @return array
     */
    public function actionCreate()
    {
        $company = Yii::$app->companyManager->getCompany();

        $candidates = Yii::$app->request->getBodyParam("candidates");
        $start_date = Yii::$app->request->getBodyParam("start_date");
        $end_date = Yii::$app->request->getBodyParam("end_date");
        $currency_code = Yii::$app->request->getBodyParam('currency_code');
        $contract_uuid = Yii::$app->request->getBodyParam('contract_uuid');

        if(!$currency_code) {
            Yii::$app->request->headers->get('currency');
        }

        //save transfer
        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code, $contract_uuid);
    }
    
    /**
     * Initiate transfer by excel.
     * @return array
     */
    public function actionCreateByExcel()
    {
        $company = Yii::$app->companyManager->getCompany();
        
        $model = new TranferExcel;        
        $model->excel = Yii::$app->request->getBodyParam('excel');
        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');
        $currency_code = Yii::$app->request->getBodyParam('currency_code');
        $contract_uuid = Yii::$app->request->getBodyParam('contract_uuid');
        $contract_type = Yii::$app->request->getBodyParam('contract_type');

        if(!$currency_code) {
            Yii::$app->request->headers->get('currency');
        }

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }
        
        $candidates = [];

        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($model->excel); 

        //save in temp folder to process 
        
        $tmpFile = sys_get_temp_dir() . '/' . $model->excel;
                
        if(!file_put_contents($tmpFile, file_get_contents($fileUrl))) { 
            return [
                "operation" => "error",
                "type" => "system",
                "message" => Yii::t('company',"Error reading file")
            ];
        } 

        $data  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel);

        //no need file anymore 
        
        @unlink($tmpFile);
        
        //remove empty rows 

        foreach ($data as $key => $value) 
        {
            if(empty($value['candidate_id']))
                continue;

            $candidates[] = $value;
        }

        //save transfer

        $noOfPayout = 1;

        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code, $contract_uuid, $noOfPayout, $contract_type);
    }

    /**
     * Edit transfer by excel.
     * @param $id
     * @return array
     */
    public function actionEditByExcel($id)
    {
        $model = new TranferExcel;    
        $model->excel = Yii::$app->request->getBodyParam('excel');
        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');
        $currency_code = Yii::$app->request->getBodyParam('currency_code');
        $contract_uuid = Yii::$app->request->getBodyParam('contract_uuid');
        $contract_type = Yii::$app->request->getBodyParam('contract_type');

        if(!$currency_code) {
            Yii::$app->request->headers->get('currency');
        }

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }
        
        $candidates = [];

        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($model->excel); 

        //save in temp folder to process 

        $tmpFile = sys_get_temp_dir() . '/' . $model->excel;

        if(!file_put_contents($tmpFile, file_get_contents($fileUrl))) { 
            return [
                "operation" => "error",
                "type" => "system",
                "message" => Yii::t('company',"Error reading file")
            ];
        } 

        $data  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel);

        //no need file anymore 

        @unlink($tmpFile);

        //remove empty rows 

        foreach ($data as $key => $value) 
        {
            if(empty($value['candidate_id']))
                continue;

            $candidates[] = $value;
        }

        //save transfer

        $transfer = $this->findModel($id);

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code, $contract_uuid, $contract_type);
    }

    /**
     * Edit transfer with "Initiated" status
     * @param $id
     * @return array
     */
    public function actionEdit($id)
    {
        $candidates = Yii::$app->request->getBodyParam("candidates");
        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');
        $currency_code = Yii::$app->request->getBodyParam('currency_code');
        $contract_uuid = Yii::$app->request->getBodyParam('contract_uuid');
        $contract_type = Yii::$app->request->getBodyParam('contract_type');

        if(!$currency_code) {
            Yii::$app->request->headers->get('currency');
        }

        $transfer = $this->findModel($id);

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code, $contract_uuid, $contract_type);
    }

    /**
     * Mark Transfer as Payment Sent
     * @param $id
     * @return array
     */
    public function actionPaymentSent($id)
    {
        $transfer = $this->findModel ($id);

        try{
            $transfer->paymentSent();
        }
        catch(\Exception $e)
        {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        $company = Yii::$app->companyManager->getCompany();

        $info = '[ Agent '.Yii::$app->user->identity->contact_name.' marked Transfer #'.$transfer->transfer_id.' as "Payment Sent" ] ';
        $info .= '[ for Company '.$company->company_name.'] ';
        $info .= 'Check if payment has been received by bank.';
        Yii::info($info, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company','Transfer has been marked as "Payment Sent"')
        ];
    }

    /**
     * Lock transfer
     * @param $id
     * @return array
     */
    public function actionLock($id)
    {
        $transfer = $this->findModel ($id);

        try{
            $transfer->lock();
        }
        catch(\Exception $e)
        {
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }
        
        // send invoice mail
        $transfer->notify('invoice');

        $company = Yii::$app->companyManager->getCompany();

        $info = '[ Agent '.Yii::$app->user->identity->contact_name.' has locked transfer #'.$transfer->transfer_id.' ] ';
        $info .= '[ for Company '.$company->company_name.'] ';
        $info .= 'They will be sending payment soon.';
        Yii::info($info, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Transfer has been locked. Invoices will be sent to your email.")
        ];
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

        $company = Yii::$app->companyManager->getCompany();

        $info = '[Agent '.Yii::$app->user->identity->contact_name.' has cancel transfer #'.$transfer->transfer_id.' ] ';
        $info .= '[ for Company '.$company->company_name.'] ';

        Yii::info($info, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Transfer has been cancelled.")
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

        //delete data child transfer
        if(Transfer::deleteTransfer($model)) 
        {
            $company = Yii::$app->companyManager->getCompany();

            $info = '[ Agent '.Yii::$app->user->identity->contact_name.' has Deleted Transfer #'.$id.' ] ';
            $info .= '[ for Company '.$company->company_name.'] ';
            $info .= 'Check for reason and ask if they require assistance.';
            Yii::info($info, __METHOD__);

            return [
                "operation" => "success",
                "message" => Yii::t('company','Transfer deleted as requested.')
            ];
        } 
        else 
        {
            return [
                "operation" => "error",
                "message" => Yii::t('company','Transfer status should be "Initiated" or "Locked" to delete it!')
            ];            
        }        
    }

    /**
     * Download Transfer as PDF
     * @param $id
     * @return array|mixed
     */
    public function actionPdf($id)
    {
        $company = Yii::$app->companyManager->getCompany();

        $invoice = Invoice::find()
            ->withTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

        if(!$invoice) {
            return [
                "operation" => "error",
                "message" => 'Invoice not found!'
            ];
        }

        $this->layout = 'pdf';

        if($invoice['invoice_status'] == 'paid')
            $template = 'receipt';
        else
            $template = 'invoice';

        $content = $this->render('@admin/modules/v1/views/transfer/' . $template . '.php', [
            'invoice' => $invoice,
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // any css to be embedded if required
            'cssInline' => 'body {line-height: 1.85714286em;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #666666;} h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #252525;font-variant-ligatures: common-ligatures;margin-top: 0;margin-bottom: 0;}',
            // set mPDF properties on the fly
            'options' => [],//['title' => 'Booking #'.$id],
            // call mPDF methods on the fly
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }
    
    /**
     * Excel template to initiate transfer
     */
    public function actionTransferExcelTemplate()
    {
        $preFilled = Yii::$app->request->get("preFilled");
        $startDate = Yii::$app->request->get("startDate");
        $endDate = Yii::$app->request->get("endDate");
        $contract_uuid = Yii::$app->request->get("contract_uuid");
        $contract_type = Yii::$app->request->get("contract_type");

        $company = Yii::$app->companyManager->getCompany();

        $transferCandidates = [];

        $candidateQuery = $company->getCandidates()
            ->joinWith(['contracts' => function($subQuery) use ($company) {
                $subQuery->filterActive()
                    ->filterOrg($company->company_id);
                // ->orderBy(['contract.created_at' => 'DESC']);
            }])
            ->andWhere(new Expression('contract.contract_uuid IS NOT NULL'));

        if ($contract_uuid) {
            $candidateQuery->andWhere(['contract.contract_uuid' => $contract_uuid]);
        }

        if ($contract_type && $contract_type != "ALL") {
            $candidateQuery->andWhere(['contract.type' => $contract_type]);
        }

        if ($preFilled) {

            if ($preFilled == 'workLog') {

                if(!$startDate) {
                    $startDate = date('Y-m-01');
                }

                if (!$endDate) {
                    $endDate = date("Y-m-d");
                }

                foreach ($candidateQuery->batch(100) as $candidates) {

                    foreach ($candidates as $candidate) {

                        $seconds = CandidateWorkingDate::find()->andWhere([
                            "candidate_id" => $candidate->candidate_id,
                            "store_id" => $candidate->store_id, //filter by store, in case store changed in month
                            "status" => CandidateWorkingDate::STATUS_APPROVED
                        ])
                            ->filterByDateRange($startDate, $endDate)
                            ->sum("total_time");

                        $hours = floor($seconds / 3600);
                        $minutes = floor(($seconds - ($hours * 3600)) / 60);

                        $transferCandidates[$candidate->candidate_id] = [
                            "hours" => $hours,
                            "minutes" => $minutes,
                            "seconds" => $seconds - ($hours * 3600) - ($minutes * 60),
                            "bonus" => 0
                        ];
                    }
                }

            } else {

                $latestTransferQuery = $company->getParentTransfers();

                if ($contract_uuid) {
                    $latestTransferQuery->andWhere(['contract_uuid' => $contract_uuid]);
                }

                $latestTransfer = $latestTransferQuery->one();

                if ($latestTransfer) {
                    $transferCandidates = ArrayHelper::index($latestTransfer->getTransferCandidates()->all(),
                        "candidate_id");
                }
            }
        }

        header('Access-Control-Allow-Origin: *');

        $columns = [
            [
                'header' => 'candidate_id',
                'value' => function($data) {
                    return $data->candidate_id;
                }
            ],
            [
                'header' => 'candidate_name',
                'value' => function($data) {
                    return $data->candidate_name;
                }
            ],
            [
                'header' => 'company_name',
                'value' => function($data) {
                    return $data->company->company_name;
                }
            ],
            [
                'header' => 'store_name',
                'value' => function($data) {
                    return $data->store->store_name;
                }
            ],
        ];

        //$contract = $company->getContracts()
        //    ->andWhere(['contract_uuid' => $contract_uuid])
         //   ->one();

        if (!$contract_type || $contract_type == Contract::TYPE_HOURLY) {
            //!$contract || $contract->type == Contract::TYPE_HOURLY) {
            $columns = array_merge($columns, [
                [
                    'header' => 'bonus',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['bonus']: 0;
                    }
                ],
                [
                    'header' => 'hours',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                    return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                        $transferCandidates[$data->candidate_id]['hours']: 0;
                    }
                ],
                [
                    'header' => 'minutes',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['minutes']: 0;
                    }
                ],
                [
                    'header' => 'seconds',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['seconds']: 0;
                    }
                ],
            ]);
        } /*else { //fixed or monthly
            $columns = array_merge($columns, [
                [
                    'header' => 'candidate_total',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['candidate_total']: 0;
                    }
                ],
                [
                    'header' => 'company_total',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['company_total']: 0;
                    }
                ],
            ]);
        }*/

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $candidateQuery->all(),
            'columns' => $columns
        ]);        
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $company = Yii::$app->companyManager->getCompany();

        $model = $company
            ->getTransfers()
            ->filterTransfer($id)
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
