<?php
namespace common\tests;

use common\fixtures\CompanyFixture;
use common\fixtures\ContactFixture;
use common\models\Contact;
use Codeception\Specify;


class ContactTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'company' => CompanyFixture::class,
            'contact' => ContactFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Contact::find()->one());
        //});

        //$this->specify('Field validation', function() {

            $model = new Contact;

            $this->assertFalse($model->validate(['contact_email']));
            //expect('password hash required', $model->validate(['contact_password_hash']))->false();
                
            //email validation

            $model->contact_email = 'ashsakdhkashdkjhkhkhkhtest@gmail.com';
            $this->assertTrue($model->validate(['contact_email']));

            $model->contact_email = 'testtets tests';
            $this->assertFalse($model->validate(['contact_email']));

            $model->contact_email = $this->tester->grabFixture('contact', 'contact0')->contact_email;
            $this->assertFalse($model->validate(['contact_email']));

            $model->contact_email = 'comprrrrrr@localhost.com';//new email
            $this->assertTrue($model->validate(['contact_email']));

            $model->contact_name = null;

            $this->assertFalse($model->validate(['contact_name']));

        //});
    }
}
