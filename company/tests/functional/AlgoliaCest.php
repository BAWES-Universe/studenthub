<?php
namespace company\tests;

use yii;
use common\models\CompanyToken;
use common\fixtures\CompanyTokenFixture;
use Codeception\Util\HttpCode;


class AlgoliaCest
{
    public $token;

    public function _fixtures() {
        return [
            'tokens' => CompanyTokenFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CompanyToken::find()
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
        $I->wantTo('get algolia secure key');
        $I->sendGET('v1/algolia/key');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
