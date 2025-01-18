<?php
namespace manager\tests;

use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreManagerFixture;
use common\models\StoreManager;
use manager\models\Contact;
use manager\models\Candidate;
use common\fixtures\ManagerTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class CandidateCest
{
    public $token;
    public $company;

	public function _fixtures() {
		return [
            'company' => CompanyFixture::class,
            'companyContact' => CompanyContactFixture::class,
			'contactToken' => ManagerTokenFixture::class,
			'candidate'    => CandidateFixture::class,
            'manager' => StoreManagerFixture::class
		] ;
	}

	public function _before(FunctionalTester $I)
	{
        $this->manager = StoreManager::find()->one();

        $this->store = $this->manager->store;

        $this->token = $this->manager->getAccessToken()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * view candidates
     * @param FunctionalTester $I
     */
    public function tryViewCandidates(FunctionalTester $I)
    {
        $candidate = $this->store->getCandidates()->one();

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
        $I->wantTo('Validate company > candidates/total api to get total candidates');
        $I->sendGET('v1/candidates/total');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        //$I->seeResponseContains($count);
    }

    /**
     * Get candidate work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $candidate = $this->store->getCandidates()->one();

        $I->wantTo('Validate company > candidates/work-history/1 api to list work history');
        $I->sendGET('v1/candidates/work-history/' . $candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
