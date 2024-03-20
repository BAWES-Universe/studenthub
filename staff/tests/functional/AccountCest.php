<?php
namespace staff\tests;

use yii;
use staff\tests\FunctionalTester;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;


class AccountCest
{
    public $token;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className(),
            'staff' => StaffFixture::className(),
        ];
    }

    /**
     * @param \staff\tests\FunctionalTester $I
     * @return void
     */
    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()->one()->token_value;
        
        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * @param \staff\tests\FunctionalTester $I
     * @return void
     */
    public function _after(FunctionalTester $I){}

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $I->amGoingTo('Try to update password');
        $I->sendPOST('v1/account/update-password', array('password' => '123123', 'newPassword' => '12122'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
