<?php
namespace staff\tests;

use staff\models\Staff;
use yii;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'staffToken' => StaffTokenFixture::className()
        ];
	}

	public function _before(FunctionalTester $I)
	{
		$this->token = StaffToken::find()
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I)
    {
    	$staff = Staff::find()->one();
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($staff->staff_email, 'password_'.($staff->staff_id-1));
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $this->token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
