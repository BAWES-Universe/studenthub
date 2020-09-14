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
        return ['admin' => MallFixture::className()];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Check mall loaded',
                Mall::find()->one()
            )->notNull();
        });

        $this->specify('Admin model fields validation', function () {
            $admin = new Mall();
            expect('should not accept empty name english', $admin->validate(['mall_name_en']))->false();
            expect('should not accept empty name arabic', $admin->validate(['mall_name_ar']))->false();
        });
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New Admin', function () {
            $model = new Mall();
            $model->mall_name_en = 'BigBazar';
            $model->mall_name_ar = 'بيج بازار';
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['mall_name_en' => 'BigBazar']))->notNull();
        });

        $this->specify('Update university Data', function() {
            $model = Mall::find()->one();
            $model->mall_name_en = 'Matro';
            $model->mall_name_ar = 'فقط';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['mall_name_en' => 'Matro']))->notNull();
        });
    }
}
