<?php
namespace common\tests;

use common\fixtures\ContactPhoneFixture;
use common\fixtures\ContactFixture;


class ContactPhoneTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'contactPhone' => ContactPhoneFixture::class,
            'contact' => ContactFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('contactPhone', 'contact_phone0');

        $this->assertTrue($data->save());

        $data->phone_number = null;

        $this->assertFalse($data->validate(['phone_number']));

        $data->contact_uuid = '123123123';
        $this->assertFalse($data->validate(['contact_uuid']));
    }
}
