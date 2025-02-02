<?php
namespace company\tests;

use Yii;
use company\models\ContactToken;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\ContactFixture;
use Codeception\Util\HttpCode;


class AccountCest
{
	public function _fixtures() {
		return [
            'company' => CompanyFixture::class,
            'contact' => ContactFixture::class,
			'contactToken' => ContactTokenFixture::class
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = ContactToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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
                //'position' => 'Java developer',
                'emails' => [
                    [
                        'email_address' => 'demo@demo.com'
                    ]
                ],
                'phones' => [
                    [
                        'phone_number' => '12345678'
                    ]
                ]
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
        //$I->seeResponseContainsJson(['contact_uuid' => $this->candidate->candidate_id]);
    }

    /**
     * try to update password for login user
     * @param \company\tests\FunctionalTester $I
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
