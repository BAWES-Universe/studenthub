<?php
namespace inspector\tests\unit\models;

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
        $this->assertGreaterThan(0, \staff\models\Staff::find()->count());
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
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertEquals('Email cannot be blank.', $model->errors['email'][0]);
        $this->assertArrayHasKey('password', $model->errors);
        $this->assertEquals('Password cannot be blank.', $model->errors['password'][0]);
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
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertEquals('Email is not a valid email address.', $model->errors['email'][0]);
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
        $this->assertArrayHasKey('password', $model->errors);
        $this->assertEquals('Incorrect email or password.', $model->errors['password'][0]);
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
        $this->assertArrayNotHasKey('password', $model->errors);
        $this->assertFalse(Yii::$app->user->isGuest);
    }
}
