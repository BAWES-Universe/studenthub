<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\CompanyTokenFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;

class CandidateCest
{
    public $token;
    public $company;

	public function _fixtures() {
		return [
			'companyToken' => CompanyTokenFixture::className(),
			'candidate'    => CandidateFixture::className()
		] ;
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
        $this->company = CompanyToken::find()
            ->one();
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
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * view candidates
     * @param FunctionalTester $I
     */
    public function tryViewCandidates(FunctionalTester $I)
    {
        $I->wantTo('View candidate api');
        $I->sendGET('v1/candidates/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id'=>1
        ]);
    }

    /**
     * get total candidates
     * @param FunctionalTester $I
     */
    public function getCandidateCount(FunctionalTester $I)
    {
        $count = $this->company->company->getCandidates()->count();
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
