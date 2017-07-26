<?php
namespace common\tests;

use admin\models\Invoice;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Country as CountryFixture;
use common\fixtures\University as UniversityFixture;
use common\fixtures\Store as StoreFixture;
use common\fixtures\Transfer as TransferFixture;
use common\fixtures\TransferCandidate as TransferCandidateFixture;

use Codeception\Specify;

class InvoiceTest extends \Codeception\Test\Unit
{
    use Specify;
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'candidates' => [
                'class' => CandidateFixture::className(),
                'dataFile' => codecept_data_dir() . 'candidate.php'
            ],
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => codecept_data_dir() . 'country.php'
            ],
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => codecept_data_dir() . 'university.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => codecept_data_dir() . 'store.php'
            ],
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => codecept_data_dir() . 'transfer.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => codecept_data_dir() . 'transferCandidate.php'
            ]
        ]);
    }

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
            $model->transfer_id = '3';
            $model->validate();
            expect('invalid transfer id', $model->errors)->haskey('transfer_id');
            expect('error count', count($model->errors))->equals(1);
        });
    }
}