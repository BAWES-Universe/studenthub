<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\InspectorTokenFixture;
use common\models\Inspector;
use common\models\InspectorToken;

class InspectorTokenTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'inspectorToken' => InspectorTokenFixture::class
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidation()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Inspector::find()->one());
            $this->assertNotNull(InspectorToken::find()->one());
        //});

        //$this->specify('Test Validator', function() {
            $model = new InspectorToken();
            $model->validate();
            $this->assertArrayHasKey('inspector_uuid', $model->errors);
            $this->assertArrayHasKey('token_value', $model->errors);
            $this->assertArrayHasKey('token_status', $model->errors);
            $this->assertEquals(3, count($model->errors));
        //});
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(InspectorToken::find()->one());
        //});

        //$this->specify('Test existing Token', function() {
            $this->assertNull(InspectorToken::findOne(['token_value' => InspectorToken::generateUniqueTokenString()]));
        //});
    }
}
