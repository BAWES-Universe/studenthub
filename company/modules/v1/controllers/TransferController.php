<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use company\models\Company;
use company\models\Transfer;
use company\models\TranferExcel;
use common\models\Invoice;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use kartik\mpdf\Pdf;

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
        $query = Yii::$app->user->identity
                    ->getParentTransfers();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array
     */
    public function actionView($id)
    {
        $company = Yii::$app->user->identity;

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
            throw new yii\web\NotFoundHttpException('The requested page does not exist.');

        return $transfer;

    }

    /**
     * Initiate transfer.
     * @return array
     */
    public function actionCreate()
    {
        $company = Yii::$app->user->identity;
        $candidates = Yii::$app->request->getBodyParam("candidates");

        //save transfer
        return Transfer::saveTransfer($company, $candidates);
    }
    
    /**
     * Initiate transfer by excel.
     * @return array
     */
    public function actionCreateByExcel()
    {
        $company = Yii::$app->user->identity;
        
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
        
        $candidates = [];

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

        $data  = \moonland\phpexcel\Excel::import(sys_get_temp_dir() . '/' . $model->excel);

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
        return Transfer::saveTransfer($company, $candidates);
    }

    /**
     * Edit transfer by excel.
     * @param $id
     * @return array
     */
    public function actionEditByExcel($id)
    {
        $company = Yii::$app->user->identity;

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
        
        $candidates = [];

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

        $data  = \moonland\phpexcel\Excel::import(sys_get_temp_dir() . '/' . $model->excel);

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
        return Transfer::updateTransfer($company, $id, $candidates);
    }

    /**
     * Edit transfer with "Initiated" status
     * @param $id
     * @return array
     */
    public function actionEdit($id)
    {
        $company = Yii::$app->user->identity;

        $candidates = Yii::$app->request->getBodyParam("candidates");

        return Transfer::updateTransfer($company, $id, $candidates);
    }

    /**
     * Mark Transfer as Payment Sent
     * @param $id
     * @return array
     */
    public function actionPaymentSent($id)
    {
        $company = Yii::$app->user->identity;

        $transfer = $company
            ->getTransfers()
            ->filterTransfer($id)
            ->one();

        if (!$transfer) {
            throw new yii\web\NotFoundHttpException('The requested page does not exist.');
        }

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

        Yii::info('[Company '.$company->company_name.' marked Transfer #'.$transfer->transfer_id.' as "Payment Sent"] Check if payment has been received by bank.', __METHOD__);

        return [
            "operation" => "success",
            "message" => 'Transfer has been marked as "Payment Sent"'
        ];
    }

    /**
     * Lock transfer
     * @param $id
     * @return array
     */
    public function actionLock($id)
    {
        $company = Yii::$app->user->identity;

        $transfer = $company
            ->getTransfers()
            ->filterTransfer($id)
            ->one();

        if(!$transfer) {
            throw new yii\web\NotFoundHttpException('The requested page does not exist.');
        }

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

        Yii::info('[Company '.$company->company_name.' has locked transfer #'.$transfer->transfer_id.'] They will be sending payment soon.', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Transfer has been locked. Invoices will be sent to your email."
        ];
    }

    /**
     * Delete transfer with "Initiated" or "Locked" status
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        $company = Yii::$app->user->identity;

        $model = $company
            ->getTransfers()
            ->filterTransfer($id)
            ->one();

        if(!$model) {
            throw new yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        //delete data child transfer
        if(Transfer::deleteTransfer($model)) 
        {
            Yii::info('[Company '.$company->company_name.' Deleted Transfer #'.$id.'] Check for reason and ask if they require assistance.', __METHOD__);

            return [
                "operation" => "success",
                "message" => 'Transfer deleted as requested.'
            ];
        } 
        else 
        {
            return [
                "operation" => "error",
                "message" => 'Transfer status should be "Initiated" or "Locked" to delete it!'
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
        $company = Company::findOne(Yii::$app->user->id);

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
        $company = Yii::$app->user->identity;
        
        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $company->candidates,
            'columns' => [
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
                [
                    'header' => 'hours',
                    'value' => function() {
                        return 0;
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function() {
                        return 0;
                    }
                ]
            ]
        ]);        
    }
}
