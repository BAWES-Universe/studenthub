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
            'staffToken' => StaffTokenFixture::class
        ];
	}

    /**
     * @param FunctionalTester $I
     * @return void
     */
	public function _before(FunctionalTester $I)
	{
		$this->token = StaffToken::find()
                ->one()
                ->token_value;

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
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
        $staff->staff_status = '10';
        $staff->save(false);
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($staff->staff_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Login with wrong password
     * @param FunctionalTester $I
     */
    public function tryToLoginWithWrongPassword(FunctionalTester $I) {
        $I->wantTo('Validate auth > login with wrong password api');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);     
        $I->seeResponseIsJson();
    }

    /**
     * Login with two step verification token
     * @param FunctionalTester $I
     */
    public function tryToLoginWithTwoStepVerificationToken(FunctionalTester $I) {
        $this->token = StaffToken::find()
             ->one();
 
        $this->token->otp = 12344;
        $this->token->token_status = 0;
        $this->token->save();

        $I->wantTo('Validate auth > login with two step verification api');
        $I->sendPOST('v1/auth/login-two-step', ['token' => $this->token->token_value, 'otp' => $this->token->otp]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Login with invalid two step verification OTP
     * @param FunctionalTester $I
     */
    public function tryToLoginWithInvalidTwoStepVerificationOTP(FunctionalTester $I) {
        $I->wantTo('Validate auth > login with two step verification api');
        $I->sendPOST('v1/auth/login-two-step', ['token' => 'test@me.admin', 'otp' => '12345']);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED); // 200
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
