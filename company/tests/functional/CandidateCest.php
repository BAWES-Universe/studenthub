<?php
namespace company\tests;

use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use company\models\Contact;
use company\models\Candidate;
use common\fixtures\ContactTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class CandidateCest
{
    public $token;
    public $company;

	public function _fixtures() {
		return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
			'contactToken' => ContactTokenFixture::className(),
			'candidate'    => CandidateFixture::className()
		] ;
	}

	public function _before(FunctionalTester $I)
	{
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I) { }

    /**
     * list candidates
     * @param FunctionalTester $I
     */
    public function tryListCandidates(FunctionalTester $I)
    {
        $I->wantTo('List candidates api');
        $I->sendGET('v1/candidates');
        $I->haveHttpHeader ('Company-ID', $this->company->company_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * view candidates
     * @param FunctionalTester $I
     */
    public function tryViewCandidates(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();

        $I->wantTo('View candidate api');
        $I->sendGET('v1/candidates/' . $candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * get total candidates
     * @param FunctionalTester $I
     */
    public function getCandidateCount(FunctionalTester $I)
    {
        $count = $this->company->getCandidates()->count();
        $I->wantTo('Validate company > candidates/total api to get total candidates');
        $I->sendGET('v1/candidates/total');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContains($count);
    }

    /**
     * Get candidate work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $I->wantTo('Validate company > candidates/work-history/1 api to list work history');
        $I->sendGET('v1/candidates/work-history/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
