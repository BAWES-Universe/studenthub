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
            'candidate' => CandidateFixture::className(),
            'candidateToken' => CandidateTokenFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryToPlaceDetail(FunctionalTester $I)
    {
        $I->wantTo('Validate google-map > place detail api response');
        $I->sendGET('v1/google-map/place-detail/1');
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
