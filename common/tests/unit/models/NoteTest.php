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
        $data->company_id = null;
        $data->note_text = null;
        expect('note company_id should be required field', $data->validate(['company_id']))->false();
        expect('note note_text should be required field', $data->validate(['note_text']))->false();

        $data->company_id = '123123123';
        $data->staff_id = '123123123';
        expect('Invalid Company id', $data->validate(['company_id']))->false();
    }

}
