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
            'contact' => ContactFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $model = new ContactInvitation();

        $model->email_to_invite = null;
        expect('ContactInvitation email_to_invite should be required field', $model->validate(['email_to_invite']))->false();

        $model->email_to_invite = 'unique';
        expect('ContactInvitation email_to_invite should be email field', $model->validate(['email_to_invite']))->false();

        $model->contact_uuid = '123123123';
        expect('Invalid contact uuid', $model->validate(['contact_uuid']))->false();

        $model->company_id = '123123123';
        expect('Invalid company id', $model->validate(['company_id']))->false();

        //role
    }
}