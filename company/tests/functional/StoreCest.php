<?php
namespace company\tests;

use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use company\models\Company;
use company\models\Contact;
use company\models\Store;
use Yii;
use company\tests\FunctionalTester;
use company\models\ContactToken;
use common\fixtures\ContactTokenFixture;
use common\fixtures\StoreFixture;
use Codeception\Util\HttpCode;


class StoreCest
{
    public $token;
    public $company;

	public function _fixtures() {
		return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
			'store'        => StoreFixture::className()
		];
	}

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * List stores
     * @param FunctionalTester $I
     */
    public function testListing(FunctionalTester $I)
    {
        $I->wantTo('Validate company > stores api');
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    /**
     * ability to view store
     * @param \company\tests\FunctionalTester $I
     */
    public function testViewStore(FunctionalTester $I)
    {
        $store = $this->company->getStores()->one();

        $I->wantTo('View Store');
        $I->sendGET('v1/stores/view/' . $store->store_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List sub company stores
     * @param FunctionalTester $I
     */
    public function testViewChildStores(FunctionalTester $I) {
        $I->wantTo('Validate company > stores api to list sub company\'s stores');
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
        $I->sendGET('v1/stores/company-store');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View store
     * @param FunctionalTester $I
     */
    public function testViewStoreDetail(FunctionalTester $I) {
        $I->wantTo('Validate company > stores api to list sub company\'s stores');
        $I->sendGET('v1/stores/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
