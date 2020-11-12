<?php
namespace company\tests;

use company\models\Company;
use company\tests\FunctionalTester;
use Yii;
use company\models\CompanyToken;
use common\fixtures\CompanyTokenFixture;
use Codeception\Util\HttpCode;

class CompanyCest
{
    public $company;
	public function _fixtures() {
		return [
			'companyToken' => CompanyTokenFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->company = CompanyToken::find()
            ->one();
        $I->amBearerAuthenticated($this->company->token_value);
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
        $company = Company::find()
            ->childCompany($this->company->company_id)
            ->one();

        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id'=>$company->company_id
        ]);
    }

    public function viewCompany(FunctionalTester $I) {
        Yii::$app->user->loginByAccessToken($this->company->token_value);
        $data = Yii::$app->user->identity->getSubCompanies()->one();
        $I->sendGET('v1/companies/'.$data->company_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id'=>$data->company_id
        ]);
    }
}
