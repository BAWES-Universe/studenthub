<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Transfer;
use common\fixtures\CompanyFixture;

class TransferTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return ['company' => CompanyFixture::className()];
    }

    protected function _before(){}

    protected function _after(){}

    public function testValidations()
    {
        $this->specify('Transfer model : Company ID validation', function () {
            $model = new Transfer();

            $model->company_id = 'test';
            expect('passing random string', $model->validate(['company_id']))->false();

            $model->company_id = 1;
            expect('passing valid company id', $model->validate(['company_id']))->true();

            $model->company_id = 9999;
            expect('passing invalid company id', $model->validate(['company_id']))->false();
        });

        $this->specify('Transfer model : Transfer status ID validation', function () {
            $model = new Transfer();

            $model->transfer_status = Transfer::STATUS_INITIATED;
            expect('passing valid transfer status', $model->validate(['transfer_status']))->true();

            $model->transfer_status = 99;
            expect('passing invalid transfer status', $model->validate(['transfer_status']))->false();
        });

        $this->specify('Transfer model : Transfer total validation', function () {
            $model = new Transfer();

            $model->total = 43.56;
            expect('passing valid transfer total', $model->validate(['total']))->true();

            $model->total = 'test';
            expect('passing invalid transfer total', $model->validate(['total']))->false();
        });

        $this->specify('Transfer model : Transfer company total validation', function () {
            $model = new Transfer();

            $model->company_total = 43.56;
            expect('passing valid transfer company total', $model->validate(['company_total']))->true();

            $model->company_total = 'test';
            expect('passing invalid transfer company total', $model->validate(['company_total']))->false();
        });
    }
}
