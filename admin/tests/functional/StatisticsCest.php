<?php
namespace admin\tests;

use admin\models\Candidate;
use admin\models\Transfer;
use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\fixtures\AdminTokenFixture;
use common\fixtures\CandidateIdCardFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
{
    public $token;

    public function _fixtures() 
    {
        return [
            'adminToken' => AdminTokenFixture::class,
            'candidateIdCard' =>  CandidateIdCardFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()->one()->token_value;
        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Get statistics
     * @param FunctionalTester $I
     */
    public function tryToGetStatistics(FunctionalTester $I)
    {
        $payableDetail = Candidate::getTotalPayableCandidate();
        // Candidates
        $totalCandidate = Candidate::candidateCountByCondition();
        $totalAssignedToWork = Candidate::candidateCountByCondition('assigned');
        $approved = Candidate::candidateCountByCondition('approved');

        $result['candidates']['total_candidate'] = $totalCandidate;
        $result['candidates']['total_assigned'] = $totalAssignedToWork;
        $result['candidates']['total_unapproved'] = $totalCandidate - $approved;
        $result['payable']['total'] = $payableDetail['payable'];
        $result['payable']['amount'] = $payableDetail['amount'];

        // Transfers
        $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK);
        $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT);

        $result['transfers'] = [];
        $result['transfers']['locked'] = [
            "code" => Transfer::STATUS_LOCK,
            "total" => (isset($lockedTransfers['total']))? (int)$lockedTransfers['total'] : 0
        ];
        $result['transfers']['paymentSent'] = [
            "code" => Transfer::STATUS_PAYMENT_SENT,
            "total" => (isset($paymentSentTransfers['total']))? (int)$paymentSentTransfers['total'] : 0
        ];

        $I->wantTo('Validate admin > statistics api response');
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson($result);
    }

    /**
     * Get transfer statistics
     * @param FunctionalTester $I
     */
    public function tryToGetTransferStatistics(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer statistics api response');
        $I->sendGET('v1/statistics/transfer');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
