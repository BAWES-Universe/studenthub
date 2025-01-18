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
        return ['staff' => StaffFixture::class];
    }

    protected function _before() {
        
    }

    protected function _after() {
        
    }

    public function testValidatorsForFixtureData() {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Staff::find()->count());
        //});
    }

    /**
     * validation test for empty field
     */
    public function testValidatorsForEmptyFields() {
        //$this->specify('Staff model should not accept empty required fields', function () {
            $model = new Staff();
            $model->validate();
            $this->assertArrayHasKey('staff_name', $model->errors);
            $this->assertArrayHasKey('staff_email', $model->errors);
            $this->assertArrayNotHasKey('staff_password_hash', $model->errors);
            $this->assertArrayHasKey('staff_job_title', $model->errors);
            $this->assertEquals(3, count($model->errors));
        //});
    }

    /**
     * validation for password field
     */
    public function testValidatorsForRequiredPasswordField() {
        //$this->specify('Staff model with required password field', function () {
            $model = new Staff();
            $model->scenario = "newAccount";
            $model->validate();
            $this->assertArrayHasKey('staff_password_hash', $model->errors);
           // $this->assertEquals(3, count($model->errors));
        //});
    }

    /**
     * validation for valid email field
     */
    public function testValidatorsForValidEmailField() {
        //$this->specify('validate Duplicate staff email', function() {
            $model = new Staff();
            $model->staff_email = 'krajcik.viola@bogan.com';
            $this->assertFalse($model->validate(['staff_email']));
        //});
    }

    /**
     * Tests Create for the staff model
     */
    public function testCrudForCreate() {
        //$this->specify('Create New Staff', function () {
            $model = new Staff();
            $model->staff_name = 'John';
            $model->staff_email = 'john@gmail.com';
            $model->staff_job_title = 'Developer';
            $model->staff_auth_key = '';
            $model->staff_password_hash = \Yii::$app->getSecurity()->generatePasswordHash('123456');
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['staff_name' => 'John']));
        //});
    }

    /**
     * Tests Update for the staff model
     */
    public function testCrudForUpdate() {
        //$this->specify('Update staff Data', function () {
            $model = Staff::find()->one();
            $model->staff_name = 'Doe';
            $model->staff_job_title = 'Developer';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['staff_name' => 'Doe']));
        //});
    }

    /**
     * Tests Delete for the staff model
     */
//    public function testCrudForDelete() {
//        //$this->specify('Delete Staff', function() {
//            $model = Staff::find()->one();
//            $staff_id = $model->staff_id;
//            expect('Deletes record', $model->delete())->equals(1);
//            expect('Record no longer exists', $model->findOne($staff_id))->null();
//        //});
//    }

}
