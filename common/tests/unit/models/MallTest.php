<?php
namespace common\tests\models;

use common\models\Mall;
use common\fixtures\MallFixture;
use Codeception\Specify;

class MallTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures(){
        return ['admin' => MallFixture::class];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Mall::find()->one());
        //});

        //$this->specify('Admin model fields validation', function () {
            $admin = new Mall();
            $this->assertFalse($admin->validate(['mall_name_en']));
            $this->assertFalse($admin->validate(['mall_name_ar']));
        //});
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        //$this->specify('Create New Admin', function () {
            $model = new Mall();
            $model->mall_name_en = 'BigBazar';
            $model->mall_name_ar = 'بيج بازار';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['mall_name_en' => 'BigBazar']));
        //});

        //$this->specify('Update university Data', function() {
            $model = Mall::find()->one();
            $model->mall_name_en = 'Matro';
            $model->mall_name_ar = 'فقط';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['mall_name_en' => 'Matro']));
        //});
    }
}
