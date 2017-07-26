<?php
namespace common\tests;

use common\models\Store;
use common\fixtures\Store as StoreFixture;
use common\fixtures\Company as CompanyFixture;
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
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => codecept_data_dir() . 'company.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => codecept_data_dir() . 'store.php'
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
            expect('table data is in the table', Store::findOne(['store_name'=>'First Store']))->notNull();
        });

        $this->specify('model should not accept empty required fields', function () {
            $model = new Store();
            $model->validate();
            expect('store name is required', $model->errors)->hasKey('store_name');
        });

        $this->specify('model Data Type fields test', function () {
            $model = new Store();
            $model->store_name = 'GR Outlets';
            $model->company_id = "Company Name";
            $model->store_status = 'Store Status';
            $model->validate();
            expect('company id should accept only integer', $model->errors)->hasKey('company_id');
            expect('store status should accept only integer', $model->errors)->hasKey('store_status');
        });
    }

    /**
     * test case for validate length
     */
    public function testValidatorLength()
    {
        $this->specify('model Data Type fields test', function () {
            $StoreName = 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $model = new Store();
            $model->validate();
            $model->store_name = $StoreName;
            expect('store name should only accept less then equal to 255', $model->errors)->hasKey('store_name');
        });
    }

    /**
     * Test case for soft Delete
     */
    public function testSoftDelete()
    {
        $this->specify('Store check record exist', function () {
            expect('store record is in the table',
                Store::findOne(['store_name'=>'Second Store','deleted' => '0'])
            )->notNull();
        });

        $this->specify('Soft delete Testing', function () {
            $model = Store::findOne(['store_name'=>'Second Store','deleted' => '0']);
            $model->deleted = 1;
            expect('updated successfully', $model->save())->true();
            expect('checking is soft delete Record updated in database', $model->findOne(['store_name'=>'Second Store','deleted' => '0']))->null();
            expect('checking is soft delete Record updated in database', $model->findOne(['store_name'=>'Second Store','deleted' => '1']))->notNull();
        });
    }

    /**
     * test case for SubCompany validation
     */
    public function testValidatorValidCompany()
    {

        $this->specify('Testing Invalid Company', function () {
            $model = new Store();
            $model->company_id = 1; // company id 1 has Sub Company
            $model->store_name = 'New Store';
            $model->store_status = 1;
            $model->store_created_at = '2017-02-23 18:04:42';
            $model->store_updated_at = '2017-02-23 18:04:42';
            $model->deleted = '0';
            $model->validate();
            expect('error count', count($model->errors))->equals(1);
            expect('sub company error case ', $model->errors)->hasKey('company_id');
        });
    }
}