<?php

namespace candidate\tests;

use yii;
use candidate\models\Candidate;
use candidate\tests\FunctionalTester;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;
use common\models\CandidateToken;

class AuthCest {

    public $token;
    public $candidate;

    public function _fixtures() {
        return [
            'candidates' => CandidateFixture::class,
            'candidateToken' => CandidateTokenFixture::class
        ];
    }

    public function _before(FunctionalTester $I) {

        $this->candidate = Candidate::findOne(['candidate_email_verification'=>1]);
        
        $this->token = $this->candidate->getAccessToken()->token_value;
        
        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I) {
        
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I) {
        $candidate = Candidate::findOne(['candidate_email_verification'=>1]);

        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($candidate->candidate_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'id' => $candidate->candidate_id,
            'name' => $candidate->candidate_name,
            'email' => $candidate->candidate_email,
            'language_pref' => $candidate->candidate_language_pref,
            'approved' => $candidate->approved
        ]);
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
        $this->token = CandidateToken::find()
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
     * Try to update password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I) {
        $candidate =  Candidate::findOne(['candidate_id'=>$this->candidate->candidate_id]);
        $candidate->setScenario('changePassword');
        $candidate->candidate_password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
        $candidate->save(false);

        $I->wantTo('Validate auth > update-password api');
        $I->sendPATCH('v1/auth/update-password', [
            'newPassword' => 'demo1admin',
            'token' => $candidate->candidate_password_reset_token
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'message' => 'Your password has been reset'
        ]);
    }
    
    /**
     * Try to validate email
     * @param FunctionalTester $I
     */
    public function tryToValidateEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > email-check api');
        $I->sendPOST('v1/auth/email-check', [
            'email' => $this->candidate->candidate_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "candidate_id"=>$this->candidate->candidate_id
        ]);
    }
    
    /**
     * Try to register
     * @param FunctionalTester $I
     */
    public function tryToRegister(FunctionalTester $I) {
        $I->wantTo('Validate auth > register api');
        $I->sendPOST('v1/auth/register', [
            'name' => 'demo com',
            'lang' => 'en',
            'email' => 'demo@demo.com',
            'phone' => 12345678,
            'password' => 'demo1admin'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'message' => 'Please click on the link sent to you by email to verify your account',
        ]);
    }
    
    /**
     * Try to reset password
     * @param FunctionalTester $I
     */
    public function tryToResetPassword(FunctionalTester $I) {
        $I->wantTo('Validate auth > request-reset-password api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => $this->candidate->candidate_email,
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'operation' => 'success',
            'message' => 'Please check the link sent to you on your email to set new password.'
        ]);
    }
    
    /**
     * Try to check if email verified
     * @param FunctionalTester $I
     */
    public function tryToCheckIfEmailVerified(FunctionalTester $I) {
        $candidate = Candidate::findOne(['candidate_email_verification'=>0]);
        $I->wantTo('Validate auth > is-email-verified api');
        $I->sendPOST('v1/auth/is-email-verified', [
            'email' => $candidate->candidate_email,
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'status' => 0
        ]);
    }
    
    /**
     * Try to update email
     * @param FunctionalTester $I
     */
    public function tryToUpdateEmail(FunctionalTester $I) {
        $I->wantTo('Validate auth > update-email api');
        $I->sendPOST('v1/auth/update-email', [
            'newEmail' => 'abc@test.com',
            'unVerifiedToken' => $this->token
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'message' => 'Candidate Account Info Updated Successfully, please check email to verify new email address'
        ]);
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
        $I->seeResponseContainsJson([
            "operation"=>"error",
            "errorCode"=>1,
            "message"=>"You have verified your email"
        ]);
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
        $I->seeResponseContainsJson([
            'email' => $this->candidate->candidate_email
        ]);
    }

}

