<?php
namespace candidate\tests;

use yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\models\Note;
use common\fixtures\CompanyFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\NoteFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;


class NoteCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'company' => CompanyFixture::className(),
            'staff' => StaffFixture::className(),
            'staff' => NoteFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('Validate note > create api response');
        $I->sendPOST('v1/notes', [
        	'note' => 'lorem isum',
        	'company_id' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Note created successfully"
        ]);
    }

    public function tryToUpdate(FunctionalTester $I)
    {
        $note = Note::find()->one();

        $I->wantTo('Validate note > update api response');
        $I->sendPATCH('v1/notes/' . $note->note_uuid, [
        	'note' => 'lorem isum'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Note successfully updated"
        ]);
    }

    public function tryToView(FunctionalTester $I)
    {
        $note = Note::find()->one();

        $I->wantTo('Validate note > view api response');
        $I->sendGET('v1/notes/' . $note->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "note_uuid" => $note->note_uuid
        ]);
    }

    public function tryToList(FunctionalTester $I)
    {
        $note = Note::find()->one();
        $I->wantTo('Validate note > list api response');
        $I->sendGET('v1/notes');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "note_uuid" => $note->note_uuid
        ]);
    }

    public function tryToDelete(FunctionalTester $I)
    {
        $note = Note::find()->one();

        $I->wantTo('Validate note > delete api response');
        $I->sendDELETE('v1/notes/' . $note->note_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Note deleted successfully"
        ]);
    }

}
