<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Company;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreFixture;
use common\fixtures\CandidateFixture;

class CompanyTest extends \Codeception\Test\Unit
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
            'candidates' => CandidateFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    // tests
    public function testValidation()
    {
        //$this->specify('Company model fields validation on scenario : newAccount', function () {
            $model = new Company();
            $model->scenario = "newAccount";

            //required field validation

            $this->assertFalse($model->validate(['company_name']));

            $this->assertFalse($model->validate(['company_email']));
            $this->assertFalse($model->validate(['company_hourly_rate']));
            
            //email validation

            $model->company_email = 'ashsakdhkashdkjhkhkhkhtest@gmail.com';
            $this->assertTrue($model->validate(['company_email']));

            $model->company_email = 'testtets tests';
            $this->assertFalse($model->validate(['company_email']));

            $model->company_email = $this->tester->grabFixture('company', 'company0')->company_email;
            $this->assertFalse($model->validate(['company_email']));

            $model->company_email = 'comprrrrrr@localhost.com';//new email
            $this->assertTrue($model->validate(['company_email']));
        //});

        //$this->specify('Company model fields validation on scenario : newSubAccount', function () {
            $model = new Company();
            $model->scenario = "newSubAccount";

            $this->assertFalse($model->validate(['company_name']));
            $this->assertFalse($model->validate(['company_hourly_rate']));
            
            // parent_company_id

            $company = $this->tester->grabFixture('company', 'company0');
            $store = $this->tester->grabFixture('store', 'store0');

            //had to create new Object each time to fix validation issue

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = $company->company_id;
            $this->assertTrue($model->validate(['parent_company_id']));

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = $store->company_id;
            $this->assertFalse($model->validate(['parent_company_id']));

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = '999999999';
            $this->assertFalse($model->validate(['parent_company_id']));
        //});
        
        //$this->specify('Company model hourly rate validation on update', function () {
            $model = Company::find()
               // ->where(['company_id' => 1])
                ->one();
            
            //get min value required for company_hourly_rate
            
            $candidate = $model->getCandidates()
                ->orderBy('candidate_hourly_rate DESC')
                ->one();

            $model->company_hourly_rate = $candidate->candidate_hourly_rate;
            
            $this->assertTrue($model->validate(['company_hourly_rate']));
            
            $model->company_hourly_rate = $candidate->candidate_hourly_rate -1;
            
            $this->assertFalse($model->validate(['company_hourly_rate']));            
        //});
    }
}
