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
            'staffToken' => StaffTokenFixture::className(),
            'staff' => StaffFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * testing validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff Token is in the table', StaffToken::findOne(['staff_id'=>'1']))->notNull();
        });


        $this->specify('Test Validator', function() {
            $model = new StaffToken();
            $model->validate();
            expect('staff_id required error',$model->errors)->hasKey('staff_id');
            expect('token_value required error',$model->errors)->hasKey('token_value');
            expect('token_status required error',$model->errors)->hasKey('token_status');
            expect('total 3 errors',count($model->errors))->equals(3);
        });
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff Token is in the table', StaffToken::findOne(['staff_id'=>'1']))->notNull();
        });


        $this->specify('Test existing Token', function() {
            expect('unique token string',strlen(StaffToken::generateUniqueTokenString()))->greaterThan(31);
        });

        $this->specify('relation testing', function() {
            expect('relative data testing', StaffToken::findOne(['staff_id'=>'1'])->getStaff()->one()->staff_email)->equals($this->tester->grabFixture('staff', 0)->staff_email);
        });
    }
}
