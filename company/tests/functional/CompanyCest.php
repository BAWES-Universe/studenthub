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
     * Try to update
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a company via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/companies/' . $this->contact_uuid,
            [
                'name' => 'davert',
                'common_name_en' => 'ravan',
                'common_name_ar' => 'ravan',
                'description_en' => 'ravan',
                'description_ar' => 'ravan',
                'website' => 'google.com',
                'email' => 'tets@lol.com'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
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

    public function removeCompanyLogo(FunctionalTester $I) {
        $I->sendDELETE('v1/companies/remove-logo');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'operation' => 'success'
        ]);
    }
}
