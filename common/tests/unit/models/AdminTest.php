<?php
namespace common\tests\models;

use common\models\Admin;
use common\fixtures\AdminFixture;
// use Codeception\Specify;

class AdminTest extends \Codeception\Test\Unit
{
    // use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures(){
        return ['admin' => AdminFixture::class];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $admin = Admin::find()->one();

        // Fixtures should be loaded
        $this->assertNotNull($admin, "Check admin loaded");

        // //$this->specify('Admin model fields validation', function () {
        // Admin model fields validation
        $admin = new Admin();
        $admin->scenario = 'newAccount';
        $this->assertFalse($admin->validate(['admin_name']), 'should not accept empty admin_name');
        $this->assertFalse($admin->validate(['admin_email']), 'should not accept empty admin_email');
        $this->assertFalse($admin->validate(['admin_password_hash']), 'should not accept empty admin_password_hash');

        $admin->admin_email = 'randomString';
        $this->assertFalse($admin->validate(['admin_email']), 'should not accept invalid email');

        $admin->admin_email = 'demo@admin.com';
        $this->assertTrue($admin->validate(['admin_email']), 'should accept valid email');
       // //});
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        ////$this->specify('Create New Admin', function () {
        // Create New Admin
        $model = new Admin();
        $model->admin_name = 'Magan';
        $model->admin_email = 'unique@admin.com';
        $model->admin_auth_key = '';
        $model->setPassword('admin2');
        $this->assertTrue($model->save(), 'Created successfully');
        $this->assertNotNull($model->findOne(['admin_name' => 'Magan']), 'Record is in database');
        $model->admin_name = 'Magan';
        $model->admin_email = 'unique@admin.com';

        // Update university Data
        $model = Admin::findOne(['admin_id' => 1]);
        $model->admin_name = 'Chhagan';
        $model->admin_auth_key = '';
        $this->assertTrue($model->save(), 'updated successfully');
        $this->assertNotNull($model->findOne(['admin_name' => 'Chhagan']), 'Updated Record is in database');
        
        $model = Admin::findOne(['admin_id' => 1]);
        $model->admin_name = 'Chhagan';
        $model->admin_auth_key = '';
        $this->assertTrue($model->save(), 'updated successfully');
        $this->assertNotNull($model->findOne(['admin_name' => 'Chhagan']), 'Updated Record is in database'); 
        
        ////});
    }
}
