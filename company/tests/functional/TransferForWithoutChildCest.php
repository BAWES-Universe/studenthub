<?php
namespace company\tests;

use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\models\CompanyContact;
use Yii;
use common\fixtures\ContactTokenFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use company\models\Transfer;
use company\models\Company;
use common\components\Excel;
use Codeception\Util\HttpCode;


class TransferForWithoutChildCest
{
    public $token, $companyWithoutChild;

    public function _fixtures() {
            return [
                'company' => CompanyFixture::className(),
                'companyContact' => CompanyContactFixture::className(),
                'contactToken' => ContactTokenFixture::className(),
                'candidate'    => CandidateFixture::className(),
                'invoice'    => InvoiceFixture::className(),
                'transferCandidate' => TransferCandidateFixture::className(),
            ];
    }

    public function _before(FunctionalTester $I)
    {
        Yii::$app->params['inCodeception'] = true;
        Yii::$app->params['transfer_cost'] = 0.35;
      
        $this->companyWithoutChild = Company::findOne(5);

        $companyContact = CompanyContact::find()
            ->filterWhere ([
                'allow_access' => 1,
                'company_id' => 5
            ])
            ->one();

        $this->token = $companyContact->contact->getAccessToken()->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader('Company-Id', $this->companyWithoutChild->company_id);

    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List transfers with relations for company without child
     * @param FunctionalTester $I
     */
    public function tryToListWithRelations(FunctionalTester $I)
    {
        $I->wantTo('List transfers with relations for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers?expand=invoices,transferCandidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View transfers for company without child
     * @param FunctionalTester $I
     */
    public function tryToViewForWithoutChild(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild->getTransfers()->one();

        $I->wantTo('View transfer with relations for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/' . $transfer->transfer_id . '?expand=invoices,transferCandidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Create transfers for company with child by excel
     * @param FunctionalTester $I
     */
    public function tryToCreateTransferByExcel(FunctionalTester $I)
    {
        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'transferByExcelForCompanyWithoutChild.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->companyWithoutChild->candidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
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
                    'header' => 'hours',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ]
            ]
        ]);

        //save in S3 temp bucket

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'temp-' . $fileName,
            [],
            sys_get_temp_dir() . '/' . $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        
        $I->wantTo('Create transfer for company with child by excel upload');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'form-data');
        $I->sendPOST('v1/transfers/create-by-excel', [
            "excel" => basename($response['ObjectURL']),
            "start_date" => '2010/10/10',
            "end_date" => '2010/12/10'
        ]);
        $I->seeResponseMatchesJsonType([
             'transfer_id' => 'integer'
        ]);

        unlink(sys_get_temp_dir() . '/' . $fileName);
    }

    /**
     * Edit transfers for company with child by excel
     * @param FunctionalTester $I
     */
    public function tryToEditTransferByExcel(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->isParentTransfer()
            ->one();

        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'transferByExcelForCompanyWithoutChild.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->companyWithoutChild->candidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
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
                    'header' => 'hours',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ]
            ]
        ]);

        //save in S3 temp bucket

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'temp-' . $fileName,
            [],
            sys_get_temp_dir() . '/' . $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        
        $I->wantTo('Create transfer for company with child by excel upload');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'form-data');
        $I->sendPATCH('v1/transfers/edit-by-excel/' . $transfer->transfer_id, [
            "excel" => basename($response['ObjectURL']),
            "start_date" => '2010/10/10',
            "end_date" => '2010/12/10'
        ]);
        $I->seeResponseContainsJson([
             'operation' => 'success'
        ]);

        unlink(sys_get_temp_dir() . '/' . $fileName);
    }

    /**
     * Create transfers for company without child
     * @param FunctionalTester $I
     */
    public function tryToCreateTransfer(FunctionalTester $I)
    {
        $candidates = $this->companyWithoutChild
                ->getCandidates()
                ->all();

        $arrCandidate = [];

        foreach ($candidates as $value)
        {
            $arrCandidate[] = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => $value->candidate_id
            ];
        }

        $I->wantTo('Create transfer for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/transfers', [
            'candidates' => $arrCandidate,
            "start_date" => '2010/10/10',
            "end_date" => '2010/12/10'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Edit transfers for company without child
     * @param FunctionalTester $I
     */
    public function tryToEditTransfer(FunctionalTester $I)
    {
        $candidates = $this->companyWithoutChild
                ->getCandidates()
                ->all();

        $arrCandidate = [];

        foreach ($candidates as $value)
        {
            $arrCandidate[] = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => $value->candidate_id
            ];
        }

        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();

        $I->wantTo('Edit transfer for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/' . $transfer->transfer_id, [
            'candidates' => $arrCandidate,
            "start_date" => '2010/10/10',
            "end_date" => '2010/12/10'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark transfers as "Payment Sent" for company without child
     * @param FunctionalTester $I
     */
    public function tryToMarkPaymentSent(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_LOCK])
            ->one();

        $I->wantTo('Mark transfer as "Payment Sent" for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/payment-sent/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark transfers as "Locked" for company without child
     * @param FunctionalTester $I
     */
    public function tryToMarkLocked(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();

        $I->wantTo('Mark transfer as "Locked" for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/lock/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Delete transfers for company without child
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();

        $I->wantTo('Delete transfer for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendDELETE('v1/transfers/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Download invoice for company without child
     * @param FunctionalTester $I
     *
    public function tryToDownloadInvoice(FunctionalTester $I)
    {
        $transfer = $this->companyWithoutChild
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_LOCK])
            ->one();

        $invoice = $transfer
            ->getInvoices()
            ->one();

        $I->wantTo('Download invoice for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/pdf/' . $invoice->invoice_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function tryToDownloadTransferExcel(FunctionalTester $I)
    {
        $I->wantTo('List transfers with relations for company without child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/transfer-excel-template');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }*/
}
