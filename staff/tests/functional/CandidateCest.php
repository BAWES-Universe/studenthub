<?php
namespace staff\tests;

use staff\models\Transfer;
use yii;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\CandidateWorkHistoryFixture;
use Codeception\Util\HttpCode;
use staff\models\Candidate;


class CandidateCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className(),
            'invoice' => InvoiceFixture::className(),
            'candidates' => CandidateFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'candidateWorkHistory' => CandidateWorkHistoryFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
        
        $I->amBearerAuthenticated($this->token);
        
        $this->candidate = Candidate::find()->one();
    }

    public function _after(FunctionalTester $I){}

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToToggleCommitted(FunctionalTester $I)
    {
        $candidate_id = Candidate::find()->one()->candidate_id;

        $I->wantTo('toggle candidate committed status via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/candidates/toggle-committed',
            [
                'candidate_id' => $candidate_id,
                'note' => 'Spring specialist'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["message" => "Candidate committed status updated successfully"]);
    }

    /**
     * Merge 2 accounts to 1
     * @param FunctionalTester $I
     */
    public function restCallToMergeAccounts(FunctionalTester $I)
    {
        $source = Candidate::findOne(['deleted'=>0]);
        $destination = Candidate::find()->andWhere(['deleted'=>0])->andWhere(['<>','candidate_id',$source->candidate_id])->one();
        $I->wantTo('Merge to account');
        $I->sendPATCH('v1/candidates/merge', [
            'source' => $source->candidate_id,
            'destination' => $destination->candidate_id
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['operation' => 'success']);
    }

    /**
     * List candidate Work History
     * @param FunctionalTester $I
     */
    public function restCallToListWorkHistory(FunctionalTester $I)
    {
        $candidateID = 1;
        $I->wantTo('Get candidate work history');
        $I->sendGET('v1/candidates/work-history/'.$candidateID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['candidate_id' => 1, 'store_id' => 1, 'start_date' => '2017-02-23']);
    }

    /**
     * List candidate Paid Transfer
     * @param FunctionalTester $I
     */
    public function restCallToListCandidatePaidTransfers(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();
        $I->wantTo('Get candidate paid transfer');
        $I->sendGET('v1/candidates/transfers/'.$candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
//        $I->seeResponseIsJson(['tc_id'=>$transfer->tc_id,'candidate_id' => $transfer->candidate_id, 'transfer_id' => $transfer->transfer_id, 'paid' => $transfer->paid]);
    }

    /**
     * try to reset candidate password
     * @param FunctionalTester $I
     */
    public function restCallToResetCandidatePassword(FunctionalTester $I)
    {
        $candiate = Candidate::find()->one();
        $I->wantTo('reset password successfully');
        $I->sendPATCH('v1/candidates/reset-password/'.$candiate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation" => "success","message" => "New password sent to registered email successfully"]);
    }

    /**
     * try to search candidate by country id
     * @param FunctionalTester $I
     */
    public function restCallToSearchCandidateByCountryID(FunctionalTester $I)
    {
        $I->wantTo('search candidate by country id');
        $I->sendGET('v1/candidates/search',['country_id'=>'2']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['candidate_id' => 8]);
    }

    /**
     * try to list all working candidates
     * @param FunctionalTester $I
     */
    public function restCallToListWorkingCandidate(FunctionalTester $I)
    {
        $I->wantTo('list all working candidate');
        $I->sendGET('v1/candidates/assigned');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['candidate_id' => 7]);
    }

    /**
     * try to list all non working candidates
     * @param FunctionalTester $I
     */
    public function restCallToListNonWorkingCandidate(FunctionalTester $I)
    {
        $I->wantTo('list all Non working candidate');
        $I->sendGET('v1/candidates/not-assigned');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['candidate_id' => 8]);
    }

    /**
     * try to unassigned candidate from store
     * @param FunctionalTester $I
     */
    public function restCallToUnAssignCandidateFromStore(FunctionalTester $I)
    {
        $candidateID = Candidate::find()->andWhere([">",'store_id','1'])->one();
        $I->wantTo('unassigned candidate from store');
        $I->sendDELETE('v1/candidates/unassign/'.$candidateID->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation" => "success", "message" => "Candidate unassigned from store successfully"]);
    }

    /**
     * try to assigned candidate to store
     * @param FunctionalTester $I
     */
    public function restCallToAssignCandidateToStore(FunctionalTester $I)
    {
        $candidate = Candidate::find()->andWhere([">",'store_id','1'])->one();
        $I->wantTo('assigned candidate to store');
        $I->sendPATCH('v1/candidates/assign/'.$candidate->candidate_id,['store_id'=>1]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation" => "success", "message" => "Candidate assigned to store successfully"]);
    }

    /**
     * try to update Candidate
     * @param FunctionalTester $I
     */
    public function restCallToUpdateCandidate(FunctionalTester $I)
    {
        $I->wantTo('update candidate details');

        $data = [
            'store_id' => 1,
            'bank_id' => 1,
            'university_id' => 1,
            'country_id' => 2,
            'bank_account_name' => 'DHMANU Kumar',
            'iban' => 'IBAN12121221',
            'name' => 'DHMANU Kumar',
            'name_ar' => 'ساريكا ديف',
            'personal_photo' => 'photos/photo-1497874516406.png',
            'email' => 'DHMANU@gmail.com',
            'phone' => '989898989111',
            'birth_date' => '1992-11-11',
            'civil_id' => 'XIS1212222112',
            'expiry_date' => date('Y-m-d', strtotime('+1 month')),
            'photo_front' => 'photos/photo-1497874516406.png',
            'photo_back' => 'photos/photo-1497874516406.png',
            'hourly_rate' => 1.5,
        ];

        $candidate = Candidate::find()->one();

        $I->sendPATCH('v1/candidates/'.$candidate->candidate_id, $data);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * try to create Candidate
     * skipping image upload
     * @param FunctionalTester $I
     */
    public function restCallToCreateCandidate(FunctionalTester $I)
    {
        $I->wantTo('create candidate profile');

        $candidate = [
            'store_id' => 1,
            'bank_id' => 1,
            'university_id' => 1,
            'country_id' => 2,
            'bank_account_name' => 'dhiman Kumar',
            'iban' => 'IBAN121212223',
            'name' => 'dhiman Kumar',
            'name_ar' => 'ساريكا ديف',
            'personal_photo' => 'photos/photo-1497874516406.png',
            'email' => 'DHMANU@gmail.com',
            'phone' => '989898989111',
            'birth_date' => '1992-11-11',
            'civil_id' => 'XIS1212222101',
            'expiry_date' => date('Y-m-d', strtotime('+1 month')),
            'photo_front' => 'photos/photo-1497874516406.png',
            'photo_back' => 'photos/photo-1497874516406.png',
            'hourly_rate' => 1.5,
        ];

        $I->sendPOST('v1/candidates', $candidate);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Candidate List
     * @param FunctionalTester $I
     */
    public function restCallToListCandidate(FunctionalTester $I)
    {
        $I->wantTo('List candidate');
        $I->sendGET('v1/candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Candidate List Without Bank Detail
     * @param FunctionalTester $I
     */
    public function restCallToListCandidateWithoutBankDetail(FunctionalTester $I)
    {
        $I->wantTo('List candidate without bank');
        $I->sendGET('v1/candidates/without-bank');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View Candidate Detail
     * @param FunctionalTester $I
     */
    public function restCallToViewCandidate(FunctionalTester $I)
    {
        $I->wantTo('View candidate detail');
        $I->sendGET('v1/candidates/detail/' . $this->candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Filter Candidate List
     * @param FunctionalTester $I
     */
    public function restCallToFilterCandidateList(FunctionalTester $I)
    {
        $I->wantTo('List candidate');
        $I->sendGET('v1/candidates/filter?name=a');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Search Candidate 
     * @param FunctionalTester $I
     */
    public function restCallToSearchCandidate(FunctionalTester $I)
    {
        $I->wantTo('List candidate');
        $I->sendGET('v1/candidates/search?country_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Search Candidate transfers
     * @param FunctionalTester $I
     */
    public function restCallToListCandidateTransfers(FunctionalTester $I)
    {
        $I->wantTo('List candidate transfers');
        $I->sendGET('v1/candidates/transfers/' . $this->candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View Candidate resume
     * @param FunctionalTester $I
     */
    public function restCallToViewCandidateResume(FunctionalTester $I)
    {
        $I->wantTo('View candidate resume');
        $I->sendGET('v1/candidates/candidate-resume-pdf/' . $this->candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->canSeeResponseContains('endstream');
    }

    /**
     * View Candidate revuew count
     * @param FunctionalTester $I
     */
    public function restCallToViewCandidateReviewCount(FunctionalTester $I)
    {
        $I->wantTo('Get candidate review count');
        $I->sendGET('v1/candidates/total-to-review');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update Candidate hourly rate
     * @param FunctionalTester $I
     */
    public function restCallToUpdateHourlyRate(FunctionalTester $I)
    {
        $I->wantTo('Update candidate hourly');
        $I->sendPATCH('v1/candidates/update-hour-rate/' . $this->candidate->candidate_id, [
            'hourly_rate' => 1.5
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update Candidate job search status
     * @param FunctionalTester $I
     */
    public function restCallToUpdateJobSearchStatus(FunctionalTester $I)
    {
        $I->wantTo('Update Candidate job search status');
        $I->sendPATCH('v1/candidates/job-search-status', [
            'candidate_id' => $this->candidate->candidate_id,
            'job_search_status' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * @param FunctionalTester $I
     */
    public function restCallToResetPassword(FunctionalTester $I)
    {
        $I->wantTo('Update Candidate password');
        $I->sendPATCH('v1/candidates/reset-password/' . $this->candidate->candidate_id, [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * @param FunctionalTester $I
     */
    public function restCallToUpdateApprove(FunctionalTester $I)
    {
        $I->wantTo('Update Candidate approve status');
        $I->sendPATCH('v1/candidates/approve/' . $this->candidate->candidate_id, [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * @param FunctionalTester $I
     */
    public function restCallToUpdateUnapprove(FunctionalTester $I)
    {
        $I->wantTo('Update Candidate unapprove status');
        $I->sendPATCH('v1/candidates/unapprove/' . $this->candidate->candidate_id, [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * @param FunctionalTester $I
     */
    public function restCallToExpireId(FunctionalTester $I)
    {
        $I->wantTo('Candidate expire card');
        $I->sendPATCH('v1/candidates/expire-card/' . $this->candidate->candidate_id, [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * assignedIdleCandidate
     * @param FunctionalTester $I
     */
    public function assignedIdleCandidate(FunctionalTester $I)
    {
        $I->wantTo('assigned Idle Candidate');
        $I->sendGET('v1/candidates/assigned-idle-candidate', [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function listExpiredIds(FunctionalTester $I)
    {
        $I->wantTo('list expired ids');
        $I->sendGET('v1/candidates/expired-civil-id');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /*
    public function viewAppreciationCertificate(FunctionalTester $I)
    {
        $I->wantTo('list expired ids');
        $I->sendGET('v1/candidates/appreciation-certificate/<id>/<wid>');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }*/

    /**
     * update email
     * @param FunctionalTester $I
     */
    public function restCallToUpdateEmail(FunctionalTester $I)
    {
        $I->wantTo('Candidate expire card');
        $I->sendPATCH('v1/candidates/update-email/' . $this->candidate->candidate_id, [
            'email' => 'new@email.com'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
 
