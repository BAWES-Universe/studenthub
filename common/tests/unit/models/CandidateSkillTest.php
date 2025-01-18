<?php
namespace common\tests;


use common\fixtures\CandidateSkillFixture;
use common\models\CandidateSkill;

class CandidateSkillTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateSkill' => CandidateSkillFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        //$skill = $this->tester->grabFixture('candidateSkill', 'candidate_skill0');

        $skill = CandidateSkill::find()->one();

        $this->assertTrue($skill->save(), 'model adding new skill');

        $skill->candidate_id = null;
        $skill->skill = null;
        $this->assertFalse($skill->validate(['candidate_id']), 'candidateSkill candidate_id should be required field');
        $this->assertFalse($skill->validate(['skill']), 'candidateSkill skill should be required field');

        $skill->candidate_id = '123123123';
        $this->assertFalse($skill->validate(['candidate_id'], 'Invalid candidate id'));
    }
}
