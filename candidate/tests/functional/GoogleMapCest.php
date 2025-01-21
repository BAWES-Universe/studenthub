<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use common\models\CandidateToken; 
use common\fixtures\CandidateTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class GoogleMapCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidate' => CandidateFixture::class,
            'candidateToken' => CandidateTokenFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryToPlaceDetail(FunctionalTester $I)
    {
        $I->wantTo('Validate google-map > place detail api response');
        $I->sendGET('v1/google-map/place-detail/ChIJWZXnT4IIzz8RD-6elZ0eaTQ');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function tryToPlacePrediction(FunctionalTester $I)
    {
        $I->wantTo('Validate google-map > place predictions api response');
        $I->sendGET('v1/google-map/place-predictions?query=kuwait');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "description"=>"Kuwait City, Kuwait"
        ]);
    }
}
