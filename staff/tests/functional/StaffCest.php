<?php
namespace staff\tests;

use common\fixtures\StaffFixture;
use yii;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class StaffCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'staffToken' => StaffTokenFixture::class,
			'staff' => StaffFixture::class
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I){}

    /**
     * list all staff
     * @param FunctionalTester $I
     */
    public function listStaff(FunctionalTester $I)
    {
        $I->wantTo('get staff listing');
        $I->sendGET('v1/staff');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['staff_id'=>2]);
    }

    /**
     * view university by id
     * @param FunctionalTester $I
     */
    public function viewStaff(FunctionalTester $I)
    {
        $I->wantTo('get staff listing');
        $I->sendGET('v1/staff/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['staff_id'=>1]);
    }
}

