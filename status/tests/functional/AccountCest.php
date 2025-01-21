<?php
namespace status\tests;

use Yii;
use Codeception\Util\HttpCode;
use common\models\InspectorToken;
use common\fixtures\InspectorFixture;
use common\fixtures\InspectorTokenFixture;


class AccountCest
{
    public function _fixtures()
    {
        return [
            'statuss' => InspectorFixture::class,
            'token' => InspectorTokenFixture::class
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = InspectorToken::find()
                ->one()
                ->token_value;


        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I){}

    // tests
//    public function testChangePassword(FunctionalTester $I)
//    {
//        $I->wantTo('trying to change password');
//        $I->amBearerAuthenticated($this->token);
//        $I->sendPOST('v1/account/update-password', [
//            'password' => '12345',
//            'newPassword' => 'newPassword'
//        ]);
//        $I->seeResponseCodeIs(HttpCode::OK); // 200
//    }
}
