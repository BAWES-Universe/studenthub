<?php
namespace company\tests;

use common\models\ContactToken;
use common\models\Contact;
use common\models\CompanyContact;
use common\fixtures\CompanyFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\ContactTokenFixture;
use Codeception\Util\HttpCode;


class RoleCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _fixtures()
    {
        return [
            'companyContact' => CompanyContactFixture::className (),
            'company' => CompanyFixture::className (),
            'contactToken' => ContactTokenFixture::className ()
        ];
    }

    /**
     * Owner access
     * @param FunctionalTester $I
     */
    public function testOwnerAccess(FunctionalTester $I)
    {
        $contact = Contact::find()
            ->joinWith(['companyContacts'])
            ->filterWhere(['role' => CompanyContact::ROLE_OWNER])
            ->one();

        $token = $contact->getAccessToken();

        $I->amBearerAuthenticated($token->token_value);

        $I->wantTo('HR and Owner should able to list stores');
        $I->haveHttpHeader('Company-Id', $contact->companyContacts[0]->company_id);
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    /**
     * Owner access
     * @param FunctionalTester $I
     */
    public function testOtherAccess(FunctionalTester $I)
    {
        $contact = Contact::find()
            ->joinWith(['companyContacts'])
            ->filterWhere(['role' => CompanyContact::ROLE_OTHER])
            ->one();

        $token = $contact->getAccessToken();

        $I->amBearerAuthenticated($token->token_value);

        $I->wantTo('Other and Finance should not able to list stores');
        $I->haveHttpHeader('Company-Id', $contact->companyContacts[0]->company_id);
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);//400
    }
}
