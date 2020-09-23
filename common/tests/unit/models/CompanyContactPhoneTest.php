<?php
namespace common\tests;

use common\fixtures\CompanyContactPhoneFixture;

class CompanyContactPhoneTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'companyContactPhone' => CompanyContactPhoneFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('companyContactPhone', 'company_contact_phone0');
        expect('model adding new companyContactPhone', $data->save())->true();

        $data->phone_number = null;

        expect('companyContactPhone phone_number should be required field', $data->validate(['phone_number']))->false();

        $data->contact_uuid = '123123123';
        expect('Invalid contact uuid', $data->validate(['contact_uuid']))->false();
    }
}
