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

        expect('email should be required field', $model->validate(['email']))->false();
        expect('code should be required field', $model->validate(['code']))->false();
        expect('ip address should be required field', $model->validate(['ip_address']))->false();

        $model->email = '123123123';
        expect('Invalid email', $model->validate(['email']))->false();
    }
}