<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Invoice;
use admin\models\Transfer;
use admin\models\TransferCandidate;
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

        $query = Transfer::find()
            ->notDeleted()
            ->isParentTransfer();

        if($company_name)
        {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at');

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
                ->where(['paid' => '0'])
                ->all();

            if($candidates)
            {
                $result[] = [
                    'transfer_id' => $transfer->transfer_id,
                    'candidates' => $candidates,
                    'total' => $transfer->total
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
        $transfer = Transfer::findOne((int)$id);

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
    public function actionPaymentReceived($id)
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

        Yii::info('[Transfer marked as "Payment Received"] Transfer "'.$id.'" marked as "Payment Received" by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

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

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        try{
            $transfer->unlock();
        } catch(Exception $e){
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        Yii::info('[Transfer unlocked] Transfer "'.$id.'" unlocked by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

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

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        try{
            $transfer->lock();
        } catch(Exception $e){
            return [
                "operation" => "error",
                "message" => $e->getMessage()
            ];
        }

        Yii::info('[Transfer reverted to locked] Transfer "'.$id.'" reverted to locked by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

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

        Yii::info('[Candidate(s) have been marked as paid] ' . count($candidate_ids) . ' candidate(s) have been marked as paid by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            'operation' => 'success',
            'message' => count($candidate_ids). ' candidate(s) have been marked as paid',
        ];
    }

    /**
     * Return a Excel Containing Payable Candidates
     */
    public function actionExportPayableCandidates()
    {
        // Candidates whose company paid to admin but admin have not paid yet
        $candidates = TransferCandidate::find()
            ->payable()
            ->all();

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
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
                'bonus',
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
        $totalTransaction = 0;
        $totalAmount = 0;

        $candidates = TransferCandidate::find()
            ->payable()
            ->all();

        if(!$candidates) {
            return [
                "operation" => "error",
                "message" => 'No Payable Candidates!'
            ];
        }

        foreach ($candidates as $detail) {
            $totalAmount += $detail->totalPaidToCandidate;
            $description = 'Internship '.$detail->hours.' Hours';

            if(empty($detail->candidate->bank)) {
                continue;
            }

            $s2 .=  "S2,".$detail->candidate->bank->bank_transfer_type.",".$detail->totalPaidToCandidate.",KWD,,,,11622216,".
                    $detail->candidate->candidate_iban.",".
                    $detail->transfer_id.",".
                    $detail->invoiceNumber.",".
                    $description.",,,,".
                    $detail->candidate->bank_account_name.",".
                    $detail->candidate->bank->bank_name.",,".
                    $detail->candidate->bank->bank_name.",".
                    $detail->candidate->bank->bank_address.",,,".
                    $detail->candidate->bank->bank_swift_code.",,,,,,,B,,,".
                    $detail->candidate->candidate_iban.",".PHP_EOL;
            $totalTransaction +=1;
        }

        $finalAmount = number_format($totalAmount,3,'.',',');
        $s3 = 'S3,'.$totalTransaction.','.$finalAmount; // Footer
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
        $transfer = Transfer::findOne((int)$id);

        if(!$transfer) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

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
                'bonus',
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
                "message" => 'Transfer not found!'
            ];
        }

        $this->layout = 'pdf';

        if($invoice['invoice_status'] == 'paid')
            $template = 'receipt';
        else
            $template = 'invoice';

        $content = $this->render($template, [
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
}
