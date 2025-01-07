<?php


namespace common\tests;


use common\fixtures\FulltimerSkillFixture;
use common\models\FulltimerExperience;

class FulltimerExperienceTest extends \Codeception\Test\Unit
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
        $model = new FulltimerExperience;

        $model->fulltimer_uuid = null;
        $model->experience = null;
        $this->assertFalse($model->validate(['fulltimer_uuid']));
        $this->assertFalse($model->validate(['experience']));

        $model->fulltimer_uuid = '123123123';
        $this->assertFalse($model->validate(['fulltimer_uuid']));
    }
}
