<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\CandidateNoteFixture;
use common\models\CandidateNote;
use common\models\Staff;

class CandidateNoteTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateNote' => CandidateNoteFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        $data = new CandidateNote();
        $data->candidate_id = null;
        $data->note_text = null;
        expect('note candidate_id should be required field', $data->validate(['candidate_id']))->false();
        expect('note note_text should be required field', $data->validate(['note_text']))->false();

        $data->candidate_id = '123123123';
        $data->created_by = '123123123';
        $data->updated_by = '123123123';
        expect('Invalid Candidate id', $data->validate(['candidate_id']))->false();
        expect('Invalid staff id', $data->validate(['created_by']))->false();
        expect('Invalid staff id', $data->validate(['updated_by']))->false();
    }

}
