<?php

namespace staff\tests;

use common\fixtures\StoryFixture;
use common\models\Story;
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
    public $staff;
    public $story;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::class,
            'suggestion' => SuggestionFixture::class,
            'story' => StoryFixture::class,
            'candidate' => CandidateFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $model = StaffToken::find()
            ->one();

        $this->staff = $model->staff;

        $this->token = $model
            ->token_value;

        $this->story = Story::find()
            ->andWhere(['story_status' => Story::STATUS_STARTED])
            ->one();

        //assign current staff so he can work
        $this->story->staff_id = $this->staff->staff_id;
        $this->story->save(false);

        $this->story->request->request_status = Request::STATUS_STARTED;
        $this->story->request->save(false);

        $this->suggestion = Suggestion::find()->one();
   
        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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
        $candidate = Candidate::find()->one();

        $I->wantTo('create a suggestion via API');
        $I->sendPOST(
            'v1/suggestions',
            [
                'suggestion' => 'big bazar',
                'story_uuid' => $this->story->story_uuid,
                'request_uuid' => $this->story->request_uuid,
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
     *
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete suggestion via API');
        $I->sendDelete('v1/suggestions/' . $this->suggestion->suggestion_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }*/
}

