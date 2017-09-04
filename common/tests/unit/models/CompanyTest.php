<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Company;
use common\fixtures\CompanyFixture;
use common\fixtures\StoreFixture;

class CompanyTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _fixtures()
    {
        return [
            'company' => CompanyFixture::className(),
            'store' => StoreFixture::className(),
        ];
    }

    protected function _after() {}

    // tests
    public function testValidation()
    {
        $this->specify('Company model fields validation on scenario : newAccount', function () {
            $model = new Company();
            $model->scenario = "newAccount";

            //required field validation

            expect('company name required', $model->validate(['company_name']))->false();
            expect('password hash required', $model->validate(['company_password_hash']))->false();
            expect('company email', $model->validate(['company_email']))->false();

            //email validation

            $model->company_email = 'ashsakdhkashdkjhkhkhkhtest@gmail.com';
            expect('company email should be valid email', $model->validate(['company_email']))->true();

            $model->company_email = 'testtets tests';
            expect('company email should not accept random string', $model->validate(['company_email']))->false();

            $model->company_email = $this->tester->grabFixture('company', 0)->company_email;
            expect('company email should not exists in db', $model->validate(['company_email']))->false();

            $model->company_email = 'comprrrrrr@localhost.com';//new email
            expect('company email should be unique', $model->validate(['company_email']))->true();

            // company_status should integer

            $model->company_status = '1';
            expect('company status field should accept only integer', $model->validate(['company_status']))->true();

            $model->company_status = NULL;
            expect('company status field should not accept other than integer', $model->validate(['company_status']))->true();
        });

        $this->specify('Company model fields validation on scenario : newSubAccount', function () {
            $model = new Company();
            $model->scenario = "newSubAccount";

            expect('company name required', $model->validate(['company_name']))->false();

            // parent_company_id

            $company = $this->tester->grabFixture('company', 0);
            $store = $this->tester->grabFixture('store', 0);

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
    }
}
