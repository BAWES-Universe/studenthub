<?php
namespace common\tests;

use admin\models\Invoice;
use common\fixtures\InvoiceFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\StoreFixture;
use common\fixtures\TransferFixture;
use common\fixtures\TransferCandidateFixture;

use Codeception\Specify;

class InvoiceTest extends \Codeception\Test\Unit
{
    use Specify;
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidates' => CandidateFixture::class,
            'country' => CountryFixture::class,
            'university' => UniversityFixture::class,
            'store' => StoreFixture::class,
            'transfer' => TransferFixture::class,
            'transferCandidate' => TransferCandidateFixture::class,
            'invoice' => InvoiceFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * test case for validate required fields
     */
    public function testValidatorRequired()
    {
        //$this->specify('Fixtures Data loaded Test', function() {
            $this->assertNotNull(Invoice::findOne(['transfer_id'=>'2']));
        //});

        //$this->specify('Create New Data validate', function () {
            $model = new Invoice();
            $model->transfer_id = 'John';
            $model->validate();
            $this->assertArrayHasKey('transfer_id', $model->errors);
            $this->assertEquals(1, count($model->errors));
        //});

        //$this->specify('check if transfer exist', function () {
            $model = new Invoice();
            $model->transfer_id = '93';
            $model->validate();
            $this->assertArrayHasKey('transfer_id', $model->errors);
            $this->assertEquals(1, count($model->errors));
        //});
    }
}
