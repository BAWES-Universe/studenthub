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
            'fileToken' => FileFixture::className()
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
        expect('File file_title should be required field', $data->validate(['file_title']))->false();

        $data->company_id = '123123123';
        expect('Invalid Company id', $data->validate(['company_id']))->false();
    }
}
