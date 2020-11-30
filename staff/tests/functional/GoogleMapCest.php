<?php
namespace staff\tests;

use yii;
use common\models\StaffToken; 
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;


class GoogleMapCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryGetAreaByLocation(FunctionalTester $I)
    {
        $I->amGoingTo('try to get area by location');
        $I->sendGET('v1/google-map/area-by-location?latitude=70&longitude=70');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([ 'operation' => 'success']);
    }

    public function tryToPlaceDetail(FunctionalTester $I)
    {
        $I->wantTo('Validate google-map > place detail api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/google-map/place-detail/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function tryToPlacePrediction(FunctionalTester $I)
    {
        $I->wantTo('Validate google-map > place predictions api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/google-map/place-predictions?query=kuwait');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

}
