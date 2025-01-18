<?php

namespace common\tests\unit\models;

use common\fixtures\InspectorFixture;
use common\models\Inspector;
use Codeception\Specify;

class InspectorTest extends \Codeception\Test\Unit {

    use Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return ['inspector' => InspectorFixture::class];
    }

    protected function _before() {
        
    }

    protected function _after() {
        
    }

    public function testValidatorsForFixtureData() {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Inspector::find()->count());
        //});
    }

    /**
     * validation test for empty field
     */
    public function testValidatorsForEmptyFields() {
        //$this->specify('Inspector model should not accept empty required fields', function () {
            $model = new Inspector();
            $model->validate();
            $this->assertArrayHasKey('inspector_name', $model->errors);
            $this->assertArrayHasKey('inspector_email', $model->errors);
            $this->assertArrayHasKey('inspector_password_hash', $model->errors);
            $this->assertEquals(3, count($model->errors));
        //});
    }

    /**
     * validation for valid email field
     */
    public function testValidatorsForValidEmailField() {
        //$this->specify('validate Duplicate Inspector email', function() {
            $data = Inspector::find()->one();
            $model = new Inspector();
            $model->inspector_email = $data->inspector_email;
            $this->assertFalse($model->validate(['inspector_email']));
        //});
    }

    /**
     * Tests Create for the Inspector model
     */
    public function testCrudForCreate() {
        //$this->specify('Create New Inspector', function () {
            $model = new Inspector();
            $model->inspector_name = 'John';
            $model->inspector_email = 'john@gmail.com';
            $model->inspector_auth_key = '';
            $model->inspector_password_hash = \Yii::$app->getSecurity()->generatePasswordHash('123456');
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['inspector_name' => 'John']));
        //});
    }

    /**
     * Tests Update for the Inspector model
     */
    public function testCrudForUpdate() {
        //$this->specify('Update Inspector Data', function () {
            $model = Inspector::find()->one();
            $model->inspector_name = 'Doe';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['inspector_name' => 'Doe']));
        //});
    }

    /**
     * Tests Delete for the Inspector model
     */
    public function testCrudForDelete() {
        //$this->specify('Delete Inspector', function() {
            $model = Inspector::find()->one();
            $Inspector_id = $model->inspector_uuid;
            $this->assertEquals(1, $model->delete());
            $this->assertNull($model->findOne($Inspector_id));
        //});
    }

}
