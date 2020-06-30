<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use common\fixtures\CompanyTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
	public function _fixtures()
	{
        return [
            'companyToken' => CompanyTokenFixture::className()
        ];
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Try to login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated('ukertzmann@leffler.net', '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update Password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => 'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
