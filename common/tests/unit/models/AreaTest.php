<?php
namespace common\tests;

use common\fixtures\AreaFixture;

class AreaTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'area' => AreaFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $area = $this->tester->grabFixture('area', 'area0');
        $this->assertTrue($area->save(), 'model adding new area');

        $area->area_name_en = null;
        $area->area_name_ar = null;
        $this->assertFalse($area->validate(['area_name_en']), 'area name should be required field');
        $this->assertFalse($area->validate(['area_name_ar']), 'area name should be required field');
    }
}
