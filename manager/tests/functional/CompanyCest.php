<?php
namespace manager\tests;

use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreManagerFixture;
use common\models\StoreManager;
use manager\models\Company;
use manager\models\Contact;
use Yii;
use common\models\ManagerToken;
use common\fixtures\ManagerTokenFixture;
use Codeception\Util\HttpCode;


class CompanyCest
{
    public $company;

	public function _fixtures() {
		return [
            'company' => CompanyFixture::class,
            'companyContact' => CompanyContactFixture::class,
            'contactToken' => ManagerTokenFixture::class,
            'candidate'    => CandidateFixture::class,
            'manager' => StoreManagerFixture::class
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->manager = StoreManager::find()->one();

        $this->token = $this->manager->getAccessToken()
            ->token_value;

        $this->company = $this->manager->getCompany()->one();

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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

        //$I->haveHttpHeader ('Company-ID', $this->company->company_id);
        $I->sendGET('v1/companies/list-child');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_id' => $company->company_id
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
