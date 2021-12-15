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
            'company' => CompanyFixture::className(),
            'contact' => ContactFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Contact is in the table',
                Contact::find()->one()
            )->notNull();
        });

        $this->specify('Field validation', function() {

            $model = new Contact;

            expect('contact email', $model->validate(['contact_email']))->false();
            //expect('password hash required', $model->validate(['contact_password_hash']))->false();
                
            //email validation

            $model->contact_email = 'ashsakdhkashdkjhkhkhkhtest@gmail.com';
            expect('company email should be valid email', $model->validate(['contact_email']))->true();

            $model->contact_email = 'testtets tests';
            expect('company email should not accept random string', $model->validate(['contact_email']))->false();

            $model->contact_email = $this->tester->grabFixture('contact', 'contact0')->contact_email;
            expect('company contact_email should not exists in db', $model->validate(['contact_email']))->false();

            $model->contact_email = 'comprrrrrr@localhost.com';//new email
            expect('company email should be unique', $model->validate(['contact_email']))->true();

            $model->contact_name = null;

            expect('Contact contact_name should be required field', $model->validate(['contact_name']))->false();

        });
    }
}
