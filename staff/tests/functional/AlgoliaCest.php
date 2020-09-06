<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;


class AlgoliaCest
{
    public $token;

	public function _fixtures() {
		return [
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

    public function _after(FunctionalTester $I){}

    /**
     * get public key for index reading
     * @param FunctionalTester $I
     */
    public function tryToGetAlgoliaKey(FunctionalTester $I)
    {
        $I->wantTo('get Bank listing');
        $I->sendGET('v1/algolia/key');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
