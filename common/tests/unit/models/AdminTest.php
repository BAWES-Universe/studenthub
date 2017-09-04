<?php
namespace common\tests\models;

use common\models\Admin;
use common\fixtures\AdminFixture;
use Codeception\Specify;

class AdminTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        return ['admin' => AdminFixture::className()];
    }

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Check admin loaded',
                Admin::find()->one()
            )->notNull();
        });

        $this->specify('Admin model fields validation', function () {
            $admin = new Admin;
            $admin->scenario = 'newAccount';
            expect('should not accept empty admin_name', $admin->validate(['admin_name']))->false();
            expect('should not accept empty admin_email', $admin->validate(['admin_email']))->false();
            expect('should not accept empty admin_password_hash', $admin->validate(['admin_password_hash']))->false();

            $admin->admin_email = 'randomString';
            expect('should not accept invalid email', $admin->validate(['admin_email']))->false();

            $admin->admin_email = 'demo@admin.com';
            expect('should accept valid email', $admin->validate(['admin_email']))->true();
        });
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New Admin', function () {
            $model = new Admin();
            $model->admin_name = 'Magan';
            $model->admin_email = 'unique@admin.com';
            $model->setPassword('admin2');
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['admin_name' => 'Magan']))->notNull();
        });

        $this->specify('Update university Data', function() {
            $model = Admin::findOne(['admin_id' => 1]);
            $model->admin_name = 'Chhagan';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['admin_name' => 'Chhagan']))->notNull();
        });
    }
}
