<?php
namespace common\tests;

use common\models\Bank;
use common\fixtures\Bank as BankFixture;

class BankTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'bank' => [
                'class' => BankFixture::className(),
                'dataFile' => codecept_data_dir() . 'bank.php'
            ]
        ]);
    }

    protected function _after()
    {
    }

    public function testValidate()
    {
        $bank = $this->tester->grabFixture('bank', 0);
                
        expect('model adding new bank', $bank->save())->true();

        //bank name validation 

        $bank->bank_name = null;
        $this->assertFalse($bank->validate(['bank_name']));

        $bank->bank_name = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        $this->assertFalse($bank->validate(['bank_name']));

        $bank->bank_name = 'INDB';
        $this->assertTrue($bank->validate(['bank_name']));

        //bank_swift_code validation 

        $bank->bank_swift_code = null;
        $this->assertFalse($bank->validate(['bank_swift_code']));

        $bank->bank_swift_code = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        $this->assertFalse($bank->validate(['bank_swift_code']));

        $bank->bank_swift_code = 'SW275045';
        $this->assertTrue($bank->validate(['bank_swift_code']));

        //bank_address validation 

        $bank->bank_address = null;
        $this->assertFalse($bank->validate(['bank_address']));

        //bank_transfer_type validation 

        $bank->bank_transfer_type = null;
        $this->assertFalse(in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']));

        $bank->bank_transfer_type = 'SWF';
        $this->assertTrue(in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']));
    }
}