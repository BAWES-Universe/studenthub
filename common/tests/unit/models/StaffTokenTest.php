<?php
namespace common\tests\unit\models;

use common\fixtures\StaffTokenFixture;
use common\fixtures\StaffFixture;
use common\models\StaffToken;
use Codeception\Specify;

class StaffTokenTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var | $tester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::class,
            'staff' => StaffFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * testing validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(StaffToken::findOne(['staff_id'=>'1']));
        //});


        //$this->specify('Test Validator', function() {
            $model = new StaffToken();
            $model->validate();
            $this->assertArrayHasKey('staff_id', $model->errors);
            $this->assertArrayHasKey('token_value', $model->errors);
            $this->assertArrayHasKey('token_status', $model->errors);
            $this->assertEquals(3, count($model->errors));
        //});
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(StaffToken::findOne(['staff_id'=>'1']));
        //});


        //$this->specify('Test existing Token', function() {
            $this->assertGreaterThan(31, strlen(StaffToken::generateUniqueTokenString()));
        //});

        //$this->specify('relation testing', function() {
            $this->assertEquals($this->tester->grabFixture('staff', 0)->staff_email, StaffToken::findOne(['staff_id'=>'1'])->getStaff()->one()->staff_email);
        //});
    }
}
