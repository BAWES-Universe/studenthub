<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Transfer;
use common\fixtures\Store as StoreFixture;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\Transfer as TransferFixture;
use common\fixtures\TransferCandidate as TransferCandidateFixture;
use common\models\TransferCandidate;

class TransferCandidateTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => codecept_data_dir() . 'company.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => codecept_data_dir() . 'store.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => codecept_data_dir() . 'candidate.php'
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

    protected function _after()
    {
    }

    public function testValidations()
    {
        $this->specify('fixture data load test', function () {
            expect('if data exist', TransferCandidate::find()->count())->greaterThan(0);
        });

        $this->specify('validate required data', function () {

            $model = new TransferCandidate;
            $model->validate();
            expect('Nothing required', count($model->errors))->equals(0);
        });


        $this->specify('validate invalid integer data', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 'transfer_id';
            $model->candidate_id = 'candidate_id';
            $model->store_id = 'store_id';
            $model->company_id = 'company_id';
            $model->validate();
            expect('invalid transfer_id', $model->errors)->hasKey('transfer_id');
            expect('invalid candidate_id', $model->errors)->hasKey('candidate_id');
            expect('invalid store_id', $model->errors)->hasKey('store_id');
            expect('invalid company_id', $model->errors)->hasKey('company_id');
            expect('invalid Values', count($model->errors))->equals(4);
        });

        $this->specify('validate invalid email data', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 1;
            $model->candidate_id = 1;
            $model->store_id = 1;
            $model->company_id = 1;

            $model->company_email = 'email';
            $model->validate();
            expect('invalid email', $model->errors)->hasKey('company_email');
        });

        $this->specify('validate invalid data length', function () {

            $storeName = 'StoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreName';
            $storeName .= 'StoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreNameStoreName';
            $companyName = 'CompanyNameCompanyNameCompanyNameCompanyNameCompanyNameCompanyNameCompanyName';
            $companyName .= 'CompanyNameCompanyNameCompanyNameCompanyNameCompanyNameCompanyNameCompanyName';
            $model = new TransferCandidate;
            $model->transfer_id = 1;
            $model->candidate_id = 1;
            $model->store_id = 1;
            $model->company_id = 1;
            $model->company_email = 'email@gmail.com';


            $model->store_name = $storeName;
            $model->company_name = $companyName;
            $model->validate();
            expect('invalid store_name Length', $model->errors)->hasKey('store_name');
            expect('invalid company_name Length', $model->errors)->hasKey('company_name');
        });

        $this->specify('validate invalid number value', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 1;
            $model->candidate_id = 1;
            $model->store_id = 1;
            $model->company_id = 1;
            $model->company_email = 'email@gmail.com';
            $model->store_name = 'StoreName';
            $model->company_name = 'CompanyName';

            $model->hours = 'hours';
            $model->transfer_cost = 'transfer_cost';
            $model->bonus = 'bonus';
            $model->candidate_hourly_rate ='candidate_hourly_rate';
            $model->company_hourly_rate = 'company_hourly_rate';

            $model->validate();

            expect('invalid hours Length', $model->errors)->hasKey('hours');
            expect('invalid transfer_cost Length', $model->errors)->hasKey('transfer_cost');
            expect('invalid bonus Length', $model->errors)->hasKey('bonus');
            expect('invalid candidate_hourly_rate data type', $model->errors)->hasKey('candidate_hourly_rate');
            expect('invalid company_hourly_rate data type', $model->errors)->hasKey('company_hourly_rate');
        });

        $this->specify('validate non-existing data like candidate_id,transfer_id..', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 100;
            $model->candidate_id = 102;
            $model->store_id = 3001;
            $model->company_id = 121;
            $model->company_email = 'email@gmail.com';
            $model->store_name = 'StoreName';
            $model->company_name = 'CompanyName';

            $model->hours = '10';
            $model->transfer_cost = '.350';
            $model->bonus = '5';
            $model->candidate_hourly_rate ='1.7';
            $model->company_hourly_rate = '2.0';

            $model->validate();

            expect('invalid transfer_id', $model->errors)->hasKey('transfer_id');
            expect('invalid candidate_id', $model->errors)->hasKey('candidate_id');
            expect('invalid store_id', $model->errors)->hasKey('store_id');
            expect('invalid company_id', $model->errors)->hasKey('company_id');

            expect('error count', count($model->errors))->equals(4);
        });

        $this->specify('validate valid and existing data', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 2;
            $model->candidate_id = 2;
            $model->store_id = 1;
            $model->company_id = 2;
            $model->company_email = 'email@gmail.com';
            $model->store_name = 'StoreName';
            $model->company_name = 'CompanyName';

            $model->hours = '10';
            $model->transfer_cost = '.350';
            $model->bonus = '5';
            $model->candidate_hourly_rate ='1.7';
            $model->company_hourly_rate = '2.0';

            $model->validate();
            expect('error count', count($model->errors))->equals(0);
        });
    }

    /**
     * test case for getTotalPaidToCandidate
     */
    public function testTotalPaidToCandidate()
    {
        $this->specify('fixture data load test', function () {
            expect('if data exist', TransferCandidate::find()->count())->greaterThan(0);
        });

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);
        $output = ($transferCandidateData->candidate_hourly_rate * $transferCandidateData->hours) + $transferCandidateData->bonus;
        expect('validate paid to candidate data ', $transferCandidateData->getTotalPaidToCandidate())->equals($output);
    }

    /**
     * test case for getTotalPaidByCompany
     */

    public function testTotalPaidByCompany()
    {
        $this->specify('fixture data load test', function () {
            expect('if data exist', TransferCandidate::findOne(1))->notNull();
        });

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);

        $output = ($transferCandidateData->company_hourly_rate * $transferCandidateData->hours) + $transferCandidateData->bonus;
        expect('validate paid to candidate data ', $transferCandidateData->getTotalPaidByCompany())->equals($output);
    }


    public function testProfit()
    {
        $this->specify('fixture data load test', function () {
            expect('if data exist', TransferCandidate::findOne(1))->notNull();
        });

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);

        $CompanyTotal = ($transferCandidateData->company_hourly_rate * $transferCandidateData->hours) + $transferCandidateData->bonus;
        $PaidToCandidate = ($transferCandidateData->candidate_hourly_rate * $transferCandidateData->hours) + $transferCandidateData->bonus;
        $TransferCost = '.350';
        $profit = $CompanyTotal - $PaidToCandidate - $TransferCost;
        expect('validate profit value ', $transferCandidateData->getProfit())->equals($profit);
    }
}