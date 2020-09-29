<?php

namespace candidate\tests;

use yii;
use candidate\models\Candidate;
use candidate\tests\FunctionalTester;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class AuthCest {

    public $token;

    public function _fixtures() {
        return [
            'candidates' => CandidateFixture::className(),
            'candidateToken' => CandidateTokenFixture::className()
        ];
    }

    public function _before(FunctionalTester $I) {
        
        $this->candidate = Candidate::find()
            ->one();
        
        $this->token = $this->candidate->getAccessToken()->token_value;
        
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I) {
        
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I) {
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated('jennie50@gmail.com', '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to update password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I) {
        $I->wantTo('Validate auth > update-password api');
        $I->sendPATCH('v1/auth/update-password', [
            'newPassword' => 'demo1admin',
            'token' => $this->token
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to validate email
     * @param FunctionalTester $I
     */
    public function tryToValidateEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > email-check api');
        $I->sendPOST('v1/auth/email-check', [
            'email' => 'demo@demo.com'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to register
     * @param FunctionalTester $I
     */
    public function tryToRegister(FunctionalTester $I) {
        $I->wantTo('Validate auth > register api');
        $I->sendPOST('v1/auth/register', [
            'name' => 'demo@demo.com',
            'lang' => 'en',
            'email' => 'demo@demo.com',
            'phone' => 12345678,
            'password' => 'demo1admin'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to reset password
     * @param FunctionalTester $I
     */
    public function tryToResetPassword(FunctionalTester $I) {
        $I->wantTo('Validate auth > request-reset-password api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => 'demo@demo.com',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to check if email verified
     * @param FunctionalTester $I
     */
    public function tryToCheckIfEmailVerified(FunctionalTester $I) {
        $I->wantTo('Validate auth > is-email-verified api');
        $I->sendPOST('v1/auth/is-email-verified', [
            'email' => 'demo@demo.com',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to update email
     * @param FunctionalTester $I
     */
    public function tryToUpdateEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > update-email api');
        $I->sendPOST('v1/auth/update-email', [
            'newEmail' => 'demo@demo.com',
            'unVerifiedToken' => $this->token
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to get verification email
     * @param FunctionalTester $I
     */
    public function tryToGetVerificationEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > resend-verification-email api');
        $I->sendPOST('v1/auth/resend-verification-email', [
            'email' => $this->candidate->candidate_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * Try to verify email
     * @param FunctionalTester $I
     */
    public function tryToVerifyEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > verify-email api');
        $I->sendPOST('v1/auth/verify-email', [
            'email' => $this->candidate->candidate_email,
            'code' => $this->candidate->candidate_auth_key
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}

