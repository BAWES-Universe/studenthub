<?php
namespace common\tests;

use common\fixtures\CompanyContactFixture;

class CompanyContactTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'companyContact' => CompanyContactFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('companyContact', 'company_contact0');
        expect('model adding new contact', $data->save())->true();

        $data->contact_name = null;
        $data->contact_position = null;

        expect('companyContact contact_name should be required field', $data->validate(['contact_name']))->false();
        expect('companyContact contact_position should be required field', $data->validate(['contact_position']))->false();

        $data->company_id = '123123123';
        expect('Invalid Company id', $data->validate(['company_id']))->false();
    }
}
