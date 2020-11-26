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
        expect('Invalid staff id', $data->validate(['created_by']))->false();
        expect('Invalid staff id', $data->validate(['updated_by']))->false();
        expect('Invalid request id', $data->validate(['request_uuid']))->false();
        expect('Invalid candidate id', $data->validate(['candidate_id']))->false();
    }
}
