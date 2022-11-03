<?php

namespace common\tests\unit\models;

use common\fixtures\StaffFixture;
use common\models\Staff;
use Codeception\Specify;

class StaffTest extends \Codeception\Test\Unit {

    use Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return ['staff' => StaffFixture::className()];
    }

    protected function _before() {
        
    }

    protected function _after() {
        
    }

    public function testValidatorsForFixtureData() {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff testing-staff is in the table', Staff::find()->count())->notNull();
        });
    }

    /**
     * validation test for empty field
     */
    public function testValidatorsForEmptyFields() {
        $this->specify('Staff model should not accept empty required fields', function () {
            $model = new Staff();
            $model->validate();
            expect('staff name is required', $model->errors)->hasKey('staff_name');
            expect('staff email is required', $model->errors)->hasKey('staff_email');
            expect('staff password is required', $model->errors)->hasntKey('staff_password_hash');
            expect('staff job title is required', $model->errors)->hasKey('staff_job_title');
            expect('no more fields required', count($model->errors))->equals(3);
        });
    }

    /**
     * validation for password field
     */
    public function testValidatorsForRequiredPasswordField() {
        $this->specify('Staff model with required password field', function () {
            $model = new Staff();
            $model->scenario = "newAccount";
            $model->validate();
            expect('staff password is required', $model->errors)->hasKey('staff_password_hash');
//            expect('no more fields required', count($model->errors))->equals(3);
        });
    }

    /**
     * validation for valid email field
     */
    public function testValidatorsForValidEmailField() {
        $this->specify('validate Duplicate staff email', function() {
            $model = new Staff();
            $model->staff_email = 'krajcik.viola@bogan.com';
            expect('username is duplicated', $model->validate(['staff_email']))->false();
        });
    }

    /**
     * Tests Create for the staff model
     */
    public function testCrudForCreate() {
        $this->specify('Create New Staff', function () {
            $model = new Staff();
            $model->staff_name = 'John';
            $model->staff_email = 'john@gmail.com';
            $model->staff_job_title = 'Developer';
            $model->staff_auth_key = '';
            $model->staff_password_hash = \Yii::$app->getSecurity()->generatePasswordHash('123456');
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['staff_name' => 'John']))->notNull();
        });
    }

    /**
     * Tests Update for the staff model
     */
    public function testCrudForUpdate() {
        $this->specify('Update staff Data', function () {
            $model = Staff::find()->one();
            $model->staff_name = 'Doe';
            $model->staff_job_title = 'Developer';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['staff_name' => 'Doe']))->notNull();
        });
    }

    /**
     * Tests Delete for the staff model
     */
//    public function testCrudForDelete() {
//        $this->specify('Delete Staff', function() {
//            $model = Staff::find()->one();
//            $staff_id = $model->staff_id;
//            expect('Deletes record', $model->delete())->equals(1);
//            expect('Record no longer exists', $model->findOne($staff_id))->null();
//        });
//    }

}
