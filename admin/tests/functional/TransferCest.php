<?php
namespace admin\tests;

use Yii;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use common\components\Excel;
use admin\models\Transfer;
use Codeception\Util\HttpCode;


class TransferCest
{
    public $token;

    public function _fixtures() 
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'invoice' => InvoiceFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;

        $this->transferWithPaymentSent = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_PAYMENT_SENT
                ])
                ->isParentTransfer()
                ->one();

        $this->lockedTransfer = Transfer::find()
            ->where([
                'transfer_status' => Transfer::STATUS_LOCK
            ])
            ->isParentTransfer()
            ->one();

        $this->transferWithPaymentReceived = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
                ])
                ->isParentTransfer()
                ->one();
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * list transfers
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View transfers
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > view transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/' . $this->transferWithPaymentSent->transfer_id . '?expand=invoices,transferCandidates,totalPaid,totalUnpaid,profit ');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark Received & Distributing Salary
     * @param FunctionalTester $I
     */
    public function tryToMarkReceived(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Mark Received & Distributing Salary api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/payment-received-distributing/' . $this->transferWithPaymentSent->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Unlock Transfer
     * @param FunctionalTester $I
     */
    public function tryToUnlock(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Unlock Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/unlock/' . $this->lockedTransfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Lock transfer
     * @param FunctionalTester $I
     */
    public function tryToLock(FunctionalTester $I)
    {
        $transferWithPaymentSent2 = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_PAYMENT_SENT
                ])
                ->isParentTransfer()
                ->one();

        $I->wantTo('Validate admin > transfer > Lock Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/lock/' . $transferWithPaymentSent2->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark All Candidate as Payment Received
     * @param FunctionalTester $I
     */
    public function tryToMarkCandidateReceived(FunctionalTester $I)
    {
        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'excelForCompanyWithout.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->transferWithPaymentReceived->transferCandidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
            'columns' => [
                [
                    'header' => 'Status',
                    'value' => 'SUCCESS'
                ],
                [
                    'header' => 'Credit Narrative',
                    'value' => function($data) {
                        return $data->tc_id;
                    }
                ],
                [
                    'header' => 'Status Description',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'Debit Narrative',
                    'value' => function($data) {
                        return $data->transfer_id;
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
        
        @unlink(sys_get_temp_dir() . '/' . $fileName);
        
        $I->wantTo('Validate admin > transfer > Mark All Candidate as Payment Received api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/mark-paid-all', [
            'excel' => basename($response['ObjectURL']),
            'candidates' => [
                [
                    'transfer_id' => $this->transferWithPaymentReceived->transfer_id,
                    'transfer_confirmation_id' => 6,
                    'tc_id' => $this->transferWithPaymentReceived->transferCandidates[0]->tc_id
                ],
            ]
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);  //200
        $I->seeResponseIsJson();
    }
    
    /**
     * List Payable Candidates
     * @param FunctionalTester $I
     */
    public function tryToListPayable(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > List Payable Candidates api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Download Invoice as TEXT
     * @param FunctionalTester $I
    */
    public function tryToAsText(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Invoice as TEXT api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/text');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * Download Payable Candidates' Detail
     * @param FunctionalTester $I
     * @param \admin\tests\FunctionalTester $I
     *
    public function tryToDownloadPayable(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Payable Candidates\' Detail api');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/transfers/export-payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }*/
    
    /**
     * Export Transfer Detail
     * @param FunctionalTester $I
     *
    public function tryToExport(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Export Transfer Detail api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/export/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    } */   
        
    /**
     * List Candidate Transfers
     * @param FunctionalTester $I
     */
    public function tryToListCandidateTransfers(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > List Candidate Transfers api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfer-candidates?tc_id=6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark Candidate Transfers as unpaid
     * @param FunctionalTester $I
     */
    public function tryToMarkCandidateTransferUnpaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Mark Candidate Transfers as unpaid');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/unpaid/6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Mark Candidate Transfers as Paid
     * @param FunctionalTester $I
     */
    public function tryToMarkCandidateTransferPaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Mark Candidate Transfers as Paid');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/paid/6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Download Transfer Invoice
     * @param FunctionalTester $I
     */
    public function tryToDownloadInvoice(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Transfer invoice api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/pdf/invoice/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * Download Transfer Receipt
     * @param FunctionalTester $I
     */
    public function tryToDownloadReceipt(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Transfer receipt api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/pdf/receipt/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * List Transfer Invoices
     * @param FunctionalTester $I
     */
    public function tryToListInvoices(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > View Transfer invoices api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/invoices/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * Import data from excel provided by bank
     * @param FunctionalTester $I
     */
    public function tryToImportExcel(FunctionalTester $I)
    {
        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'excelForCompanyWithout.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->transferWithPaymentReceived->transferCandidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
            'columns' => [
                [
                    'header' => 'Status',
                    'value' => 'SUCCESS'
                ],
                [
                    'header' => 'Credit Narrative',
                    'value' => function($data) {
                        return $data->tc_id;
                    }
                ],
                [
                    'header' => 'Status Description',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'Debit Narrative',
                    'value' => function($data) {
                        return $data->transfer_id;
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
        
        $I->wantTo('Validate admin > transfer > import excel api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/transfers/import-excel', [
            'excel' => basename($response['ObjectURL'])
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    } 
}

                    
                      
                    
                      
                        
                     