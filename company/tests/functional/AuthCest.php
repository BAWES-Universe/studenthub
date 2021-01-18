<?php
namespace company\tests;

use common\models\Company;
use company\models\Contact;
use company\models\ContactToken;
use company\tests\FunctionalTester;
use common\fixtures\CompanyContactFixture;
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
}
