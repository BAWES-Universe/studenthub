<?php

namespace staff\tests;

use PhpParser\Node\Stmt\Expression;
use common\models\Note;
use common\models\StaffToken;
use common\models\Candidate;
use common\fixtures\StaffTokenFixture;
use common\fixtures\NoteFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class CandidateNoteCest
{
    public $token;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::className(),
            'candidateNote' => NoteFixture::className(),
            'candidate' => CandidateFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $this->note_uuid = Note::find()
            ->where(new Expression('candidate_id IS NOT NULL'))
            ->one()
            ->note_uuid;

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate candidate note api response for listing');
        $I->sendGET('v1/candidate-notes');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $model = Note::find()->one();
        
        $I->wantTo('Validate note api to view candidate note detail');
        $I->sendGET('v1/candidate-notes/' . $model->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["note_uuid" => $model->note_uuid]);
    }

    /**
     * Try to create new note
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a candidate note via API');

        $I->sendPOST(
            'v1/candidate-notes',
            [
                'note' => 'Spring specialist',
                'candidate_id' => '1'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["message" => "Candidate Note created successfully"]);
    }

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
            'v1/candidate-notes/toggle-committed',
            [
                'candidate_id' => $candidate_id,
                'note' => 'Spring specialist'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["message" => "Candidate committed status updated successfully"]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a candidate note via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/candidate-notes/' . $this->note_uuid,
            [
                'note' => 'Spring specialist'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["message" => "Candidate Note successfully updated"]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete candidate note via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/candidate-notes/' . $this->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(["message" => "Note deleted successfully"]);
    }
}

