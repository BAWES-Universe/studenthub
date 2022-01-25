<?php
namespace common\tests;

use common\fixtures\ContactEmailFixture;


class ContactEmailTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'contactEmail' => ContactEmailFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('contactEmail', 'contact_email0');

        expect('model adding new contactEmail', $data->save())->true();

        $data->email_address = null;
        expect('contactEmail email_address should be required field', $data->validate(['email_address']))->false();

        $data->email_address = 'Im-invalid';
        expect('contactEmail email_address should be validated as email', $data->validate(['email_address']))->false();

        $data->contact_uuid = '123123123';
        expect('Invalid contact id', $data->validate(['contact_uuid']))->false();
    }
}
