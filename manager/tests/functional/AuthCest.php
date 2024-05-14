<?php
namespace manager\tests;

use common\fixtures\CountryFixture;
use common\fixtures\StoreFixture;
use common\fixtures\StoreManagerFixture;
use common\models\StoreManager;
use manager\models\Contact;
use common\fixtures\CompanyContactFixture;
use common\fixtures\ContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ManagerTokenFixture;
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
            "country" => CountryFixture::className(),
            "store" => StoreFixture::className(),
            'contactToken' => ManagerTokenFixture::className(),
            'manager' => StoreManagerFixture::className()
        ];
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
    public function _before(FunctionalTester $I)
    {
        $this->manager = StoreManager::find()->one();

        $this->token = $this->manager->getAccessToken()
            ->token_value;

        $this->company = $this->manager->getCompany()->one();
    }

    /**
     * @param FunctionalTester $I
     * @return void
     */
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
        $I->amHttpAuthenticated($this->manager->email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
            "company_id"=> $this->company->company_id
        ]);
    }

    /**
     * Check if email got verified
     * @param FunctionalTester $I
     */
    public function tryToCheckEmailVerificationStatus(FunctionalTester $I)
    {
        $model = StoreManager::find()->one();
        $model->email_verification = 0;
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
        $model = StoreManager::find()->one();
        $model->email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to get verification again by email');
        $I->sendPOST('v1/auth/resend-verification-email', [
            'email' => $model->email
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
        $model = StoreManager::find()->one();
        $model->email_verification = 0;
        $model->save(false);

        $I->wantTo('Try to verify email by code');
        $I->sendPOST('v1/auth/verify-email', [
            'code' => $model->auth_key,
            'email' => $model->email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson();
    }

    public function tryToUpdateEmail(FunctionalTester $I)
    {
        $model = StoreManager::find()->one();
        $model->email_verification = 0;
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
        $this->manager->password_reset_token = \Yii::$app->security->generateRandomString() . '_' . time();
        $this->manager->save(false);

        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $this->manager->password_reset_token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
        ]);
    }

    /**
     * try to request password reset
     * @param \manager\tests\FunctionalTester $I
     */
    public function tryToRequestPasswordReset(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > request password reset api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => $this->manager->email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
        ]);
    }
}
