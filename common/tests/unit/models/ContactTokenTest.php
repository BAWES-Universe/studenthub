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
            'contact' => ContactFixture::className(),
            'contactToken' => ContactTokenFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after(){}

    // tests
    public function testValidation()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Token is in the table', ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190']))->notNull();
        });

        $this->specify('Test Validator', function() {
            $model = new ContactToken();
            $model->validate();
            expect('contact_uuid required error',$model->errors)->hasKey('contact_uuid');
            expect('token_value required error',$model->errors)->hasKey('token_value');
            expect('token_status required error',$model->errors)->hasKey('token_status');
            expect('total 3 errors',count($model->errors))->equals(3);
        });
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Contact Token is in the table', ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190']))->notNull();
        });

        $this->specify('Test existing Token', function() {
            expect('unique token string',strlen(ContactToken::generateUniqueTokenString()))->greaterThan(31);
        });

        $this->specify('relation testing', function() {
            expect('relative data testing', ContactToken::findOne(['contact_uuid'=>'20666f33-b761-35c0-8520-b8a1902f3190'])->getContact()->one()->contact_email)->equals($this->tester->grabFixture('contact', 'contact0')->contact_email);
        });
    }
}
