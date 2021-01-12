<?php
namespace company\tests;

use common\models\ContactToken;
use common\fixtures\CompanyFixture;
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
        $token = ContactToken::find()
            ->filterWhere(['contact_uuid' => '16dbd631-9057-3926-bd42-cbac2ccd4246'])
            ->one();

        $I->amBearerAuthenticated($token->token_value);

        $I->wantTo('HR and Owner should able to list stores');
        $I->haveHttpHeader('company-id', 1);
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
        $token = ContactToken::find()
            ->filterWhere(['contact_uuid' => '20666f33-b761-35c0-8520-b8a1902f3190'])
            ->one();

        $I->amBearerAuthenticated($token->token_value);

        $I->wantTo('Other and Finance should not able to list stores');
        $I->haveHttpHeader('company-id', 1);
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);//400
    }
}
