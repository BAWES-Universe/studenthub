<?php
namespace common\tests;

use common\models\CandidateIdCard;
use common\fixtures\CandidateIdCardFixture;
use Codeception\Specify;


class CandidateIdCardTest extends \Codeception\Test\Unit
{
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateIdCard' => CandidateIdCardFixture::class
        ];
    }

    protected function _before(){}
    protected function _after(){}

    public function testValidations()
    {
        // Test fixtures loaded
        $this->assertNotNull(CandidateIdCard::findOne(['candidate_id'=>1]), 'CandidateIdCard fixture should exist');

        // Test required fields
        $model = new CandidateIdCard();
        $this->assertFalse($model->validate(['candidate_id']), 'Candidate ID should be required');
      //  $this->assertFalse($model->validate(['expiry_date']), 'Expiry date should be required');

        // Test invalid candidate_id
        $model->candidate_id = 999999999;
        $this->assertFalse($model->validate(['candidate_id']), 'Should not accept invalid candidate_id');
    }

    /*public function testCrud()
    {
        // Test Create
        $model = new CandidateIdCard();
        $model->candidate_id = 2;
        $model->expiry_date = date('Y-m-d', strtotime('+1 month'));
        $this->assertTrue($model->save(), 'Should create new record');
        $this->assertNotNull(CandidateIdCard::findOne(['candidate_id' => 2]), 'Created record should exist');

        // Test Update
        $model = CandidateIdCard::findOne(['candidate_id' => 2]);
        $model->expiry_date = date('Y-m-d', strtotime('+2 month'));
        $this->assertTrue($model->save(), 'Should update record');
        $this->assertEquals(
            date('Y-m-d', strtotime('+2 month')),
            CandidateIdCard::findOne(['candidate_id' => 2])->expiry_date,
            'Record should be updated'
        );

        // Test Delete
        $this->assertEquals(1, $model->delete(), 'Should delete record');
        $this->assertNull(CandidateIdCard::findOne(['candidate_id' => 2]), 'Record should be deleted');
    }*/

    /*public function testExpiryDateValidation()
    {
        $model = new CandidateIdCard();
        
        // Test past date
        $model->expiry_date = date('Y-m-d', strtotime('-1 day'));
        $this->assertFalse($model->validate(['expiry_date']), 'Should not accept past date');

        // Test valid future date
        $model->expiry_date = date('Y-m-d', strtotime('+1 month'));
        $this->assertTrue($model->validate(['expiry_date']), 'Should accept future date');

        // Test invalid date format
        $model->expiry_date = 'invalid-date';
        $this->assertFalse($model->validate(['expiry_date']), 'Should not accept invalid date format');
    }*/

    public function testRelations()
    {
        $model = CandidateIdCard::findOne(['candidate_id' => 1]);
        $this->assertNotNull($model->candidate, 'Should have candidate relation');
    }

    public function testAttributeLabels()
    {
        $model = new CandidateIdCard();
        $labels = $model->attributeLabels();
        
        $this->assertArrayHasKey('candidate_id', $labels);
        $this->assertArrayHasKey('expiry_date', $labels);
        $this->assertArrayHasKey('created_at', $labels);
        $this->assertArrayHasKey('updated_at', $labels);
    }
}
