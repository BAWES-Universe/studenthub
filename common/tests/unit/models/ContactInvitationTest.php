<?php


namespace common\tests;


use common\fixtures\ContactFixture;
use common\models\ContactInvitation;

class ContactInvitationTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'contact' => ContactFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $model = new ContactInvitation();

        $model->email_to_invite = null;
        $this->assertFalse($model->validate(['email_to_invite']));

        $model->email_to_invite = 'unique';
        $this->assertFalse($model->validate(['email_to_invite']));

        $model->contact_uuid = '123123123';
        $this->assertFalse($model->validate(['contact_uuid']));

        $model->company_id = '123123123';
        $this->assertFalse($model->validate(['company_id']));

        //role
    }
}