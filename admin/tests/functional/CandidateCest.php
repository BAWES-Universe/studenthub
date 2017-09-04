<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\models\Candidate;
use common\models\AdminToken;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\TransferFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\CountryFixture;
use Codeception\Util\HttpCode;

class CandidateCest
{
    public $token, $candidate_id;

	public function _fixtures() {

		return [
			'admin' => AdminFixture::className(),
			'adminToken' => AdminTokenFixture::className(),
			'country' => CountryFixture::className(),
			'company' => CompanyFixture::className(),
			'store' => StoreFixture::className(),
			'candidate' => CandidateFixture::className(),
			'transfer' => TransferFixture::className(),
			'transferCandidate' => TransferCandidateFixture::className()
		];
	}
	public function _before(FunctionalTester $I)
	{
        $this->token = AdminToken::find()
                ->one()
                ->token_value;

        $this->candidate_id = Candidate::find()
            ->one()
            ->candidate_id;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * list candidates to review
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api response for review listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=review&review=0&expand=store,university,country,company,bank');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Approve candidate
     * @param FunctionalTester $I
     */
    public function tryToApprove(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to approve candidate');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/candidates/approve/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by country
     * @param FunctionalTester $I
     */
    public function tryToListByCountry(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by country');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=country_id&country_id=168');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by store
     * @param FunctionalTester $I
     */
    public function tryToListByStore(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by store');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=store_id&store_id=5');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by university
     * @param FunctionalTester $I
     */
    public function tryToListByUniversity(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=university_id&university_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get total no of candidate to review
     * @param FunctionalTester $I
     */
    public function getTotalCandidates(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/total-to-review');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get candidate salary transfer
     * @param FunctionalTester $I
     */
    public function getSalaryTransfers(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/transfers/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get candidate's work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/work-history/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
