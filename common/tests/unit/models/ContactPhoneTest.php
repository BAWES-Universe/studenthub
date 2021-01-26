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
            'contactPhone' => ContactPhoneFixture::className(),
            'contact' => ContactFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $data = $this->tester->grabFixture('contactPhone', 'contact_phone0');

        expect('model adding new contactPhone', $data->save())->true();

        $data->phone_number = null;

        expect('contactPhone phone_number should be required field', $data->validate(['phone_number']))->false();

        $data->contact_uuid = '123123123';
        expect('Invalid contact uuid', $data->validate(['contact_uuid']))->false();
    }
}
