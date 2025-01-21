<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Admin;
use common\models\AdminToken;
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
            'adminToken' => AdminTokenFixture::class
        ];
    }

    /**
     * Test Validation
     */
    public function testValidation()
    {
        ////$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Admin::find()->one(), 'Admin is in the table');
            $this->assertNotNull(AdminToken::find()->one(), 'Admin Token is in the table');
        ////});

        ////$this->specify('Test Validator', function() {
            $model = new AdminToken();
            $model->validate();
            $this->assertArrayHasKey('admin_id', $model->errors, 'admin_id required error');
            $this->assertArrayHasKey('token_value', $model->errors, 'token_value required error');
            $this->assertArrayHasKey('token_status', $model->errors, 'token_status required error');
            $this->assertCount(3, $model->errors, 'total 3 errors');
        ////});
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        ////$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(AdminToken::find()->one(), 'Admin Token is in the table');
        ////});

        ////$this->specify('Test existing Token', function() {
            $existingToken = AdminToken::find()->one();
            $this->assertNotNull($existingToken, 'Existing token should not be null');
            $this->assertEquals(AdminToken::STATUS_ACTIVE, $existingToken->token_status, 'Token status should be active');
        ////});

        ////$this->specify('Test unique token generation', function() {
            $uniqueToken = AdminToken::generateUniqueTokenString();
            $this->assertNull(AdminToken::findOne(['token_value' => $uniqueToken]), 'Generated token should be unique');
        ////});
    }

    /**
     * Test token expiration
     *
    public function testTokenExpiration()
    {
        ////$this->specify('Test token expiration', function() {
            $token = AdminToken::find()->one();
            $token->token_expiry_datetime = date('Y-m-d H:i:s', strtotime('-1 day'));
            $token->save(false);
            $this->assertEquals(AdminToken::STATUS_EXPIRED, 
                $token->token_status, 
                'Token status should be expired');
       // //});
    }*/

    /**
     * Test token last used datetime update
     *
    public function testTokenLastUsedDatetimeUpdate()
    {
       // //$this->specify('Test token last used datetime update', function() {
            $token = AdminToken::find()->one();
            $lastUsedBefore = $token->token_last_used_datetime;
            $token->afterFind();
            $this->assertNotEquals($lastUsedBefore, 
                $token->token_last_used_datetime, 
                'Token last used datetime should be updated');
       // //});
    }*/
} 