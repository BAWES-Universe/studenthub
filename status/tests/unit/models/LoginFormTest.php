<?php
namespace status\tests\unit\models;

use Yii;
use staff\models\Staff;
use staff\models\LoginForm;
use common\fixtures\StaffFixture;

class LoginFormTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


	public function _fixtures()
	{
        return [ 'staff' => StaffFixture::class];
    }

    /**
     * test fixture load
     */
    public function testLoadingFixture()
    {
        $this->assertGreaterThan(0, \staff\models\Staff::find()->count(),'check if fixture data loaded');
    }

    /**
     * validate Blank Fields login errors
     */
    public function testLoginFormErrorForBlankFields()
    {
        $model = new LoginForm([
            'email' => '',
            'password' => '',
        ]);
        $model->validate();
        $this->assertArrayHasKey('email', $model->errors, 'Email error');
        $this->assertEquals('Email cannot be blank.', $model->errors['email'][0], 'Email error message');
        $this->assertArrayHasKey('password', $model->errors, 'Password error');
        $this->assertEquals('Password cannot be blank.', $model->errors['password'][0], 'Password error message');
    }


    /**
     * Invalid Email Address
     */
    public function testLoginFormErrorForInvalidFields()
    {
        $model = new LoginForm([
            'email' => 'test',
            'password' => 'test',
        ]);
        $model->validate();
        $this->assertArrayHasKey('email', $model->errors, 'invalid Email error');
        $this->assertEquals('Email is not a valid email address.', $model->errors['email'][0], 'invalid Email error message');
    }

    /**
     * Valid Email Address & invalid password
     */
    public function testLoginFormErrorForInvalidPasswordWithValidEmail()
    {
        $staff = Staff::findOne(1);
        $model = new LoginForm([
            'email' => $staff->staff_email,
            'password' => 'invalid password',
        ]);
        $model->validate();
        $this->assertArrayHasKey('password', $model->errors, 'invalid Password error');
        $this->assertEquals('Incorrect email or password.', $model->errors['password'][0], 'invalid Password error message');
    }

    /**
     * Valid Email Address & valid password
     */
    public function testLoginFormForValidPasswordWithValidEmail()
    {
        $staff = Staff::findOne(1);
        $model = new LoginForm([
            'email' => $staff->staff_email,
            'password' => '12345',
        ]);
        $model->validate();
        $this->assertTrue($model->login());
        $this->assertArrayNotHasKey('password', $model->errors, 'error message should not be set');
        $this->assertFalse(Yii::$app->user->isGuest, 'user should be logged in');
    }
}
