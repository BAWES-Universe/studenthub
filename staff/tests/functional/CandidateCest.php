<?php
namespace staff\tests;

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
     * Merge 2 accounts to 1
     * @param FunctionalTester $I
     */
    public function restCallToMergeAccounts(FunctionalTester $I)
    {
        $candidateID = 1;
        $I->wantTo('Merge to account');
        $I->sendPATCH('v1/candidates/merge', [
            'source' => 1,
            'destination' => 2
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
        $candidateID = 1;
        $I->wantTo('Get candidate paid transfer');
        $I->sendGET('v1/candidates/transfers/'.$candidateID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['tc_id'=>5,'candidate_id' => 1, 'transfer_id' => 5, 'paid' => '0']);
    }

    /**
     * try to delete candidate while working will show error
     * @param FunctionalTester $I
     */
    public function restCallToDeleteCandidateWhileWorkingWillShowError(FunctionalTester $I)
    {
        $candidateID = 1;
        $I->wantTo('show error while deleting candidate while working');
        $I->sendDELETE('v1/candidates/'.$candidateID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>"Can not delete as assigned to store."]);
    }

    /**
     * try to delete candidate Successfully
     * @param FunctionalTester $I
     */
    public function restCallToDeleteCandidateSuccessfully(FunctionalTester $I)
    {
        $candidateID = 8;
        $I->wantTo('delete candidate successfully');
        $I->sendDELETE('v1/candidates/'.$candidateID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"success","message"=>"Candidate removed successfully"]);
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
        $candidateID = 2;
        $I->wantTo('unassigned candidate from store');
        $I->sendDELETE('v1/candidates/unassign/'.$candidateID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation" => "success", "message" => "Candidate unassigned from store successfully"]);
    }

    /**
     * try to assigned candidate to store
     * @param FunctionalTester $I
     */
    public function restCallToAssignCandidateToStore(FunctionalTester $I)
    {
        $candidateID = 8;
        $I->wantTo('assigned candidate to store');
        $I->sendPATCH('v1/candidates/assign/'.$candidateID,['store_id'=>1]);
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

        $candidate = [
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
        $candidateID = 8;

        $I->sendPATCH('v1/candidates/'.$candidateID,$candidate);
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
        $candidateID = 8;

        $I->sendPOST('v1/candidates',$candidate);
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
     *
    public function restCallToViewCandidateResume(FunctionalTester $I)
    {
        $I->wantTo('View candidate resume');
        $I->sendGET('v1/candidates/candidate-resume-pdf/' . $this->candidate->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }*/

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
    public function restCallToUpdateCandidateHourlyRate(FunctionalTester $I)
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
            'candidate_id' => 1,
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
}
 
