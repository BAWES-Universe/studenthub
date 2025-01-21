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
            'fulltimerSkill' => FulltimerSkillFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $skill = new FulltimerSkill;

        $skill->fulltimer_uuid = null;
        $skill->skill = null;
        $this->assertFalse($skill->validate(['fulltimer_uuid']));
        $this->assertFalse($skill->validate(['skill']));

        $skill->fulltimer_uuid = '123123123';
        $this->assertFalse($skill->validate(['fulltimer_uuid']));
    }
}
