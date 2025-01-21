<?php
namespace common\tests;

use common\fixtures\BankFixture;

class BankTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return ['bank' => BankFixture::class];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $bank = $this->tester->grabFixture('bank', 0);

        $this->assertTrue($bank->save(), 'model adding new bank');

        // bank name validation
        $bank->bank_name = null;
        $this->assertFalse($bank->validate(['bank_name']), 'bank name should be required field');

        $bank->bank_name = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        $this->assertFalse($bank->validate(['bank_name']), 'should not accept too long bank name');

        $bank->bank_name = 'INDB';
        $this->assertTrue($bank->validate(['bank_name']), 'should accept valid bank name');

        // bank_swift_code validation
        $bank->bank_swift_code = null;
        $this->assertFalse($bank->validate(['bank_swift_code']), 'should not accept null for bank swift code');

        $bank->bank_swift_code = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        $this->assertFalse($bank->validate(['bank_swift_code']), 'bank swift code should not be too long');

        $bank->bank_swift_code = 'SW275045';
        $this->assertTrue($bank->validate(['bank_swift_code']), 'should accept valid bank swift code');

        // bank_address validation
        $bank->bank_address = null;
        $this->assertFalse($bank->validate(['bank_address']), 'bank address required');

        $bank->bank_address = '123 Bank Street';
        $this->assertTrue($bank->validate(['bank_address']), 'should accept valid bank address');

        // bank_transfer_type validation
        $bank->bank_transfer_type = null;
        $this->assertFalse(in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']), 'bank transfer type required');

        $bank->bank_transfer_type = 'SWF';
        $this->assertTrue(in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']), 'should accept valid transfer type');

        $bank->bank_transfer_type = 'INVALID';
        $this->assertFalse(in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']), 'should not accept invalid transfer type');
        
    }
}
