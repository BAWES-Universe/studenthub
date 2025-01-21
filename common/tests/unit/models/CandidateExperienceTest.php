<?php
namespace common\tests;


use common\fixtures\CandidateExperienceFixture;

class CandidateExperienceTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateExperience' => CandidateExperienceFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}
    public function testRelations()
    {
        $exp = $this->tester->grabFixture('candidateExperience', 'candidate_experience0');
        
        $this->assertNotNull($exp->candidate, 'should have candidate relation');
     
        $exp->candidate_id = null;
        $exp->experience = null;
        $this->assertFalse($exp->validate(['candidate_id']), 'candidate_id should be required');
        $this->assertFalse($exp->validate(['experience']), 'experience should be required');
         
        $exp->candidate_id = '123123123';
        $this->assertFalse($exp->validate(['candidate_id']), 'Invalid candidate id');
    }
}
