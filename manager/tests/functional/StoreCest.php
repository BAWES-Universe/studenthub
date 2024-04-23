<?php
namespace manager\tests;

use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreManagerFixture;
use common\models\StoreManager;
use manager\models\Company;
use manager\models\Contact;
use manager\models\Store;
use Yii;
use manager\tests\FunctionalTester;
use common\models\ManagerToken;
use common\fixtures\ManagerTokenFixture;
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
            'contactToken' => ManagerTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
			'store'        => StoreFixture::className(),
            'manager' => StoreManagerFixture::className()
		];
	}

    public function _before(FunctionalTester $I)
    {
        $this->manager = StoreManager::find()->one();

        $this->token = $this->manager->getAccessToken()
            ->token_value;

        $this->company = $this->manager->getCompany()->one();

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I){}

    /**
     * ability to view store
     * @param \manager\tests\FunctionalTester $I
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
        $I->wantTo('Validate company > stores api to list sub manager\'s stores');
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
        $I->wantTo('Validate company > stores api to list sub manager\'s stores');
        $I->sendGET('v1/stores/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
