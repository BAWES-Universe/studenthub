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
            'candidateExperience' => CandidateExperienceFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $exp = $this->tester->grabFixture('candidateExperience', 'candidate_experience0');
        expect('model adding new exp', $exp->save())->true();

        $exp->candidate_id = null;
        $exp->experience = null;
        expect('candidateExperience candidate_id should be required field', $exp->validate(['candidate_id']))->false();
        expect('candidateExperience exp should be required field', $exp->validate(['experience']))->false();

        $exp->candidate_id = '123123123';
        expect('Invalid candidate id', $exp->validate(['candidate_id']))->false();
    }
}
