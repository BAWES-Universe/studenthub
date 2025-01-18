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
            'request' => RequestFixture::class,
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
        $this->assertFalse($data->validate(['request_job_description']));
        $this->assertFalse($data->validate(['request_compensation']));
        $this->assertFalse($data->validate(['request_position_title']));

        $data = new Request();
        $data->request_job_description = 'test';
        $data->request_compensation = 'test';
        $data->request_status = 1;
        $this->assertFalse($data->validate(['request_status']));

        $data = new Request();
        $data->request_job_description = 'test';
        $data->request_compensation = 'test';
        $data->company_id = 123123123;
        $data->contact_uuid = 123123123;
        $data->request_status = Request::STATUS_STARTED;
        $this->assertFalse($data->validate(['company_id']));
        $this->assertFalse($data->validate(['contact_uuid']));

        $data->staff_id = 99999;
        $data->request_position_type = 'random string';
        $data->request_status = 'random string';
        $this->assertFalse($data->validate(['staff_id']));
        $this->assertFalse($data->validate(['request_position_type']));
        $this->assertFalse($data->validate(['request_status']));
    }
}
