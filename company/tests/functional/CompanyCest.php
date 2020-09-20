<?php
namespace company\tests;

use company\tests\FunctionalTester;
use Yii;
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
        $I->amBearerAuthenticated($this->token);
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
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function viewCompany(FunctionalTester $I) {
        Yii::$app->user->loginByAccessToken($this->token);
        $data = Yii::$app->user->identity->getSubCompanies()->one();
        $I->sendGET('v1/companies/'.$data->company_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
