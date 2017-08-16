<?php
namespace company\tests;

use Yii;
use company\fixtures\Company as CompanyFixture;
use company\fixtures\CompanyToken as CompanyTokenFixture;
use company\fixtures\Store as StoreFixture;
use common\fixtures\Bank as BankFixture;
use company\fixtures\Candidate as CandidateFixture;
use company\fixtures\Transfer as TransferFixture;
use company\fixtures\TransferCandidate as TransferCandidateFixture;
use company\fixtures\Invoice as InvoiceFixture;
use company\tests\FunctionalTester;
use company\models\Transfer;
use company\models\Company;
use Codeception\Util\HttpCode;

class TransferCest
{
    public $token, $companyWithChild;
    
    public function _before(FunctionalTester $I)
    {
        Yii::$app->params['inCodeception'] = true;
        Yii::$app->params['transfer_cost'] = 0.35;
        Yii::$app->params['candidate_max_hourly_rate'] = 2;
        
        $I->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'                
            ],
            'companyToken' => [
                'class' => CompanyTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/companyToken.php'
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
        
        $this->companyWithChild = Company::findOne(1);
        
        $this->token = $this->companyWithChild->accessToken->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List transfers with relations for company with child
     * @param FunctionalTester $I
     */
    public function tryToListWithRelations(FunctionalTester $I)
    {
        $I->wantTo('List transfers with relations for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('v1/transfers?expand=invoices,transferCandidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
        
    /**
     * View transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $transfer = $this->companyWithChild->getTransfers()->one();
                
        $I->wantTo('View transfer with relations for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('v1/transfers/' . $transfer->transfer_id . '?expand=invoices,transferCandidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Create transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToCreateTransfer(FunctionalTester $I)
    {
        $candidates = $this->companyWithChild
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

        $I->wantTo('Create transfer for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPOST('v1/transfers', [
            'candidates' => $arrCandidate
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
     
    /**
     * Edit transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToEditTransfer(FunctionalTester $I)
    {
        $candidates = $this->companyWithChild
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

        $transfer = $this->companyWithChild
            ->getTransfers()    
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();
        
        $I->wantTo('Edit transfer for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('v1/transfers/'. $transfer->transfer_id, [
            'candidates' => $arrCandidate
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
        
    /**
     * Mark transfers as "Payment Sent" for company with child
     * @param FunctionalTester $I
     */
    public function tryToMarkPaymentSent(FunctionalTester $I)
    {
        $transfer = $this->companyWithChild
            ->getTransfers()    
            ->where(['transfer_status' => Transfer::STATUS_LOCK])
            ->one();
        
        $I->wantTo('Mark transfer as "Payment Sent" for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('v1/transfers/payment-sent/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
        
    /**
     * Mark transfers as "Locked" for company with child
     * @param FunctionalTester $I
     */
    public function tryToMarkLocked(FunctionalTester $I)
    {
        $transfer = $this->companyWithChild
            ->getTransfers()    
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();
        
        $I->wantTo('Mark transfer as "Locked" for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendPATCH('v1/transfers/lock/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Delete transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $transfer = $this->companyWithChild
            ->getTransfers()    
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();
        
        $I->wantTo('Delete transfer for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendDELETE('v1/transfers/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
        
    /**
     * Download invoice for company with child
     * @param FunctionalTester $I
     */
    public function tryToDownloadInvoice(FunctionalTester $I)
    {
        $transfer = $this->companyWithChild
            ->getTransfers()   
            ->where(['transfer_status' => Transfer::STATUS_LOCK])    
            ->one();
        
        $invoice = $transfer
            ->getInvoices()  
            ->one();
        
        $I->wantTo('Download invoice for company with child');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('v1/transfers/pdf/' . $invoice->invoice_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
