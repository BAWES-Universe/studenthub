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
            'brand' => BrandFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        $this->assertTrue($brand->save(), 'model adding new brand');

        // Test required fields
        $brand->company_id = null;
        $brand->brand_name_en = null; 
        $brand->brand_name_ar = null;
        $this->assertFalse($brand->validate(['company_id']), 'company_id should be required');
        $this->assertFalse($brand->validate(['brand_name_en']), 'brand_name_en should be required');
        $this->assertFalse($brand->validate(['brand_name_ar']), 'brand_name_ar should be required');

        // Test invalid company_id
        $brand->company_id = '123123123';
        $brand->brand_name_en = 'test';
        $brand->brand_name_ar = 'test';
        $this->assertFalse($brand->validate(['company_id']), 'should not accept invalid company_id');

        // Test string length validations
        $brand->brand_name_en = str_repeat('a', 1256);
        
        $this->assertFalse($brand->validate(['brand_name_en']), 'should not accept too long brand name en');
        
        $brand->brand_name_ar = str_repeat('أ', 256);
        $this->assertFalse($brand->validate(['brand_name_ar']), 'should not accept too long brand name ar');
        
        // Test valid data
        $brand->brand_name_en = 'Valid Brand';
        $brand->brand_name_ar = 'علامة تجارية';
        $brand->company_id = 1;
        $this->assertTrue($brand->validate(), 'should accept valid data');
    }

    public function testSetLogo() {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        $this->assertFalse($brand->setLogo('test.jpg'), 'should not accept invalid file');
        
        // Test valid image file
        $validImagePath = __DIR__ . '/data/test-logo.jpg';
        if(file_exists($validImagePath)) {
            $this->assertTrue($brand->setLogo($validImagePath), 'should accept valid image file');
        }
    }

    public function testDeleteLogoFromCloudinary() {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        $this->assertFalse($brand->deleteLogoFromCloudinary(), 'should return false when no logo exists');
        
        // Test with existing logo
        $brand->brand_logo = 'test_public_id';
        $this->assertFalse($brand->deleteLogoFromCloudinary(), 'should handle non-existent cloudinary image');
    }

    public function testRelations()
    {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        
        $this->assertNotNull($brand->company, 'should have company relation');
        $this->assertNotNull($brand->stores, 'should have stores relation');
        $this->assertNotNull($brand->candidates, 'should have candidates relation');
    }

    public function testAttributeLabels()
    {
        $brand = $this->tester->grabFixture('brand', 'brand0');
        $labels = $brand->attributeLabels();
        
        $this->assertArrayHasKey('company_id', $labels, 'should have company_id label');
        $this->assertArrayHasKey('brand_name_en', $labels, 'should have brand_name_en label');
        $this->assertArrayHasKey('brand_name_ar', $labels, 'should have brand_name_ar label');
        $this->assertArrayHasKey('brand_logo', $labels, 'should have brand_logo label');
    }
} 