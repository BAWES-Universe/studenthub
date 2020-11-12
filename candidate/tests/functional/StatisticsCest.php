<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use common\models\CandidateToken;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'invoice' => InvoiceFixture::className()
        ];
    }

	public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()
                ->one();
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        $return = [];
        $stats =  $this->token->candidate->accountStatistic;

        $totalHours = (int)$stats['hours'];
        $totalPaid  = (int)$stats['paid'];
        $totalBonus = (int)$stats['bonus'];

        $return['total_hours'] = number_format($totalHours);
        $return['total_paid'] = $totalPaid;
        $return['total_bonus'] = $totalBonus;
        $return['total_earning'] = $totalPaid + $totalBonus;

        $I->wantTo('Validate candidate > statistics api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token->token_value);
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'total_hours' => number_format($totalHours),
            'total_paid' => $totalPaid,
            'total_bonus' => $totalBonus,
            'total_earning' => $totalPaid + $totalBonus
        ]);
    }
}
