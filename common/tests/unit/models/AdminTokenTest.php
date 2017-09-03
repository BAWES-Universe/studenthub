<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Admin;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;

class AdminTokenTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => codecept_data_dir() . 'admin.php'
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => codecept_data_dir() . 'adminToken.php'
            ]
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
            expect('Admin is in the table', Admin::find()->one())->notNull();
            expect('Admin Token is in the table', AdminToken::find()->one())->notNull();
        });

        $this->specify('Test Validator', function() {
            $model = new AdminToken();
            $model->validate();
            expect('admin_id required error',$model->errors)->hasKey('admin_id');
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
            expect('Admin Token is in the table', AdminToken::find()->one())->notNull();
        });

        $this->specify('Test existing Token', function() {
            expect(
                'unique token string',
                AdminToken::findOne(['token_value' => AdminToken::generateUniqueTokenString()])
            )->null();
        });
    }
}
