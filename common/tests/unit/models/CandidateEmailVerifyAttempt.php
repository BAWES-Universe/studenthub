<?php


namespace common\tests;


class CandidateEmailVerifyAttempt extends \Codeception\Test\Unit
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
        $model = new \common\models\CandidateEmailVerifyAttempt();

        $model->candidate_email = null;
        $model->code = null;
        $model->ip_address = null;

        expect('email should be required field', $model->validate(['candidate_email']))->false();
        expect('code should be required field', $model->validate(['code']))->false();
        expect('ip address should be required field', $model->validate(['ip_address']))->false();

        $model->candidate_email = '123123123';
        expect('Invalid email', $model->validate(['candidate_email']))->false();
    }
}