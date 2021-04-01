<?php
namespace company\tests;

use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactFixture;
use yii;
use common\models\ContactToken;
use common\fixtures\ContactTokenFixture;
use Codeception\Util\HttpCode;


class AlgoliaCest
{
    public $token;

    public function _fixtures() {
        return [
            'tokens' => ContactTokenFixture::className(),
            'company' => CompanyFixture::className (),
            'companyContact' => CompanyContactFixture::className (),
            //'contact' => ContactFixture::className ()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = ContactToken::find()
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
