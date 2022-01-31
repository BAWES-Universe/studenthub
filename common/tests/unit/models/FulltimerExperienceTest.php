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
            'fulltimerSkill' => FulltimerSkillFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $model = new FulltimerExperience;

        $model->fulltimer_uuid = null;
        $model->experience = null;
        expect('fulltimerSkill fulltimer_uuid should be required field', $model->validate(['fulltimer_uuid']))->false();
        expect('fulltimerSkill experience should be required field', $model->validate(['experience']))->false();

        $model->fulltimer_uuid = '123123123';
        expect('Invalid fulltimer uuid', $model->validate(['fulltimer_uuid']))->false();
    }
}