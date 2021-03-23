<?php
namespace staff\tests;

use common\models\Request;
use staff\models\Candidate;
use staff\models\Company;
use yii;
use common\models\StaffToken;
use common\fixtures\CandidateIdCardFixture;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'candidateIdCardFixture' => CandidateIdCardFixture::className(),
            'staffToken' => StaffTokenFixture::className()
        ];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * List university record by student records also
     * @param FunctionalTester $I
     */
    public function restCallToListStatistics(FunctionalTester $I)
    {
        $I->wantTo('get statistics');
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $result['totalExpiredCards'] =  Candidate::totalExpiredCards()->count();

        // # of candidates that need id generated
        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $result['profileApprovalRequire'] = Candidate::profileApprovalRequire()->count();

        //Candidates are assigned to work but have incomplete profiles.
        $result['incompleteAssignedToWork'] = Candidate::incompleteAssignedToWork()->count();

        $result['missingBankInfo'] = Candidate::withoutBankInfoOrWithPayment()->count();
        $result['requireFollowup'] = Company::companyFollowupCount();

        $result['activeRequests'] = Request::activeRequestCount();

        $I->canSeeResponseContainsJson($result);
    }
}
