<?php
namespace company\tests\unit\models;

use common\models\StaffToken;
use Yii;
use staff\models\Staff;
use staff\models\PasswordResetRequestForm;
use common\fixtures\StaffFixture;

class PasswordResetRequestFormTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


	public function _fixtures()
	{
        return ['staff' => StaffFixture::class];
    }

    /**
     * test case for email validations
     * when Email Empty empty
     */
    public function testEmailErrorWhenEmailIsBlank()
    {
        $model = new PasswordResetRequestForm();
        $model->validate();
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertEquals('Email cannot be blank.', $model->errors['email'][0]);
    }

    /**
     * Validate Invalid Email
     */

    public function testEmailErrorWhenEmailAddressIsInvalid()
    {
        $model = new PasswordResetRequestForm();
        $model->email = 'email';
        $model->validate();
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertEquals('Email is not a valid email address.', $model->errors['email'][0]);
    }

    /**
     * Non-Existing email validation
     */
    public function testEmailErrorWhenEmailAddressNotExist()
    {
        $model = new PasswordResetRequestForm();
        $model->email = 'staff-mail@gmail.com';
        $model->validate();
        $this->assertArrayHasKey('email', $model->errors);
        $this->assertEquals('Email is invalid.', $model->errors['email'][0]);
    }

    /**
     * Working Email Validation
     */
    public function testZeroEmailErrorWhenEmailAddressIsCorrect()
    {
    	$staff = Staff::find()->one();
        $model = new PasswordResetRequestForm();
        $model->email = $staff->staff_email;
        $model->validate();
        $this->assertEquals(0, count($model->errors));
    }

    /**
     * test case to test send Email
     */
    public function testPasswordMail() {

        Yii::$app->params['supportEmail'] = 'testing@testing.com';

        $model = Staff::findOne(1);
        $this->assertTrue(PasswordResetRequestForm::sendEmail($model));
        // using Yii2 module actions to check email was sent
        $this->tester->seeEmailIsSent();
        $emailMessage = $this->tester->grabLastSentEmail();

        $this->assertInstanceOf('yii\mail\MessageInterface', $emailMessage);
        $this->assertArrayHasKey($model->staff_email, $emailMessage->getTo());
        $this->assertArrayHasKey(Yii::$app->params['supportEmail'], $emailMessage->getFrom());
        $this->assertEquals('Password reset token', $emailMessage->getSubject());
        //$this->assertContains($model->staff_email, $emailMessage->toString());
    }
}
