<?php

namespace staff\modules\v1\controllers;

use common\models\TransferCandidate;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use kartik\mpdf\Pdf;
use staff\models\Company;
use staff\models\Invoice;
use staff\models\Transfer;
use company\models\TranferExcel;
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
     */
    public function actionListCandidate()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $suspicious = Yii::$app->request->get('suspicious');
        $filterSameRate = Yii::$app->request->get('filterSameRate');
        $filterNoProfit = Yii::$app->request->get('filterNoProfit');
        $transfer_id = Yii::$app->request->get('transfer_id');

        $query = TransferCandidate::find();

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($transfer_id)
            $query->andWhere(['transfer_id' => $transfer_id]);

        if ($company_name) {
            $query->joinWith(['company'])
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($filterSameRate) {
            $query->filterSameRate();
        }

        if($filterNoProfit) {
            $query->filterNoProfit();
        }

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        //$query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer_candidate}}.tc_updated_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Transfer.
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $page = Yii::$app->request->get('page');
        $company_id = Yii::$app->request->get('company_id');
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $suspicious = Yii::$app->request->get('suspicious');
        $filterSameRate = Yii::$app->request->get('filterSameRate');
        $filterNoProfit = Yii::$app->request->get('filterNoProfit');
        //$filterParentOnly = Yii::$app->request->get('filterParentOnly');

        $query = Transfer::find()
            ->isParentTransfer();

        if($company_id) {
            $query->andWhere(['transfer.company_id' => $company_id]);
        }

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($filterSameRate) {
            $query->filterSameRate();
        }

        if($filterNoProfit) {
            $query->filterNoProfit();
        }

        if($suspicious) {
            $query->filterSuspicious();
        }

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false
            ]);
        }

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
        $transfer = Transfer::find()
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
        $company_id = Yii::$app->request->getBodyParam("company_id");
        $candidates = Yii::$app->request->getBodyParam("candidates");
        $start_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam("start_date")));
        $end_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam("end_date")));
        $currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$currency_code) {
            $currency_code = Yii::$app->request->headers->get('currency');
        }

        $company = $this->findCompany($company_id);

        if ($company->parent_company_id) {
            return [
                "operation" => "error",
                "message" => 'Subcompany transfer not allowed'
            ];
        }

        //save transfer
        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code);
    }

    /**
     * Initiate transfer by excel.
     * @return array
     */
    public function actionCreateByExcel()
    {
        $company_id = Yii::$app->request->getBodyParam("company_id");

        $company = $this->findCompany($company_id);

        if ($company->parent_company_id) {
            return [
                "operation" => "error",
                "message" => 'Subcompany transfer not allowed'
            ];
        }

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');
        $start_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('start_date')));
        $end_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('end_date')));
        $currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$currency_code) {
            $currency_code = Yii::$app->request->headers->get('currency');
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
        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code);
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
        $start_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('start_date')));
        $end_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('end_date')));
        $currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$currency_code) {
            $currency_code = Yii::$app->request->headers->get('currency');
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

        $transfer = $this->findModel($id);

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code);
    }

    /**
     * Edit transfer with "Initiated" status
     * @param $id
     * @return array
     */
    public function actionEdit($id)
    {
        $company = Yii::$app->user->identity;

        $company_id = Yii::$app->request->getBodyParam("company_id");
        $candidates = Yii::$app->request->getBodyParam("candidates");
        $start_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('start_date')));
        $end_date = date('Y-m-d', strtotime (Yii::$app->request->getBodyParam('end_date')));
        $currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$currency_code) {
            $currency_code = Yii::$app->request->headers->get('currency');
        }

        $transfer = $this->findModel($id);

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code);
    }

    /**
     * Mark Transfer as Payment Sent
     * @param $id
     * @return array
     */
    public function actionPaymentSent($id)
    {
        $transfer = Transfer::find()
            ->filterTransfer($id)
            ->one();

        if (!$transfer) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
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

        $info = '[ Staff '.Yii::$app->user->identity->staff_name.' marked Transfer #'.$transfer->transfer_id.' as "Payment Sent" ] ';
        $info .= '[ for Company '.$transfer->company->company_name.'] ';
        $info .= 'Check if payment has been received by bank.';
        Yii::info($info, __METHOD__);

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

        $transfer = Transfer::find()
            ->filterTransfer($id)
            ->one();

        if(!$transfer) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
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
        // https://bawescompany.atlassian.net/browse/ENG-166 condition for only two
        /*if ($transfer->company->company_id != 40 && $transfer->company->company_id != 72) {
            $transfer->notify('invoice');
        }*/

        $info = '[ Staff '.Yii::$app->user->identity->staff_name.' has locked transfer #'.$transfer->transfer_id.'] ';
        $info .= '[ for Company '.$transfer->company->company_name.'] ';
        $info .= 'They will be sending payment soon.';
        Yii::info($info, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Transfer has been locked. Invoices will be sent to your email."
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

        $info = '[Staff '.Yii::$app->user->identity->staff_name.' cancel Transfer #'.$id.' ] ';
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
        $model = Transfer::find()
            ->filterTransfer($id)
            ->one();

        if(!$model) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        //delete data child transfer
        if(Transfer::deleteTransfer($model))
        {
            $info = '[ Staff '.Yii::$app->user->identity->staff_name.' Deleted Transfer #'.$id.' ] ';
            $info .= '[ for Company '.$model->company->company_name.'] ';
            $info .= 'Check for reason and ask if they require assistance.';
            Yii::info($info, __METHOD__);

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
        $invoice = Invoice::find()
            ->withTransfer($id)
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
    public function actionTransferExcelTemplate($id)
    {
        $company = $this->findCompany($id);

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
                    'header' => 'candidate_civil_id',
                    'value' => function($data) {
                        return $data->candidate_civil_id;
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
                ],
                'currency_code'
            ]
        ]);
    }

    /**
     * Excel template to initiate transfer
     */
    public function actionExportCompaniesTransfer()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = Transfer::find()
            ->isParentTransfer();

        if($currency) {
            $query->andWhere(['transfer.currency_code' => $currency]);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');
        ;

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                [
                    'header' => 'company name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->company) ? $model->company->company_common_name_en : '-';
                    },
                ],
                [
                    'header' => 'Transfer',
                    "format" => "raw",
                    "value" => function ($model) {
                        return '# '.$model->transfer_id;
                    },
                ], [
                    'header' => 'Invoice Amount',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->company_total;
                    },
                ],
                [
                    'header' => 'Cost',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->total;
                    },
                ],
                [
                    'header' => 'Profit',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->profit) ? $model->profit : '-';
                    },
                ],
                [
                    'header' => 'Type',
                    "format" => "raw",
                    "value" => function ($model) {
                        return '-';
                    },
                ],
                [
                    'header' => 'Hours',
                    "format" => "raw",
                    "value" => function ($model) {
                        return number_format($model->getTransferCandidates()->sum('hours'),3);
                    },
                ],
                [
                    'header' => 'Candidates',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->getTransferCandidates()->count();
                    },
                ],
                [
                    'header' => 'start_date',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->start_date;
                    },
                ],
                [
                    'header' => 'end_date',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->end_date;
                    },
                ],
            ]
        ]);
    }

    protected function findCompany($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findModel($id)
    {
        if (($model = Transfer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
