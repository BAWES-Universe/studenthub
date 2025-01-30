<?php

namespace admin\tests;

use yii;
use admin\tests\FunctionalTester;
use Codeception\Util\HttpCode;
use common\models\AdminToken;
use common\fixtures\AdminTokenFixture;
use common\fixtures\AdminFixture;

class AuthCest {

    public $token;

    public function _fixtures() {
        return [
            'adminToken' => AdminTokenFixture::class,
            'admin' => AdminFixture::class,
        ];
    }

    public function _before(FunctionalTester $I) {
        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I) {
        
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I) {
        $admin = new \admin\models\Admin;
        $admin->admin_name = 'Test';
        $admin->admin_email = 'test@me.admin';
        $admin->admin_auth_key = '';
        $admin->admin_status = '10';
        $admin->setPassword('12345');
        $admin->save();
        
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($admin->admin_email, '12345');
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
        $this->token = AdminToken::find()
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
     * Logout
     * @param FunctionalTester $I
     *
    public function tryToLogout(FunctionalTester $I) {
        $I->wantTo('Validate auth > logout api');
        $I->sendGET('v1/auth/logout');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }*/
}
