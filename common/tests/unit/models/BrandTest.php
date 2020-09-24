<?php
namespace common\tests;

use common\fixtures\BrandFixture;

class BrandTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'brand' => BrandFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        expect('model adding new brand', $brand->save())->true();

        $brand->company_id = null;
        $brand->brand_name_en = null;
        $brand->brand_name_ar = null;
        expect('brand name should be required field', $brand->validate(['company_id']))->false();
        expect('brand name should be required field', $brand->validate(['brand_name_en']))->false();
        expect('brand name should be required field', $brand->validate(['brand_name_ar']))->false();

        $brand->company_id = '123123123';
        $brand->brand_name_en = 'test';
        $brand->brand_name_ar = 'test';
        expect('Invalid Company id', $brand->validate(['company_id']))->false();
    }

    public function testSetLogo() {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        expect('Invalid File', $brand->setLogo('test.jpg'))->false();
    }

    public function testDeleteLogoFromCloudinary() {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        expect('Invalid File', $brand->deleteLogoFromCloudinary())->false();
    }
}
