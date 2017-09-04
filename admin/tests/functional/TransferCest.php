<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\fixtures\StoreFixture;
use admin\fixtures\CandidateFixture;
use admin\fixtures\CompanyFixture;
use common\fixtures\BankFixture;
use common\fixtures\TransferFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use admin\models\Transfer;
use Codeception\Util\HttpCode;

class TransferCest
{
    public $token;

	public function _fixtures() {
		return [
			'admin'             => [
				'class'    => AdminFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/admin.php'
			],
			'adminToken'        => [
				'class'    => AdminTokenFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/adminToken.php'
			],
			'company'           => [
				'class'    => CompanyFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/company.php'
			],
			'store'             => [
				'class'    => StoreFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/store.php'
			],
			'bank'              => [
				'class'    => BankFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/bank.php'
			],
			'candidate'         => [
				'class'    => CandidateFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/candidate.php'
			],
			'transfer'          => [
				'class'    => TransferFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/transfer.php'
			],
			'transferCandidate' => [
				'class'    => TransferCandidateFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/transferCandidate.php'
			],
			'invoice'           => [
				'class'    => InvoiceFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/invoice.php'
			]
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
        $I->wantTo('Validate admin > transfer > Mark All Candidate as Payment Received api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfers/mark-paid-all', [
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
    }

    /**
     * Download Payable Candidates' Detail
     * @param FunctionalTester $I
     */
    public function tryToDownloadPayable(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Payable Candidates\' Detail api');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/transfers/export-payable-candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
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
     * List Payable Candidates All
     * @param FunctionalTester $I
     */
    public function tryToListAllPayable(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > List Payable Candidates All api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/all-payable-candidates');
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
     * Export Transfer Detail
     * @param FunctionalTester $I
     */
    public function tryToExport(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Export Transfer Detail api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/export/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Download Transfer
     * @param FunctionalTester $I
     */
    public function tryToDownload(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer > Download Transfer api');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfers/pdf/' . $this->transferWithPaymentReceived->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

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
}
