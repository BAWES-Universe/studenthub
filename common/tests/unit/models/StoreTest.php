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
            'company' => CompanyFixture::class,
            'store' => StoreFixture::class
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
        //$this->specify('Fixtures Data loaded Test', function() {
            $this->assertNotNull(Store::find()->one());
        //});

        //$this->specify('model should not accept empty required fields', function () {
            $model = new Store();
            $model->validate();
            $this->assertArrayHasKey('store_name', $model->errors);
            $this->assertArrayHasKey('store_location', $model->errors);
        //});

        //$this->specify('model Data Type fields test', function () {
            $model = new Store();
            $model->store_name = 'GR Outlets';
            $model->company_id = "Company Name";
            $model->store_status = 'Store Status';
            $model->validate();
            $this->assertArrayHasKey('company_id', $model->errors);
            $this->assertArrayHasKey('store_status', $model->errors);
        //});

        //$this->specify('model foreign key fields test', function () {
            $model = new Store();

            $model->store_manager_uuid = 2113123132;
            $model->brand_uuid = 2113123132;
            $model->mall_uuid = 2113123132;

            $model->validate();
            $this->assertArrayHasKey('store_manager_uuid', $model->errors);
            $this->assertArrayHasKey('brand_uuid', $model->errors);
            $this->assertArrayHasKey('mall_uuid', $model->errors);
        //});
    }

    /**
     * test case for validate length
     */
    public function testValidatorLength() {
        //$this->specify('model Data Type fields test', function () {
            $StoreName = 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $StoreName .= 'GR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR OutletsGR Outlets';
            $model = new Store();
            $model->validate();
            $model->store_name = $StoreName;
            $this->assertArrayHasKey('store_name', $model->errors);
        //});
    }

    /**
     * Test case for soft Delete
    public function testSoftDelete() {
        //$this->specify('Store check record exist', function () {
            $this->assertNotNull(Store::findOne(['store_id' => 2]));
        //});

        //$this->specify('Soft delete Testing', function () {
            $model = Store::findOne(['store_id' => 2]);
            $model->deleted = 1;
            $this->assertTrue($model->save());
            $this->assertNull(Store::findOne(['store_id' => 2]));
        //});
    } */

    /**
     * test case for SubCompany validation
     */
    public function testValidatorValidCompany() {

        //$this->specify('Testing Invalid Company', function () {
            $model = new Store();
            $model->company_id = 1; // company id 1 has Sub Company
            $model->store_name = 'New Store';
            $model->store_location = 'New Store Location';
            $model->store_status = 1;
            $model->store_created_at = '2017-02-23 18:04:42';
            $model->store_updated_at = '2017-02-23 18:04:42';
            $model->deleted = '0';
            $model->validate();
            $this->assertEquals(1, count($model->errors));
            $this->assertArrayHasKey('company_id', $model->errors);
        //});
    }
}
