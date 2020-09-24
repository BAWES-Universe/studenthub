<?php
namespace common\tests;

use common\fixtures\CompanyContactEmailFixture;

class CompanyContactEmailTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'companyContactEmail' => CompanyContactEmailFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('companyContactEmail', 'company_contact_email0');
        expect('model adding new companyContactEmail', $data->save())->true();

        $data->email_address = null;

        expect('companyContactEmail email_address should be required field', $data->validate(['email_address']))->false();

        $data->contact_uuid = '123123123';
        expect('Invalid contact id', $data->validate(['contact_uuid']))->false();
    }
}
