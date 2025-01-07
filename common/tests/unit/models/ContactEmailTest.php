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
            'contactEmail' => ContactEmailFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('contactEmail', 'contact_email0');

        $this->assertTrue($data->save());

        $data->email_address = null;
        $this->assertFalse($data->validate(['email_address']));

        $data->email_address = 'Im-invalid';
        $this->assertFalse($data->validate(['email_address']));

        $data->contact_uuid = '123123123';
        $this->assertFalse($data->validate(['contact_uuid']));
    }
}
