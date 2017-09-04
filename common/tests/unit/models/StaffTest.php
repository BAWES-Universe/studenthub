<?php
namespace common\tests\unit\models;

use common\fixtures\StaffFixture;
use common\models\Staff;
use Codeception\Specify;

class StaffTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return ['staff' => StaffFixture::className()];
    }

    protected function _before(){}

    protected function _after(){}

    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff testing-staff is in the table', Staff::findOne(['staff_name'=>'testing-staff']))->notNull();
        });

        $this->specify('Staff model should not accept empty required fields', function () {
            $model = new Staff();
            $model->validate();
            expect('staff name is required', $model->errors)->hasKey('staff_name');
            expect('staff email is required', $model->errors)->hasKey('staff_email');
            expect('staff password is required', $model->errors)->hasntKey('staff_password_hash');
            expect('no more fields required', count($model->errors))->equals(2);

        });

        $this->specify('Staff model with required password field', function () {
            $model = new Staff();
            $model->scenario = "newAccount";
            $model->validate();
            expect('staff password is required', $model->errors)->hasKey('staff_password_hash');
            expect('no more fields required', count($model->errors))->equals(3);
        });


        $this->specify('validate Duplicate staff email', function() {
            $model = new Staff();
            $model->staff_email = 'staff3@gmail.com';
            expect('username is duplicated', $model->validate(['staff_email']))->false();
        });
    }

    /**
     * Tests Create, Update and Delete for the staff model
     */
    public function testCrud()
    {
        $this->specify('Create New Staff', function () {
            $model = new Staff();
            $model->staff_name = 'John';
            $model->staff_email = 'john@gmail.com';
            $model->staff_password_hash = \Yii::$app->getSecurity()->generatePasswordHash('123456');
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['staff_name'=>'John']))->notNull();
        });

        $this->specify('Update staff Data', function() {
            $model = Staff::findOne(['staff_email'=>'john@gmail.com']);
            $model->staff_name = 'Doe';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['staff_name'=>'Doe']))->notNull();
        });

        $this->specify('Delete Staff', function() {
            $model = Staff::findOne(['staff_name'=>'Doe']);
            expect('Deletes record', $model->delete())->equals(1);
            expect('Record no longer exists', $model->findOne(['staff_name'=>'Doe']))->null();
        });
    }

}
