<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\CompanyTokenFixture;
use Codeception\Util\HttpCode;

class CompanyCest
{

	public function _fixtures() {
		return [
			'companyToken' => CompanyTokenFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List sub companies
     * @param FunctionalTester $I
     */
    public function listCompanies(FunctionalTester $I)
    {
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
