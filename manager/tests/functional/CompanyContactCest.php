<?php


namespace manager\tests;

use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ManagerTokenFixture;
use common\fixtures\StoreManagerFixture;
use common\components\StoreManager;
use manager\models\Contact;


class CompanyContactCest
{
    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ManagerTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
            'manager' => StoreManagerFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->manager = StoreManager::find()->one();

        $this->token = $this->manager->getAccessToken()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function listCompanyContact(FunctionalTester $I)
    {
        $I->sendGET('v1/company-contacts');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function viewCompanyContactAccess(FunctionalTester $I)
    {
        $I->sendGET('v1/company-contacts/view-company-contact?uuid=' . 1);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function viewCompanyContact(FunctionalTester $I)
    {
        $I->sendGET('v1/company-contacts/' . 1);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}