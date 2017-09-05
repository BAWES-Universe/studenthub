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
        return ['staff' => StaffFixture::className()];
    }

    /**
     * test case for email validations
     * when Email Empty empty
     */
    public function testEmailErrorWhenEmailIsBlank()
    {
        $model = new PasswordResetRequestForm();
        $model->validate();
        expect('required validation error', $model->errors)->hasKey('email');
        expect('required validation error message', $model->errors['email'][0])->contains('Email cannot be blank.');
    }

    /**
     * Validate Invalid Email
     */

    public function testEmailErrorWhenEmailAddressIsInvalid()
    {
        $model = new PasswordResetRequestForm();
        $model->email = 'email';
        $model->validate();
        expect('valid email error', $model->errors)->hasKey('email');
        expect('valid email error message', $model->errors['email'][0])->contains('Email is not a valid email address.');
    }

    /**
     * Non-Existing email validation
     */
    public function testEmailErrorWhenEmailAddressNotExist()
    {
        $model = new PasswordResetRequestForm();
        $model->email = 'staff-mail@gmail.com';
        $model->validate();
        expect('valid email error', $model->errors)->hasKey('email');
        expect('valid email error message', $model->errors['email'][0])->contains('Email is invalid.');
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
        expect('zero error', count($model->errors))->equals(0);
    }

    /**
     * test case to test send Email
     */
    public function testPasswordMail() {

        Yii::$app->params['supportEmail'] = 'testing@testing.com';

        $model = Staff::findOne(1);
        expect_that(PasswordResetRequestForm::sendEmail($model));
        // using Yii2 module actions to check email was sent
        $this->tester->seeEmailIsSent();
        $emailMessage = $this->tester->grabLastSentEmail();
        expect('valid email is sent', $emailMessage)->isInstanceOf('yii\mail\MessageInterface');
        expect($emailMessage->getTo())->hasKey($model->staff_email);
        expect($emailMessage->getFrom())->hasKey(Yii::$app->params['supportEmail']);
        expect($emailMessage->getSubject())->equals('Password reset token');
        expect($emailMessage->toString())->contains($model->staff_email);
    }
}
