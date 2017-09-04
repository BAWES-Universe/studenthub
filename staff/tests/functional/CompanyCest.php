<?php
namespace staff\tests;

use yii;
use staff\tests\FunctionalTester;
use common\models\StaffToken;
use staff\fixtures\StaffTokenFixture;
use staff\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class CompanyCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'staff'      => [
				'class'    => StaffFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staff.php'
			],
			'staffToken' => [
				'class'    => StaffTokenFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staffToken.php'
			],
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
