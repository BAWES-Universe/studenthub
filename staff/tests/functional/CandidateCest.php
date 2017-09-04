<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use staff\fixtures\StoreFixture;
use staff\fixtures\CandidateFixture;
use staff\fixtures\UniversityFixture;
use staff\fixtures\CompanyFixture;
use staff\fixtures\StaffTokenFixture;
use staff\fixtures\StaffFixture;
use common\fixtures\TransferFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\CandidateWorkHistoryFixture;
use Codeception\Util\HttpCode;

class CandidateCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staff.php'
            ],
            'staffToken' => [
                'class' => StaffTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staffToken.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'
            ],
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/university.php'
            ],
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'
            ],
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transfer.php'
            ],
            'invoice' => [
                'class' => InvoiceFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/invoice.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transferCandidate.php'
            ],
            'candidateWorkHistory' => [
                'class' => CandidateWorkHistoryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidateWorkHistory.php'
            ],
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
        $candidateID = 8;
        $I->wantTo('delete candidate successfully');
        $I->sendPATCH('v1/candidates/reset-password'.$candidateID);
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
}
