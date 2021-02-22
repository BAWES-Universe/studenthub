<?php

namespace staff\tests;

use common\models\Suggestion;
use staff\tests\FunctionalTester;
use common\models\Note;
use common\models\Request;
use common\models\Candidate;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\RequestFixture;
use common\fixtures\SuggestionFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class SuggestionCest
{
    public $token;
    public $suggestion;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::className(),
            'suggestion' => SuggestionFixture::className(),
            'candidate' => CandidateFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
        
        $this->suggestion = Suggestion::find()->one();
   
        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate suggestion api response for listing');
        $I->sendGET('v1/suggestions');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View suggestion detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate suggestion api to view note detail');
        $I->sendGET('v1/suggestions/' . $this->suggestion->suggestion_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new suggestion
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $request = Request::find()->where(['request_status' => Request::STATUS_STARTED])->one();
        $candidate = Candidate::find()->one();

        $I->wantTo('create a suggestion via API');
        $I->sendPOST(
            'v1/suggestions',
            [
                'suggestion' => 'big bazar',
                'request_uuid' => $request->request_uuid,
                'candidate_id' => $candidate->candidate_id
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to accept suggestion
     * @param FunctionalTester $I
     */
    public function tryToAccept(FunctionalTester $I)
    {
        $I->wantTo('accept suggestion via API');
        $I->sendPATCH('v1/suggestions/accept/' . $this->suggestion->suggestion_uuid, [
            'reason' => 'Okay can go with this'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to reject suggestion
     * @param FunctionalTester $I
     */
    public function tryToReject(FunctionalTester $I)
    {
        $I->wantTo('reject suggestion via API');
        $I->sendPATCH('v1/suggestions/reject/' . $this->suggestion->suggestion_uuid, [
            'reason' => 'Nah can not go with this'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete suggestion via API');
        $I->sendDelete('v1/suggestions/' . $this->suggestion->suggestion_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}

