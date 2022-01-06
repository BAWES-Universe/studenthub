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
            'request' => RequestFixture::className(),
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
        $data->request_job_description = null;
        $data->request_compensation = null;
        expect('request request_job_description should be required field', $data->validate(['request_job_description']))->false();
        expect('request request_compensation should be required field', $data->validate(['request_compensation']))->false();
        expect('request request_position_title should be required field', $data->validate(['request_position_title']))->false();

        $data = new Request();
        $data->request_job_description = 'test';
        $data->request_compensation = 'test';
        $data->request_status = 1;
        expect('request request_status should be valid', $data->validate(['request_status']))->false();

        $data = new Request();
        $data->request_job_description = 'test';
        $data->request_compensation = 'test';
        $data->company_id = 123123123;
        $data->contact_uuid = 123123123;
        $data->request_status = Request::STATUS_STARTED;
        expect('request company_id should be valid', $data->validate(['company_id']))->false();
        expect('request contact_uuid should be valid', $data->validate(['contact_uuid']))->false();

        $data->staff_id = 99999;
        $data->request_position_type = 'random string';
        $data->request_status = 'random string';
        expect('request staff_id should be valid', $data->validate(['staff_id']))->false();
        expect('request request_position_type should be valid', $data->validate(['request_position_type']))->false();
        expect('request request_status should be valid', $data->validate(['request_status']))->false();

    }
}
