<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\BankFixture;
use common\fixtures\StoreFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\TransferFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\CandidateExperienceFixture;
use common\fixtures\CandidateSkillFixture;
use common\models\TransferCandidate;
use common\models\Candidate;
use common\models\Company;
use common\models\Contract;
use common\models\HourlyContract;
use common\models\Transfer;
use common\models\CandidateWorkHistory;


class TransferCandidateTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'company' => CompanyFixture::class,
            'store' => StoreFixture::class,
            'bank' => BankFixture::class,
            'candidate' => CandidateFixture::class,
            'candidateSkill' => CandidateSkillFixture::class,
            'candidateExperience' => CandidateExperienceFixture::class,
            'university' => UniversityFixture::class,
            'country' => CountryFixture::class,
            'transfer' => TransferFixture::class,
            'transferCandidate' => TransferCandidateFixture::class,
            'invoice' => InvoiceFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * test case for model validations
     */
    public function testValidations()
    {
        //$this->specify('fixture data load test', function () {
            $this->assertGreaterThan(0, TransferCandidate::find()->count());
        //});

        //$this->specify('validate required data', function () {

            $model = new TransferCandidate;
            $model->validate();
            $this->assertEquals(1, count($model->errors));
        //});

        //$this->specify('validate invalid integer data', function () {

            $model = new TransferCandidate;
            $model->transfer_id = 'transfer_id';
            $model->candidate_id = 'candidate_id';
            $model->store_id = 'store_id';
            $model->company_id = 'company_id';
            $model->currency_code = "KWD";
            $model->validate();

            $this->assertArrayHasKey('transfer_id', $model->errors);
            $this->assertArrayHasKey('candidate_id', $model->errors);
            $this->assertArrayHasKey('store_id', $model->errors);
            $this->assertArrayHasKey('company_id', $model->errors);
            $this->assertEquals(4, count($model->errors));
        //});

        //$this->specify('validate invalid email data', function () {

            $model = new TransferCandidate;
            $model->currency_code = "KWD";
            $model->transfer_id = 1;
            $model->candidate_id = 1;
            $model->store_id = 1;
            $model->company_id = 1;
            $model->currency_code = "KWD";
            $model->company_email = 'email';
            $model->validate();
            $this->assertArrayHasKey('company_email', $model->errors);
        //});

       /* //$this->specify('validate invalid data length', function () {

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
            $model->currency_code = "KWD";

            $model->store_name = $storeName;
            $model->company_name = $companyName;
            $model->validate();
           // expect('invalid store_name Length', $model->errors)->hasKey('store_name');
            expect('invalid company_name Length', $model->errors)->hasKey('company_name');
        //});*/

        //$this->specify('validate invalid number value', function () {

            $model = new TransferCandidate;
            $model->currency_code = "KWD";
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

            $this->assertArrayHasKey('hours', $model->errors);
            $this->assertArrayHasKey('transfer_cost', $model->errors);
            $this->assertArrayHasKey('bonus', $model->errors);
            $this->assertArrayHasKey('candidate_hourly_rate', $model->errors);
            $this->assertArrayHasKey('company_hourly_rate', $model->errors);
        //});

        //$this->specify('validate non-existing data like candidate_id,transfer_id..', function () {

            $model = new TransferCandidate;
            $model->currency_code = "KWD";
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

            $this->assertArrayHasKey('transfer_id', $model->errors);
            $this->assertArrayHasKey('candidate_id', $model->errors);
            $this->assertArrayHasKey('store_id', $model->errors);
            $this->assertArrayHasKey('company_id', $model->errors);

            $this->assertEquals(4, count($model->errors));
        //});

        //$this->specify('validate valid and existing data', function () {

            $model = new TransferCandidate;
            $model->currency_code = "KWD";
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
            $this->assertEquals(0, count($model->errors));
        //});
    }

    /**
     * test case for getTotalPaidToCandidate
     *
    public function testTotalPaidToCandidate()
    {
        //$this->specify('fixture data load test', function () {
            $this->assertGreaterThan(0, TransferCandidate::find()->count());
        //});

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);

        $output = ($transferCandidateData->candidate_hourly_rate * $transferCandidateData->hours) +
            (($transferCandidateData->candidate_hourly_rate / 60) * $transferCandidateData->minutes) +
            (($transferCandidateData->candidate_hourly_rate / 3600) * $transferCandidateData->seconds) +
            $transferCandidateData->bonus - $transferCandidateData->bonus_commission;

        $this->assertEquals($output, $transferCandidateData->getTotalPaidToCandidate());
    }*/

    /**
     * test case for getTotalPaidByCompany
     *
    public function testTotalPaidByCompany()
    {
        //$this->specify('fixture data load test', function () {
            $this->assertNotNull(TransferCandidate::findOne(1));
        //});

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);

        $output = ($transferCandidateData->company_hourly_rate * $transferCandidateData->hours)
            + (($transferCandidateData->company_hourly_rate / 60) * $transferCandidateData->minutes)
            + (($transferCandidateData->company_hourly_rate / 3600) * $transferCandidateData->seconds)
            + $transferCandidateData->bonus + $transferCandidateData->transfer_cost;
        $this->assertEquals($output, $transferCandidateData->getTotalPaidByCompany());
    }*/

    /**
     * test cases for profit
     *
    public function testProfit()
    {
        //$this->specify('fixture data load test', function () {
            $this->assertNotNull(TransferCandidate::findOne(1));
        //});

        $transferCandidateID = 1;
        $transferCandidateData = TransferCandidate::findOne($transferCandidateID);

        $CompanyTotal = ($transferCandidateData->company_hourly_rate * $transferCandidateData->hours) +
            $transferCandidateData->bonus + $transferCandidateData->transfer_cost
            + (($transferCandidateData->company_hourly_rate / 60) * $transferCandidateData->minutes)
            + (($transferCandidateData->company_hourly_rate / 3600) * $transferCandidateData->seconds);

        $PaidToCandidate = ($transferCandidateData->candidate_hourly_rate * $transferCandidateData->hours) +
            (($transferCandidateData->candidate_hourly_rate / 60) * $transferCandidateData->minutes) +
            (($transferCandidateData->candidate_hourly_rate / 3600) * $transferCandidateData->seconds) +
            $transferCandidateData->bonus - $transferCandidateData->bonus_commission;

        //$TransferCost = '.350';
        $profit = round($CompanyTotal - $PaidToCandidate, 3);//- $TransferCost
        $this->assertEquals($profit, round($transferCandidateData->getProfit(), 3));
    }*/

    /**
     * test case for transferable candidate
     */
    public function testGetPayableCandidateListFormat() {

        //$this->specify('fixture data load test & test to check payable amount is with 3 digit after point', function () {

            $transferCandidateData = TransferCandidate::getPayableCandidateListFormat();

            if ($transferCandidateData && count($transferCandidateData['candidate_list']) >0 ) {
                $this->assertGreaterThan(0, count($transferCandidateData['candidate_list']));

                $testingData = $transferCandidateData['candidate_list'][0];

                // testing for single candidate
                list($whole, $decimal) = explode('.', $testingData['amount']);
                list($whole1, $decimal1) = explode('.', $transferCandidateData['total_amount']);

                $this->assertEquals(3, strlen($decimal));
                $this->assertEquals(3, strlen($decimal1));
                $this->assertEquals(2, strpos('11,11', ','));
                $this->assertFalse(strpos($whole, ','));
                $this->assertFalse(strpos($whole1, ','));
            }
        //});
    }

    /**
     * test case for transferable candidate
     */
    public function testToCheckNotAbleToEditTransferBankAfterPaid() {

        //$this->specify('unit test to check if transfer is paid then bank detail should not be editable', function () {
            $transfer = TransferCandidate::findOne(['paid'=>TransferCandidate::PAID]);

            $transfer->bank_id = 2;
            $this->assertFalse($transfer->validate());
            $this->assertArrayHasKey('bank_id', $transfer->getErrors());
        //});
    }

    public function testManualFallbackResolvesServerRatesWhenPayloadRatesMissing()
    {
        $candidateId = 1;
        $storeId = 31;
        $companyId = 7;

        $this->prepareManualFallbackScenario($candidateId, $storeId, $companyId, 1.400, 1.700);

        $transfer = $this->createMayTransfer();
        $candidate = $this->loadCandidateArray($candidateId);
        $value = [
            'candidate_id' => $candidateId,
            'hours' => 1,
            'minutes' => 1,
            'seconds' => 0,
            'bonus' => 0,
            'currency_code' => 'KWD',
        ];

        $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);

        $this->assertSame('success', $response['operation']);
        $this->assertEqualsWithDelta(1.423, $response['total'], 0.001);
        $this->assertEqualsWithDelta(1.728, $response['company_total'], 0.001);

        $saved = TransferCandidate::find()
            ->where(['transfer_id' => $transfer->transfer_id, 'candidate_id' => $candidateId])
            ->orderBy(['tc_id' => SORT_DESC])
            ->one();

        $this->assertNotNull($saved);
        $this->assertNull($saved->contract_uuid);
        $this->assertEqualsWithDelta(1.400, (float)$saved->candidate_hourly_rate, 0.001);
        $this->assertEqualsWithDelta(1.700, (float)$saved->company_hourly_rate, 0.001);
        $this->assertEqualsWithDelta(1.423, (float)$saved->candidate_total, 0.001);
        $this->assertEqualsWithDelta(1.728, (float)$saved->company_total, 0.001);
    }

    public function testOverlappingContractBehaviorRemainsUnchanged()
    {
        $candidateId = 1;
        $storeId = 31;
        $companyId = 7;

        $this->prepareManualFallbackScenario($candidateId, $storeId, $companyId, 1.400, 1.700);
        $contract = $this->createOverlappingHourlyContract(
            $candidateId,
            $storeId,
            $companyId,
            '2026-05-01',
            2.100,
            2.800
        );

        $transfer = $this->createMayTransfer();
        $candidate = $this->loadCandidateArray($candidateId);
        $value = [
            'candidate_id' => $candidateId,
            'hours' => 1,
            'minutes' => 0,
            'seconds' => 0,
            'bonus' => 0,
            'currency_code' => 'KWD',
        ];

        $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);

        $this->assertSame('success', $response['operation']);
        $this->assertEqualsWithDelta(2.100, $response['total'], 0.001);
        $this->assertEqualsWithDelta(2.800, $response['company_total'], 0.001);

        $saved = TransferCandidate::find()
            ->where(['transfer_id' => $transfer->transfer_id, 'candidate_id' => $candidateId])
            ->orderBy(['tc_id' => SORT_DESC])
            ->one();

        $this->assertNotNull($saved);
        $this->assertSame($contract->contract_uuid, $saved->contract_uuid);
        $this->assertEqualsWithDelta(2.100, (float)$saved->candidate_hourly_rate, 0.001);
        $this->assertEqualsWithDelta(2.800, (float)$saved->company_hourly_rate, 0.001);
    }

    public function testManualFallbackZeroPayableKeepsZeroTotals()
    {
        $candidateId = 1;
        $storeId = 31;
        $companyId = 7;

        $this->prepareManualFallbackScenario($candidateId, $storeId, $companyId, 1.400, 1.700);

        $transfer = $this->createMayTransfer();
        $candidate = $this->loadCandidateArray($candidateId);
        $value = [
            'candidate_id' => $candidateId,
            'hours' => 0,
            'minutes' => 0,
            'seconds' => 0,
            'bonus' => 0,
            'currency_code' => 'KWD',
        ];

        $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);

        $this->assertSame('success', $response['operation']);
        $this->assertSame(0, $response['total']);
        $this->assertSame(0, $response['company_total']);
        $this->assertSame(0, $response['transfer_cost']);
    }

    public function testManualFallbackErrorsWhenServerCandidateRateMissing()
    {
        $candidateId = 1;
        $storeId = 31;
        $companyId = 7;

        $this->prepareManualFallbackScenario($candidateId, $storeId, $companyId, 0, 1.700);

        $transfer = $this->createMayTransfer();
        $candidate = $this->loadCandidateArray($candidateId);
        $value = [
            'candidate_id' => $candidateId,
            'hours' => 1,
            'minutes' => 0,
            'seconds' => 0,
            'bonus' => 0,
            'currency_code' => 'KWD',
        ];

        $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);

        $this->assertSame('error', $response['operation']);
        $this->assertStringContainsString('candidate hourly rate is unavailable', $response['message']);
    }

    public function testManualFallbackErrorsWhenServerCompanyRateMissing()
    {
        $candidateId = 1;
        $storeId = 31;
        $companyId = 7;

        $this->prepareManualFallbackScenario($candidateId, $storeId, $companyId, 1.400, 0);
        Company::updateAll(['company_hourly_rate' => 0], ['company_id' => 3]);

        $transfer = $this->createMayTransfer();
        $candidate = $this->loadCandidateArray($candidateId);
        $value = [
            'candidate_id' => $candidateId,
            'hours' => 1,
            'minutes' => 0,
            'seconds' => 0,
            'bonus' => 0,
            'currency_code' => 'KWD',
        ];

        $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);

        $this->assertSame('error', $response['operation']);
        $this->assertStringContainsString('company hourly rate is unavailable', $response['message']);
    }

    private function prepareManualFallbackScenario($candidateId, $storeId, $companyId, $candidateRate, $companyRate)
    {
        CandidateWorkHistory::deleteAll(['candidate_id' => $candidateId, 'store_id' => $storeId]);

        $contractUuids = Contract::find()
            ->select('contract_uuid')
            ->where(['candidate_id' => $candidateId, 'store_id' => $storeId])
            ->column();

        if (!empty($contractUuids)) {
            HourlyContract::deleteAll(['contract_uuid' => $contractUuids]);
        }

        Contract::deleteAll(['candidate_id' => $candidateId, 'store_id' => $storeId]);

        Candidate::updateAll(['candidate_hourly_rate' => $candidateRate], ['candidate_id' => $candidateId]);
        Company::updateAll(['company_hourly_rate' => $companyRate], ['company_id' => $companyId]);
    }

    private function createMayTransfer()
    {
        $transfer = new Transfer();
        $transfer->company_id = 1;
        $transfer->start_date = '2026-05-01';
        $transfer->end_date = '2026-05-31';
        $transfer->currency_code = 'KWD';
        $transfer->transfer_status = Transfer::STATUS_INITIATED;
        $transfer->transfer_cost = 0;
        $transfer->save(false);

        return $transfer;
    }

    private function loadCandidateArray($candidateId)
    {
        return Candidate::find()
            ->with(['store', 'company'])
            ->andWhere(['candidate_id' => $candidateId])
            ->asArray()
            ->one();
    }

    private function createOverlappingHourlyContract($candidateId, $storeId, $companyId, $startDate, $candidateRate, $companyRate)
    {
        $company = Company::findOne($companyId);

        $contract = new Contract();
        $contract->candidate_id = $candidateId;
        $contract->store_id = $storeId;
        $contract->company_id = $companyId;
        $contract->parent_company_id = $company ? $company->parent_company_id : null;
        $contract->type = Contract::TYPE_HOURLY;
        $contract->start_date = $startDate;
        $contract->end_date = null;
        $contract->deleted = 0;
        $contract->status = Contract::STATUS_ACTIVE;
        $contract->created_by = 1;
        $contract->currency_code = 'KWD';
        $contract->save(false);

        $hourlyContract = new HourlyContract();
        $hourlyContract->contract_uuid = $contract->contract_uuid;
        $hourlyContract->candidate_hourly_rate = $candidateRate;
        $hourlyContract->company_hourly_rate = $companyRate;
        $hourlyContract->save(false);

        return $contract;
    }
}
