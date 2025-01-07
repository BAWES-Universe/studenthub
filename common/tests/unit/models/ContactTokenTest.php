<?php
namespace common\tests;

use Codeception\Specify;
use common\models\ContactToken;
use common\fixtures\ContactTokenFixture;
use common\fixtures\ContactFixture;


class ContactTokenTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'contact' => ContactFixture::class,
            'contactToken' => ContactTokenFixture::class
        ];
    }

    protected function _before(){}

    protected function _after(){}

    // tests
    public function testValidation()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190']));
        //});

        //$this->specify('Test Validator', function() {
            $model = new ContactToken();
            $model->validate();
            $this->assertArrayHasKey('contact_uuid',$model->errors);
            $this->assertArrayHasKey('token_value',$model->errors);
            $this->assertArrayHasKey('token_status',$model->errors);
            $this->assertEquals(3,count($model->errors));
        //});
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190']));
        //});

        //$this->specify('Test existing Token', function() {
            $this->assertGreaterThan(31,strlen(ContactToken::generateUniqueTokenString()));
        //});

        //$this->specify('relation testing', function() {
            $this->assertEquals($this->tester->grabFixture('contact', 'contact0')->contact_email, ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190'])->getContact()->one()->contact_email);
        //});
    }
}
