<?php

namespace staff\modules\v1\controllers;

use common\models\CandidateWorkingDate;
use common\models\TransferCandidate;
use common\models\TransferCost;
use common\models\TransferRateExcel;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
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
        $filterDuplicate = Yii::$app->request->get("filterDuplicate");

        $transfer_id = Yii::$app->request->get('transfer_id');
        $tc_id  = Yii::$app->request->get('tc_id');

        $query = TransferCandidate::find();

        if ($filterDuplicate) {
            $query->filterDuplicate();
        }

        if($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if($transfer_id)
            $query->andWhere(['transfer_id' => $transfer_id]);

        if ($tc_id) {
            $query->andWhere(['tc_id' => $tc_id]);
        }

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
     * @return void
     */
    public function actionExportCandidateTransfers()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $suspicious = Yii::$app->request->get('suspicious');
        $filterSameRate = Yii::$app->request->get('filterSameRate');
        $filterNoProfit = Yii::$app->request->get('filterNoProfit');
        $filterDuplicate = Yii::$app->request->get("filterDuplicate");

        $transfer_id = Yii::$app->request->get('transfer_id');
        $tc_id = Yii::$app->request->get('tc_id');

        $query = TransferCandidate::find();

        if ($filterDuplicate) {
            $query->filterDuplicate();
        }

        if ($currency) {
            $query->andWhere(['transfer_candidate.currency_code' => $currency]);
        }

        if ($transfer_id)
            $query->andWhere(['transfer_id' => $transfer_id]);

        if ($tc_id) {
            $query->andWhere(['tc_id' => $tc_id]);
        }

        if ($company_name) {
            $query->joinWith(['company'])
                ->filterCompany($company_name);
        }

        if ($transfer_status)
            $query->filterStatus($transfer_status);

        if ($filterSameRate) {
            $query->filterSameRate();
        }

        if ($filterNoProfit) {
            $query->filterNoProfit();
        }

        if ($start_date)
            $query->startDate($start_date);

        if ($end_date)
            $query->endDate($end_date);

        //$query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer_candidate}}.tc_updated_at DESC');

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                /*[
                    'header' => 'company name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->company) ? $model->company->company_common_name_en : '-';
                    },
                ],*/
                [
                    'header' => 'Transfer',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->transfer_id;//'# '.
                    },
                ],
                [
                    'header' => 'Candidate Transfer ID',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->tc_id;
                    },
                ],
                "candidate_id",
                /*[
                    'header' => 'Duplicate Candidate Transfers',
                    "format" => "raw",
                    "value" => function ($model) {
                        $result = [];

                        foreach ($model->getDuplicates() as $duplicate) {
                            $result[] = $duplicate->transfer_id . " -> " . $duplicate->tc_id;
                        }

                        return implode(", ", $result);
                    },
                ],*/
                [
                    'header' => 'Invoice Amount',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->company_total;
                    },
                ],
                [
                    'header' => 'Candidate Total',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->candidate_total;
                    },
                ],
                'profit',
                "hours",
                "minutes",
                "seconds",
                "bonus",
                "bonus_commission",
                "transfer_cost",
                "tc_created_at",
                "tc_updated_at"
            ]
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
               // "contract",
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
        $contract_uuid = Yii::$app->request->getBodyParam("contract_uuid");

        $start_date = Yii::$app->request->getBodyParam("start_date");
        $end_date = Yii::$app->request->getBodyParam("end_date");

        $candidates = Yii::$app->request->getBodyParam("candidates");

        if ($start_date)
            $start_date = date('Y-m-d', strtotime ($start_date));

        if ($end_date)
            $end_date = date('Y-m-d', strtotime ($end_date));

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
        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code, $contract_uuid);
    }

    /**
     * Initiate transfer by excel.
     * @return array
     */
    public function actionCreateByExcel()
    {
        $company_id = Yii::$app->request->getBodyParam("company_id");
        $contract_uuid = Yii::$app->request->getBodyParam("contract_uuid");

        $company = $this->findCompany($company_id);

        if ($company->parent_company_id) {
            return [
                "operation" => "error",
                "message" => 'Subcompany transfer not allowed'
            ];
        }

        $start_date = Yii::$app->request->getBodyParam("start_date");
        $end_date = Yii::$app->request->getBodyParam("end_date");

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');


        $start_date = Yii::$app->request->getBodyParam("start_date");
        $end_date = Yii::$app->request->getBodyParam("end_date");

        if ($start_date)
            $start_date = date('Y-m-d', strtotime ($start_date));

        if ($end_date)
            $end_date = date('Y-m-d', strtotime ($end_date));

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
        return Transfer::saveTransfer($company, $candidates, $start_date, $end_date, $currency_code, $contract_uuid);
    }

    /**
     * Edit transfer by excel.
     * @param $id
     * @return array
     */
    public function actionEditByExcel($id)
    {
        $contract_uuid = Yii::$app->request->getBodyParam("contract_uuid");

        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');

        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');

        if ($start_date)
            $start_date = date('Y-m-d', strtotime ($start_date));

        if ($end_date)
            $end_date = date('Y-m-d', strtotime ($end_date));

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

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code, $contract_uuid);
    }

    /**
     * Edit transfer with "Initiated" status
     * @param $id
     * @return array
     */
    public function actionEdit($id)
    {
        $contract_uuid = Yii::$app->request->getBodyParam("contract_uuid");

        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');

        $company = Yii::$app->user->identity;

        $company_id = Yii::$app->request->getBodyParam("company_id");
        $candidates = Yii::$app->request->getBodyParam("candidates");

        $start_date = Yii::$app->request->getBodyParam('start_date');
        $end_date = Yii::$app->request->getBodyParam('end_date');

        if ($start_date)
            $start_date = date('Y-m-d', strtotime ($start_date));

        if ($end_date)
            $end_date = date('Y-m-d', strtotime ($end_date));

        $currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$currency_code) {
            $currency_code = Yii::$app->request->headers->get('currency');
        }

        $transfer = $this->findModel($id);

        return $transfer->updateTransfer($candidates, $start_date, $end_date, $currency_code, $contract_uuid);
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
        if ($transfer->company->company_id != 40 && $transfer->company->company_id != 72) {
            $transfer->notify('invoice');
        }

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
     * @param $id
     * @return array|string[]|void
     * @throws \yii\db\Exception
     */
    public function actionUpdateTransferRatesByExcel($id) {

        //$company = $this->findCompany($id);

        $model = new TransferRateExcel();
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

        $data  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel);

        //no need file anymore

        @unlink($tmpFile);

        // remove old data

        TransferCost::deleteAll([
            "company_id" => $id
        ]);

        //populate data

        $rows = [];

        foreach ($data as $key => $value)
        {
            //remove empty rows
            if(empty($value['candidate_id']))
                continue;

            $rows[] = [
                "company_id" => $id,
                "candidate_id" => $value['candidate_id'],
                "transfer_cost" => $value['transfer_cost'],
                "created_at" => new \yii\db\Expression("NOW()"),
                "updated_at" => new \yii\db\Expression("NOW()")
            ];
        }

        Yii::$app->db->createCommand()->batchInsert('transfer_cost',
            ["company_id", 'candidate_id', "transfer_cost", 'created_at', "updated_at"], $rows)->execute();

        return [
            "operation" => "success",
            "message" => "Transfer rates updated successfully!"
        ];
    }

    /**
     * @param $id
     * @return void
     * @throws NotFoundHttpException
     */
    public function actionTransferRatesTemplate($id)
    {
        $preFilled = Yii::$app->request->get("preFilled");

        $company = $this->findCompany($id);

        $arrCompanyTransferRates = [];

        if ($preFilled) {
            $arrCompanyTransferRates = ArrayHelper::map(
                    $company->getTransferRates()->all(),
                    "candidate_id",
                    "transfer_cost");
        }

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $company->candidates,
            'columns' => [
                [
                    "header" => "candidate_id",
                    "value" => function($data) {
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
                    'header' => 'store_name',
                    'value' => function($data) {
                        return $data->store->store_name;
                    }
                ],
                [
                    'header' => 'transfer_cost',
                    'value' => function($data) use ($arrCompanyTransferRates, $preFilled) {
                        return $preFilled && isset($arrCompanyTransferRates[$data->candidate_id]) ?
                            $arrCompanyTransferRates[$data->candidate_id]: 0;;
                    }
                ]
            ]
        ]);
    }

    /**
     * get approved hours by date range
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionApprovedWorkLog($id) {
        $startDate = Yii::$app->request->get("start_date");
        $endDate = Yii::$app->request->get("end_date");

        $company = $this->findCompany($id);

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
     * Excel template to initiate transfer
     */
    public function actionTransferExcelTemplate($id)
    {
        $contract_uuid = Yii::$app->request->get("contract_uuid");
        $preFilled = Yii::$app->request->get("preFilled");
        $startDate = Yii::$app->request->get("startDate");
        $endDate = Yii::$app->request->get("endDate");

        $company = $this->findCompany($id);

        $transferCandidates = [];

        if ($preFilled) {
            if ($preFilled == 'workLog') {

                if(!$startDate) {
                    $startDate = date('Y-m-01');
                }

                if (!$endDate) {
                    $endDate = date("Y-m-d");
                }

                $candidateQuery = $company->getCandidates();

                if ($contract_uuid) {
                    $candidateQuery
                        ->joinWith(['candidateWorkHistories'])
                        ->andWhere(["contract_uuid" => $contract_uuid]);
                } else {
                    $candidateQuery
                        ->joinWith(['candidateWorkHistories'])
                        ->andWhere(new Expression("contract_uuid IS NULL"));
                }

                foreach ($candidateQuery->batch() as $candidates) {

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
                    $latestTransferQuery
                        ->andWhere(["contract_uuid" => $contract_uuid]);
                } else {
                    $latestTransferQuery
                        ->andWhere(new Expression("contract_uuid IS NULL"));
                }

                $latestTransfer = $latestTransferQuery->one();

                if ($latestTransfer) {
                    $transferCandidates = ArrayHelper::index($latestTransfer->getTransferCandidates()->all(), "candidate_id");
                }
            }
        }

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
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
                [
                    'header' => 'bonus',
                    'value' => function($data) use ($transferCandidates, $preFilled) {
                        return $preFilled && isset($transferCandidates[$data->candidate_id]) ?
                            $transferCandidates[$data->candidate_id]['bonus']: 0;;
                    }
                ],
               // "company_total",
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

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
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
                    'header' => 'Minutes',
                    "format" => "raw",
                    "value" => function ($model) {
                        return number_format($model->getTransferCandidates()->sum('minutes'),3);
                    },
                ],
                [
                    'header' => 'Seconds',
                    "format" => "raw",
                    "value" => function ($model) {
                        return number_format($model->getTransferCandidates()->sum('seconds'),3);
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
                [
                    'header' => 'contract_uuid',
                    "format" => "raw",
                    "value" => function ($model) {
                        return $model->contract_uuid;
                    },
                ],
            ]
        ]);
    }

    /**
     * @param $id
     * @return Company|null
     * @throws NotFoundHttpException
     */
    protected function findCompany($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param $id
     * @return Transfer|null
     * @throws NotFoundHttpException
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
