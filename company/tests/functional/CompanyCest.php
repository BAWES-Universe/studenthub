<?php
namespace company\tests;

use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use company\models\Company;
use company\models\Contact;
use company\tests\FunctionalTester;
use Yii;
use company\models\ContactToken;
use common\fixtures\ContactTokenFixture;
use Codeception\Util\HttpCode;


class CompanyCest
{
    public $company;

	public function _fixtures() {
		return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List sub companies
     * @param FunctionalTester $I
     */
    public function listChildCompanies(FunctionalTester $I)
    {
        $company = Company::find()
            ->childCompany($this->company->company_id)
            ->one();

        $I->sendGET('v1/companies/list-child');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id' => $company->company_id
        ]);
    }

    /**
     * List parent companies, user managing
     * @param FunctionalTester $I
     */
    public function listCompanies(FunctionalTester $I)
    {
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id' => $this->company->company_id
        ]);
    }

    public function viewCompany(FunctionalTester $I) {
        $I->sendGET('v1/companies/' . $this->company->company_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id' => $this->company->company_id
        ]);
    }
}
