<?php

namespace common\tests;

use common\models\Store;
use common\fixtures\StoreFixture;
use common\fixtures\CompanyFixture;
use Codeception\Specify;

class StoreTest extends \Codeception\Test\Unit {

    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'store' => StoreFixture::className()
        ];
    }

    protected function _before() {
        
    }

    protected function _after() {
        
    }

    /**
     * test case for validate required fields
     */
    public function testValidatorRequired() {
        $this->specify('Fixtures Data loaded Test', function() {
            expect('table data is in the table', Store::find()->one())->notNull();
        });

        $this->specify('model should not accept empty required fields', function () {
            $model = new Store();
            $model->validate();
            expect('store name is required', $model->errors)->hasKey('store_name');
            expect('store location is required', $model->errors)->hasKey('store_location');
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

        $this->specify('model foreign key fields test', function () {
            $model = new Store();

            $model->store_manager_uuid = 2113123132;
            $model->brand_uuid = 2113123132;
            $model->mall_uuid = 2113123132;

            $model->validate();
            expect('should not accept random value for store_manager_uuid', $model->errors)->hasKey('store_manager_uuid');
            expect('should not accept random value for brand_uuid', $model->errors)->hasKey('brand_uuid');
            expect('should not accept random value for mall_uuid', $model->errors)->hasKey('mall_uuid');
        });
    }

    /**
     * test case for validate length
     */
    public function testValidatorLength() {
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
    public function testSoftDelete() {
        $this->specify('Store check record exist', function () {
            expect('store record is in the table', Store::findOne(['store_id' => 2])
            )->notNull();
        });

        $this->specify('Soft delete Testing', function () {
            $model = Store::findOne(['store_id' => 2]);
            $model->deleted = 1;
            expect('updated successfully', $model->save())->true();
            expect('checking is soft delete Record updated in database', $model->findOne(['store_id' => 2]))->null();
        });
    }

    /**
     * test case for SubCompany validation
     */
    public function testValidatorValidCompany() {

        $this->specify('Testing Invalid Company', function () {
            $model = new Store();
            $model->company_id = 1; // company id 1 has Sub Company
            $model->store_name = 'New Store';
            $model->store_location = 'New Store Location';
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
