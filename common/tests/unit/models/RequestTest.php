<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\RequestFixture;
use common\models\Request;

class RequestTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'requestToken' => RequestFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        $data = new Request();
        $data->contact_uuid = null;
        $data->company_id = null;
        expect('request contact_uuid should be required field', $data->validate(['contact_uuid']))->false();
        expect('request company_id should be required field', $data->validate(['company_id']))->false();

        $data->contact_uuid = 12212;
        $data->company_id = 121212;
        expect('request contact_uuid should be required field', $data->validate(['contact_uuid']))->false();
        expect('request company_id should be required field', $data->validate(['company_id']))->false();
    }
}
