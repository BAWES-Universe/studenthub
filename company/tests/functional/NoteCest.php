<?php

namespace company\tests;

use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\InvitationFixture;
use common\fixtures\SuggestionFixture;
use company\models\Contact;
use company\tests\FunctionalTester;
use common\models\Note;
use common\fixtures\NoteFixture;
use Codeception\Util\HttpCode;


class NoteCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'invitation' => InvitationFixture::className (),
            'suggestion' => SuggestionFixture::className (),
            'note' => NoteFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $I->amBearerAuthenticated($this->token);
        
        $this->note_uuid = $this->company->getNotes()->one()->note_uuid;
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate note api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/notes');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate note api to view note detail');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/notes/' . $this->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new note
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a note via API');
        $I->amBearerAuthenticated($this->token);
        $I->sendPOST(
            'v1/notes',
            [
                'note' => 'Spring specialist',
                'company_id' => '1'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a note via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/notes/' . $this->note_uuid,
            [
                'note' => 'Spring specialist'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        /*$I->seeResponseContainsJson([
            "operation" => "success"
        ]);*/
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete note via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/notes/' . $this->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}

