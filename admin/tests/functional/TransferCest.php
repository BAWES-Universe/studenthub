<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\fixtures\Store as StoreFixture;
use admin\fixtures\Candidate as CandidateFixture;
use admin\fixtures\Company as CompanyFixture;
use common\fixtures\Bank as BankFixture;
use common\fixtures\Transfer as TransferFixture;
use common\fixtures\TransferCandidate as TransferCandidateFixture;
use common\fixtures\Invoice as InvoiceFixture;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\models\AdminToken;
use admin\models\Transfer;
use Codeception\Util\HttpCode;

class TransferCest
{
    public $token;
    
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/admin.php'                
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/adminToken.php'
            ],
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'
            ],
            'bank' => [
                'class' => BankFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/bank.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'
            ],
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transfer.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transferCandidate.php'
            ],
            'invoice' => [
                'class' => InvoiceFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/invoice.php'
            ]
        ]);
        
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {        
        $transferWithPaymentSent = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_PAYMENT_SENT
                ])
                ->isParentTransfer()
                ->one();
        
        $lockedTransfer = Transfer::find()
            ->where([
                'transfer_status' => Transfer::STATUS_LOCK
            ])
            ->isParentTransfer()    
            ->one();      
                
        $transferWithPaymentReceived = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
                ])
                ->isParentTransfer()
                ->one();
        
        // list transfers
        
        $I->wantTo('Validate admin > transfer api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        // view transfer 
        
        $I->wantTo('Validate admin > transfer > view transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/' . $transferWithPaymentSent->transfer_id . '?expand=invoices,transferCandidates,totalPaid,totalUnpaid,profit ');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        // Mark Received & Distributing Salary
        
        $I->wantTo('Validate admin > transfer > Mark Received & Distributing Salary api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfers/payment-received-distributing/' . $transferWithPaymentSent->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        // Unlock Transfer
        
        $I->wantTo('Validate admin > transfer > Unlock Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfers/unlock/' . $lockedTransfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        // lock Transfer 
                
        $transferWithPaymentSent2 = Transfer::find()
                ->where([
                    'transfer_status' => Transfer::STATUS_PAYMENT_SENT
                ])
                ->isParentTransfer()
                ->one();
        
        $I->wantTo('Validate admin > transfer > Lock Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfers/lock/' . $transferWithPaymentSent2->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // Mark All Candidate as Payment Received 
        
        $I->wantTo('Validate admin > transfer > Mark All Candidate as Payment Received api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfers/mark-paid-all', [
            'candidates' => [
                [
                    'candidate_id' => 6,
                    'transfer_id' => 17
                ],
                [
                    'candidate_id' => 7,
                    'transfer_id' => 17
                ]
            ]            
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // Download Payable Candidates' Detail 
        
        $I->wantTo('Validate admin > transfer > Download Payable Candidates\' Detail api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/export-payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        // List Payable Candidates 
        
        $I->wantTo('Validate admin > transfer > List Payable Candidates api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // List Payable Candidates All
        
        $I->wantTo('Validate admin > transfer > List Payable Candidates All api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/all-payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // Download Invoice as TEXT - {{url-admin}}/transfers/text 
        
        $I->wantTo('Validate admin > transfer > Download Invoice as TEXT api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/text');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        // Export Transfer Detail
        
        $I->wantTo('Validate admin > transfer > Export Transfer Detail api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/export/' . $transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        // Download Transfer
        
        $I->wantTo('Validate admin > transfer > Download Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfers/pdf/' . $transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        // List Candidate Transfers 
        
        $I->wantTo('Validate admin > transfer > List Candidate Transfers api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('transfer-candidates?tc_id=6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // Mark Candidate Transfers as unpaid
        
        $I->wantTo('Validate admin > transfer > Mark Candidate Transfers as unpaid');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfer-candidates/unpaid/6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        // Mark Candidate Transfers as Paid
        
        $I->wantTo('Validate admin > transfer > Mark Candidate Transfers as Paid');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('transfer-candidates/paid/6');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
