<?php
namespace staff\tests;

use yii;
use staff\tests\FunctionalTester;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class CompanyCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'staff'      => StaffFixture::className(),
			'staffToken' => StaffTokenFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List companies
     * @param FunctionalTester $I
     */
    public function tryToListing(FunctionalTester $I)
    {
        $I->wantTo('get Company listing');
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
