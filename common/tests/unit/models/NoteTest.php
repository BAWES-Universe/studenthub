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
            'note' => NoteFixture::className(),
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
        expect('note note_text should be required field', $data->validate(['note_text']))->false();

        $data->candidate_id = '123123123';
        $data->request_uuid = '123123123';
        $data->company_id = '123123123';
        $data->created_by = '123123123';
        $data->updated_by = '123123123';
        expect('Invalid Company id', $data->validate(['company_id']))->false();
        //expect('Invalid staff id', $data->validate(['created_by']))->false();
        //expect('Invalid staff id', $data->validate(['updated_by']))->false();
        expect('Invalid request id', $data->validate(['request_uuid']))->false();
        expect('Invalid candidate id', $data->validate(['candidate_id']))->false();

        $data->request_checklist_uuid = 'random string';
        expect('Invalid request_checklist_uuid', $data->validate(['request_checklist_uuid']))->false();

        $data->invitation_uuid = 'random string';
        expect('Invalid invitation_uuid', $data->validate(['invitation_uuid']))->false();

        $data->suggestion_uuid = 'random string';
        expect('Invalid suggestion_uuid', $data->validate(['suggestion_uuid']))->false();

        $data->note_type = 'random string';
        expect('Invalid note_type', $data->validate(['note_type']))->false();

        $data->contact_uuid = 'random string';
        expect('Invalid contact_uuid', $data->validate(['contact_uuid']))->false();

        $data->fulltimer_uuid = 'random string';
        expect('Invalid fulltimer_uuid', $data->validate(['fulltimer_uuid']))->false();
    }
}
