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
            'inspectorToken' => InspectorTokenFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidation()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Inspector is in the table', Inspector::find()->one())->notNull();
            expect('Inspector Token is in the table', InspectorToken::find()->one())->notNull();
        });

        $this->specify('Test Validator', function() {
            $model = new InspectorToken();
            $model->validate();
            expect('inspector_uuid required error',$model->errors)->hasKey('inspector_uuid');
            expect('token_value required error',$model->errors)->hasKey('token_value');
            expect('token_status required error',$model->errors)->hasKey('token_status');
            expect('total 3 errors',count($model->errors))->equals(3);
        });
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Inspector Token is in the table', InspectorToken::find()->one())->notNull();
        });

        $this->specify('Test existing Token', function() {
            expect(
                'unique token string',
                InspectorToken::findOne(['token_value' => InspectorToken::generateUniqueTokenString()])
            )->null();
        });
    }
}
