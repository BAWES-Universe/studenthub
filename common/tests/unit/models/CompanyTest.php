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
            'company' => CompanyFixture::className(),
            'store' => StoreFixture::className(),
            'candidates' => CandidateFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    // tests
    public function testValidation()
    {
        $this->specify('Company model fields validation on scenario : newAccount', function () {
            $model = new Company();
            $model->scenario = "newAccount";

            //required field validation

            expect('company name required', $model->validate(['company_name']))->false();

            expect('company email', $model->validate(['company_email']))->false();
            expect('company hourly rate', $model->validate(['company_hourly_rate']))->false();
            
            //email validation

            $model->company_email = 'ashsakdhkashdkjhkhkhkhtest@gmail.com';
            expect('company email should be valid email', $model->validate(['company_email']))->true();

            $model->company_email = 'testtets tests';
            expect('company email should not accept random string', $model->validate(['company_email']))->false();

            $model->company_email = $this->tester->grabFixture('company', 'company0')->company_email;
            expect('company email should not exists in db', $model->validate(['company_email']))->false();

            $model->company_email = 'comprrrrrr@localhost.com';//new email
            expect('company email should be unique', $model->validate(['company_email']))->true();
        });

        $this->specify('Company model fields validation on scenario : newSubAccount', function () {
            $model = new Company();
            $model->scenario = "newSubAccount";

            expect('company name required', $model->validate(['company_name']))->false();
            expect('company hourly rate', $model->validate(['company_hourly_rate']))->false();
            
            // parent_company_id

            $company = $this->tester->grabFixture('company', 'company0');
            $store = $this->tester->grabFixture('store', 'store0');

            //had to create new Object each time to fix validation issue

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = $company->company_id;
            expect('parent_company_id should be company_id of existing company from db', $model->validate(['parent_company_id']))->true();

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = $store->company_id;
            expect('Company can not be assigned to company having stores.', $model->validate(['parent_company_id']))->false();

            $model = new Company();
            $model->scenario = "newSubAccount";
            $model->parent_company_id = '999999999';
            expect(
                'should not accept parent_company_id if could not find company having company_id = given parent_company_id',
                $model->validate(['parent_company_id'])
            )->false();
        });
        
        $this->specify('Company model hourly rate validation on update', function () {
            $model = Company::find()
               // ->where(['company_id' => 1])
                ->one();
            
            //get min value required for company_hourly_rate
            
            $candidate = $model->getCandidates()
                ->orderBy('candidate_hourly_rate DESC')
                ->one();

            $model->company_hourly_rate = $candidate->candidate_hourly_rate;
            
            expect('Company hourly rate should be greater than or equal to candidate hourly rate', $model->validate(['company_hourly_rate']))->true();
            
            $model->company_hourly_rate = $candidate->candidate_hourly_rate -1;
            
            expect('Company hourly rate should not be less than candidate hourly rate', $model->validate(['company_hourly_rate']))->false();            
        });
    }
}
