<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\models\Candidate;
use common\models\AdminToken;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\AdminTokenFixture;
use Codeception\Util\HttpCode;


class CandidateCest
{
    public $token, $candidate_id;

    public function _fixtures() 
    {
        return [
            'candidate' => CandidateFixture::className(),
            'adminToken' => AdminTokenFixture::className(),
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
        $I->amBearerAuthenticated($this->token);
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
        $candidate = Candidate::findOne(['approved'=>'1']);
        $I->wantTo('Validate admin > candidates api response for review listing');
        $I->sendGET('v1/candidates/search?by=review&review=1&expand=store,university,country,company,bank');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * Approve candidate
     * @param FunctionalTester $I
     */
    public function tryToApprove(FunctionalTester $I)
    {
        $candidate = Candidate::findOne(['approved'=>'0']);
        $I->wantTo('Validate admin > candidates api to approve candidate');
        $I->sendPATCH('v1/candidates/approve/'.$candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }
    
    /**
     * view candidate
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();
        
        $I->wantTo('Validate admin > candidates api to view candidate detail');
        $I->sendGET('v1/candidates/' . $candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * List candidates by country
     * @param FunctionalTester $I
     */
    public function tryToListByCountry(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();
        $I->wantTo('Validate admin > candidates api to list candidates by country');
        $I->sendGET('v1/candidates/search?by=country_id&country_id='.$candidate->country_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * List candidates by store
     * @param FunctionalTester $I
     */
    public function tryToListByStore(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();
        $I->wantTo('Validate admin > candidates api to list candidates by store');
        $I->sendGET('v1/candidates/search?by=store_id&store_id='.$candidate->store_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * List candidates by university
     * @param FunctionalTester $I
     */
    public function tryToListByUniversity(FunctionalTester $I)
    {
        $candidate = Candidate::findOne(['university_id'=>1]);
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->sendGET('v1/candidates/search?by=university_id&university_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'candidate_id' => $candidate->candidate_id
        ]);
    }

    /**
     * Get total no of candidate to review
     * @param FunctionalTester $I
     */
    public function getTotalCandidates(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->sendGET('v1/candidates/total-to-review');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand deleted successfully"
        ]);
    }

    /**
     * Get candidate salary transfer
     * @param FunctionalTester $I
     */
    public function getSalaryTransfers(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->sendGET('v1/candidates/transfers/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand deleted successfully"
        ]);
    }

    /**
     * Get candidate's work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->sendGET('v1/candidates/work-history/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand deleted successfully"
        ]);
    }
}
