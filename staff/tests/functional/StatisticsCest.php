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
            'candidateIdCardFixture' => CandidateIdCardFixture::class,
            'staffToken' => StaffTokenFixture::class
        ];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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

        /*$result["refresh"] = false;

        $result['totalExpiredCards'] =  Candidate::totalExpiredCards()->count();

        // # of candidates that need id generated
        //Candidates with profile complete requiring their profiles to be reviewed and approved.

        $result['profileApprovalRequire'] = Candidate::profileApprovalRequire()->count();

        //Candidates are assigned to work but have incomplete profiles.
        $result['incompleteAssignedToWork'] = Candidate::incompleteAssignedToWork()->count();

        $result['missingBankInfo'] = Candidate::withoutBankInfoOrWithPayment()->count();
        $result['requireFollowup'] = Company::companyFollowupCount();

        $result['activeRequests'] = Request::activeRequestCount();

        "totalUnverifiedEmails"
        "assignedExpiredCivilID"
        "id_need_generated":
        "totalRequests":0,
        "assignedIdleCandidates":0,
        "companyMoreThen40DaysWithoutPayment":3,
        "last40daysNoRequest":3,
        "companyUnderReview":0,
        "transfersWithNoProfitInProgress":0,
        "transfersWithSameRateInProgress":0}

        $I->canSeeResponseContainsJson($result);*/
    }
}
