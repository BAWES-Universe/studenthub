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
        return [ 'staff' => StaffFixture::className()];
    }

    /**
     * test fixture load
     */
    public function testLoadingFixture()
    {
        expect('check if fixture data loaded', \staff\models\Staff::find()->count())->greaterThan(0);
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
        expect('Email error', $model->errors)->hasKey('email');
        expect('Email error', $model->errors['email'][0])->equals('Email cannot be blank.');
        expect('Password error', $model->errors)->hasKey('password');
        expect('Password error', $model->errors['password'][0])->equals('Password cannot be blank.');
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
        expect('invalid Email error', $model->errors)->hasKey('email');
        expect('invalid Email error', $model->errors['email'][0])->equals('Email is not a valid email address.');
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
        expect('invalid Password error', $model->errors)->hasKey('password');
        expect('invalid Password error', $model->errors['password'][0])->equals('Incorrect email or password.');
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
        expect('model should login user', $model->login())->true();
        expect('error message should not be set', $model->errors)->hasntKey('password');
        expect('user should be logged in', Yii::$app->user->isGuest)->false();
    }
}
