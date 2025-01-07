<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\NoteFixture;
use common\models\Note;
use common\models\Staff;

class NoteTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'note' => NoteFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        $data = new Note();
        $data->note_text = null;
        $this->assertFalse($data->validate(['note_text']), "Note Text is not valid");

        $data->candidate_id = '123123123';
        $data->request_uuid = '123123123';
        $data->company_id = '123123123';
        $data->created_by = '123123123';
        $data->updated_by = '123123123';
        $this->assertFalse($data->validate(['company_id']), "Company ID is not valid");
        //$this->assertFalse($data->validate(['created_by']), "Created By is not valid");
        //$this->assertFalse($data->validate(['updated_by']), "Updated By is not valid");
        $this->assertFalse($data->validate(['request_uuid']), "Request UUID is not valid");
        $this->assertFalse($data->validate(['candidate_id']), "Candidate ID is not valid");

        $data->request_checklist_uuid = 'random string';
        $this->assertFalse($data->validate(['request_checklist_uuid']), "Request Checklist UUID is not valid");

        $data->invitation_uuid = 'random string';
        $this->assertFalse($data->validate(['invitation_uuid']), "Invitation UUID is not valid" );

        $data->suggestion_uuid = 'random string';
        $this->assertFalse($data->validate(['suggestion_uuid']), "Suggestion UUID is not valid");

        $data->note_type = 'random string';
        $this->assertFalse($data->validate(['note_type']), "Note Type is not valid");

        $data->contact_uuid = 'random string';
        $this->assertFalse($data->validate(['contact_uuid']), "Contact UUID is not valid"   );

        $data->fulltimer_uuid = 'random string';
        $this->assertFalse($data->validate(['fulltimer_uuid']), "Fulltimer UUID is not valid");
    }
}
