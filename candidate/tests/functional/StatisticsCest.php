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
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        $I->wantTo('Validate candidate > statistics api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
