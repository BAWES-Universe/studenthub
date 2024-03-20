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

    /**
     * @param FunctionalTester $I
     * @return void
     */
	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function _after(FunctionalTester $I){}

    /**
     * get public key for index reading
     * @param FunctionalTester $I
     */
    public function tryToGetAlgoliaKey(FunctionalTester $I)
    {
        $I->wantTo('get algolia key');
        $I->sendGET('v1/algolia/key');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
