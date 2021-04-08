<?php
namespace company\tests;

use company\models\Contact;
use common\fixtures\CompanyContactFixture;
use common\fixtures\ContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use Codeception\Util\HttpCode;


class AuthCest
{
    public $token;
    public $company;
    public $contact;

	public function _fixtures()
	{
        return [
            'companyContact' => CompanyContactFixture::className(),
            'company' => CompanyFixture::className(),
            'contact' => ContactFixture::className(),
            'contactToken' => ContactTokenFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();
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
        $I->amHttpAuthenticated($this->contact->contact_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
            "company_id"=> $this->company->company_id
        ]);
    }

    /**
     * abbility to signup
     * @param \company\tests\FunctionalTester $I
     */
    public function tryToSignup(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > create account api');
        $I->sendPOST('v1/auth/create-account', [
            "name" => "Mohanchand",
            "email" => "mohan@localhost.com",
            "password" => 12345,
            "receive_email" => true,
            "phone_number" => 87384334,
            "company_name" => "Milton",
            "contact_position" => "CEO"
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
        ]);
    }

    /**
     * Check if email got verified
     * @param FunctionalTester $I
     */
    public function tryToCheckEmailVerificationStatus(FunctionalTester $I)
    {
        $model = Contact::find()->one();
        $model->contact_email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to check if email got verified');
        $I->sendPOST('v1/auth/is-email-verified', [
            'token' => $model->getAccessToken()->token_value
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    /**
     * Resend Verification Email
     * @param FunctionalTester $I
     */
    public function tryToResendVerificationEmail(FunctionalTester $I)
    {
        $model = Contact::find()->one();
        $model->contact_email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to get verification again by email');
        $I->sendPOST('v1/auth/resend-verification-email', [
            'email' => $model->contact_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    /**
     * Resend Verify Email
     * @param FunctionalTester $I
     */
    public function tryToVerifyEmail(FunctionalTester $I)
    {
        $model = Contact::find()->one();
        $model->contact_email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to verify email by code');
        $I->sendPOST('v1/auth/verify-email', [
            'code' => $model->contact_auth_key,
            'email' => $model->contact_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    public function tryToUpdateEmail(FunctionalTester $I)
    {
        $model = Contact::find()->one();
        $model->contact_email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to verify email by code');
        $I->sendPOST('v1/auth/update-email', [
            'unVerifiedToken' => $model->getAccessToken()->token_value,
            'newEmail' => 'new@localhost.com'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    /**
     * Update Password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $this->contact->contact_password_reset_token = \Yii::$app->security->generateRandomString() . '_' . time();
        $this->contact->save(false);

        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $this->contact->contact_password_reset_token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
        ]);
    }

    /**
     * try to request password reset
     * @param \company\tests\FunctionalTester $I
     */
    public function tryToRequestPasswordReset(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => $this->contact->contact_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
        ]);
    }
}
