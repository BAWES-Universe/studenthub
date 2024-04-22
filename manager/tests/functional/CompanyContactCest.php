<?php


namespace company\tests;

use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use company\models\Contact;


class CompanyContactCest
{
    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
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
        $I->sendGET('v1/company-contacts/view-company-contact?contact_uuid=' . $this->contact->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function viewCompanyContact(FunctionalTester $I)
    {
        $I->sendGET('v1/company-contacts/' . $this->contact->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}