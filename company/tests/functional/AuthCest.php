<?php
namespace company\tests;

use common\models\Company;
use company\models\CompanyToken;
use company\tests\FunctionalTester;
use common\fixtures\CompanyTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
    public $token;
    public $company;

	public function _fixtures()
	{
        return [
            'companyToken' => CompanyTokenFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
        $this->company = CompanyToken::find()
            ->one()->company;
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
        $company = Company::find()->one();
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($company->company_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
            "company_id"=>$company->company_id
        ]);
    }

    /**
     * Update Password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $company =  Company::findOne(['company_id'=>$this->company->company_id]);
        $company->company_password_reset_token = \Yii::$app->security->generateRandomString() . '_' . time();
        $company->save(false);

        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $company->company_password_reset_token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation"=>"success",
        ]);
    }
}
