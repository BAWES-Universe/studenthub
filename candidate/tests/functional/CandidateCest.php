<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use common\models\CandidateToken;
use common\fixtures\CandidateFixture;
use common\fixtures\CandidateTokenFixture;
use Codeception\Util\HttpCode;


class CandidateCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'candidate' => CandidateFixture::className()
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

    public function tryToTest(FunctionalTester $I)
    {
        $I->wantTo('Validate candidate > work-history api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token->token_value);
        $I->sendGET('v1/candidates/work-history');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
