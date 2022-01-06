<?php


namespace common\tests;


use common\fixtures\FulltimerSkillFixture;
use common\models\FulltimerSkill;

class FulltimerSkillTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'fulltimerSkill' => FulltimerSkillFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $skill = new FulltimerSkill;

        $skill->fulltimer_uuid = null;
        $skill->skill = null;
        expect('fulltimerSkill fulltimer_uuid should be required field', $skill->validate(['fulltimer_uuid']))->false();
        expect('fulltimerSkill skill should be required field', $skill->validate(['skill']))->false();

        $skill->fulltimer_uuid = '123123123';
        expect('Invalid fulltimer uuid', $skill->validate(['fulltimer_uuid']))->false();
    }
}