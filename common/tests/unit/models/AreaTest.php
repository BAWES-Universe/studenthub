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
            'area' => AreaFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $area = $this->tester->grabFixture('area', 'area0');
        expect('model adding new area', $area->save())->true();

        $area->area_name_en = null;
        $area->area_name_ar = null;
        expect('area name should be required field', $area->validate(['area_name_en']))->false();
        expect('area name should be required field', $area->validate(['area_name_ar']))->false();
    }

}
