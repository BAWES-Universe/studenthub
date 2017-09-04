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
        return ['bank' => BankFixture::className()];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $bank = $this->tester->grabFixture('bank', 0);

        expect('model adding new bank', $bank->save())->true();

        //bank name validation

        $bank->bank_name = null;
        expect('bank name should be required field', $bank->validate(['bank_name']))->false();

        $bank->bank_name = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        expect('should not accept too long bank name', $bank->validate(['bank_name']))->false();

        $bank->bank_name = 'INDB';
        expect('should accept valid bank name', $bank->validate(['bank_name']))->true();

        //bank_swift_code validation

        $bank->bank_swift_code = null;
        expect('should not accept null for bank swift code', $bank->validate(['bank_swift_code']))->false();

        $bank->bank_swift_code = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        expect('bank swift code should not too long', $bank->validate(['bank_swift_code']))->false();

        $bank->bank_swift_code = 'SW275045';
        expect('should accept valid bank swift code', $bank->validate(['bank_swift_code']))->true();

        //bank_address validation

        $bank->bank_address = null;
        expect('bank address required', $bank->validate(['bank_address']))->false();

        //bank_transfer_type validation

        $bank->bank_transfer_type = null;
        expect('bank transfer type required', in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']))->false();

        $bank->bank_transfer_type = 'SWF';
        expect('should accept valid transfer type', in_array($bank->bank_transfer_type, ['LCL', 'SWF', 'TRF']))->true();
    }
}
