<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\CompanyTokenFixture;
use common\fixtures\StoreFixture;
use Codeception\Util\HttpCode;

class StoreCest
{
    public $token;
	public function _fixtures() {
		return [
			'companyToken' => CompanyTokenFixture::className(),
			'store'        => StoreFixture::className()
		];
	}

    public function _before(FunctionalTester $I)
    {
        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I){}

    /**
     * List stores
     * @param FunctionalTester $I
     */
    public function testListing(FunctionalTester $I)
    {
        $I->wantTo('Validate company > stores api');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function testViewStore(FunctionalTester $I)
    {
        $I->wantTo('View Store');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/stores/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List sub company stores
     * @param FunctionalTester $I
     */
    public function testStores(FunctionalTester $I) {
        $I->wantTo('Validate company > stores api to list sub company\'s stores');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/stores/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List store and sub companies
     * @param FunctionalTester $I
     */
    public function testSubCompanies(FunctionalTester $I) {
        $I->wantTo('Validate company > stores api to list stores and sub company');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/stores/company-store');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
