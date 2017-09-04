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
            'candidates' => CandidateFixture::className(),
            'country' => CountryFixture::className(),
            'university' => UniversityFixture::className(),
            'store' => StoreFixture::className(),
            'transfer' => TransferFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'invoice' => InvoiceFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * test case for validate required fields
     */
    public function testValidatorRequired()
    {
        $this->specify('Fixtures Data loaded Test', function() {
            expect('table data is in the table', Invoice::findOne(['transfer_id'=>'2']))->notNull();
        });

        $this->specify('Create New Data validate', function () {
            $model = new Invoice();
            $model->transfer_id = 'John';
            $model->validate();
            expect('invalid transfer id', $model->errors)->haskey('transfer_id');
            expect('error count', count($model->errors))->equals(1);
        });

        $this->specify('check if transfer exist', function () {
            $model = new Invoice();
            $model->transfer_id = '93';
            $model->validate();
            expect('invalid transfer id', $model->errors)->haskey('transfer_id');
            expect('error count', count($model->errors))->equals(1);
        });
    }
}
