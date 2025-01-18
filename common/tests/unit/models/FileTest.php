<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\FileFixture;
use common\models\File;


class FileTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'fileToken' => FileFixture::class
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        $data = new File();
        $data->file_title = null;
        $this->assertFalse($data->validate(['file_title']));

        $data->company_id = '123123123';
        $this->assertFalse($data->validate(['company_id']));
    }
}
