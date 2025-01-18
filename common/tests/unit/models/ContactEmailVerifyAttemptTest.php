<?php


namespace common\tests;


class ContactEmailVerifyAttemptTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $model = new \common\models\ContactEmailVerifyAttempt();

        $model->email = null;
        $model->code = null;
        $model->ip_address = null;

        $this->assertFalse($model->validate(['email']));
        $this->assertFalse($model->validate(['code']));
        $this->assertFalse($model->validate(['ip_address']));

        $model->email = '123123123';
        $this->assertFalse($model->validate(['email']));
    }
}
