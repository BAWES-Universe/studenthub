<?php
namespace common\tests;


use common\fixtures\CandidateSkillFixture;

class CandidateSkillTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateSkill' => CandidateSkillFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $skill = $this->tester->grabFixture('candidateSkill', 'candidate_skill0');
        expect('model adding new skill', $skill->save())->true();

        $skill->candidate_id = null;
        $skill->skill = null;
        expect('candidateSkill candidate_id should be required field', $skill->validate(['candidate_id']))->false();
        expect('candidateSkill skill should be required field', $skill->validate(['skill']))->false();

        $skill->candidate_id = '123123123';
        expect('Invalid candidate id', $skill->validate(['candidate_id']))->false();
    }
}
