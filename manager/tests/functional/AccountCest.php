<?php
namespace manager\tests;

use common\fixtures\StoreFixture;
use common\fixtures\StoreManagerFixture;
use Yii;
use common\models\ManagerToken;
use common\fixtures\CompanyFixture;
use common\fixtures\ManagerTokenFixture;
use Codeception\Util\HttpCode;


class AccountCest
{
	public function _fixtures() {
		return [
            'manager' => StoreManagerFixture::class,
            'managerToken' => ManagerTokenFixture::class,

            "company" => CompanyFixture::class,
            "stores" => StoreFixture::class,

		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = ManagerToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * Try to update profile
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update profile via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/account/update',
            [
                'name' => 'davert',
                'email' => 'ravan@lanka.com',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function tryToGetProfile(FunctionalTester $I)
    {
        $I->amGoingTo('Validate account > profile api');
        $I->sendGET('v1/account/view');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        //$I->seeResponseContainsJson(['uuid' => $this->candidate->candidate_id]);
    }

    /**
     * try to update password for login user
     * @param \manager\tests\FunctionalTester $I
     */
    public function testChangePassword(FunctionalTester $I)
    {
        $I->wantTo('trying to change password');

        $I->sendPOST('v1/account/change-password', [
            'old_password' => '12345',
            'new_password' => 'newPassword'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Password changed successfully!"
        ]);
    }
}
