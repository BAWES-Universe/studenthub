<?php
namespace inspector\tests;

use inspector\tests\FunctionalTester;
use yii;
use inspector\models\Inspector;
use common\models\InspectorToken;
use common\fixtures\InspectorTokenFixture;
use common\fixtures\InspectorFixture;
use Codeception\Util\HttpCode;


class AuthCest
{
    public $token;

    /**
     * @return array
     */
	public function _fixtures()
	{
        return [
            'inspectors' => InspectorFixture::class,
            'token' => InspectorTokenFixture::class
        ];
	}

    /**
     * @param \inspector\tests\FunctionalTester $I
     * @return void
     */
	public function _before(FunctionalTester $I)
	{
		$this->token = InspectorToken::find()
                ->one()
                ->token_value;

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * @param \inspector\tests\FunctionalTester $I
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
    	$inspector = Inspector::find()->one();

        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($inspector->inspector_email, '12345');
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
        $this->token = InspectorToken::find()   
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
        $inspector = Inspector::find()->one();

        $inspector->generatePasswordResetToken();
        $inspector->save(false);

        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $inspector->inspector_password_reset_token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'operation' => 'success'
        ]);
    }

    /**
     * Try to reset password
     * @param FunctionalTester $I
     */
    public function tryToResetPassword(FunctionalTester $I) {
        $inspector = Inspector::find()->one();

        $I->wantTo('Validate auth > request-reset-password api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => $inspector->inspector_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'operation' => 'success'
        ]);
    }
}
