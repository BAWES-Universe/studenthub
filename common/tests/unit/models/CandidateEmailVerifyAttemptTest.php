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
        $this->assertFalse($model->validate(['candidate_email']), 'email should be required field');
        $this->assertFalse($model->validate(['code']), 'code should be required field');
        $this->assertFalse($model->validate(['ip_address']), 'ip address should be required field');
        
        $model->candidate_email = '123123123';
        $this->assertFalse($model->validate(['candidate_email']), 'Invalid email');
    }
}