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
        return ['inspector' => InspectorFixture::className()];
    }

    protected function _before() {
        
    }

    protected function _after() {
        
    }

    public function testValidatorsForFixtureData() {
        $this->specify('Fixtures should be loaded', function() {
            expect('Inspector testing-inspector is in the table', Inspector::find()->count())->notNull();
        });
    }

    /**
     * validation test for empty field
     */
    public function testValidatorsForEmptyFields() {
        $this->specify('Inspector model should not accept empty required fields', function () {
            $model = new Inspector();
            $model->validate();
            expect('Inspector name is required', $model->errors)->hasKey('inspector_name');
            expect('Inspector email is required', $model->errors)->hasKey('inspector_email');
            expect('Inspector password is required', $model->errors)->hasKey('inspector_password_hash');
            expect('no more fields required', count($model->errors))->equals(3);
        });
    }

    /**
     * validation for valid email field
     */
    public function testValidatorsForValidEmailField() {
        $this->specify('validate Duplicate Inspector email', function() {
            $data = Inspector::find()->one();
            $model = new Inspector();
            $model->inspector_email = $data->inspector_email;
            expect('username is duplicated', $model->validate(['inspector_email']))->false();
        });
    }

    /**
     * Tests Create for the Inspector model
     */
    public function testCrudForCreate() {
        $this->specify('Create New Inspector', function () {
            $model = new Inspector();
            $model->inspector_name = 'John';
            $model->inspector_email = 'john@gmail.com';
            $model->inspector_auth_key = '';
            $model->inspector_password_hash = \Yii::$app->getSecurity()->generatePasswordHash('123456');
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['inspector_name' => 'John']))->notNull();
        });
    }

    /**
     * Tests Update for the Inspector model
     */
    public function testCrudForUpdate() {
        $this->specify('Update Inspector Data', function () {
            $model = Inspector::find()->one();
            $model->inspector_name = 'Doe';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['inspector_name' => 'Doe']))->notNull();
        });
    }

    /**
     * Tests Delete for the Inspector model
     */
    public function testCrudForDelete() {
        $this->specify('Delete Inspector', function() {
            $model = Inspector::find()->one();
            $Inspector_id = $model->inspector_uuid;
            expect('Deletes record', $model->delete())->equals(1);
            expect('Record no longer exists', $model->findOne($Inspector_id))->null();
        });
    }

}
