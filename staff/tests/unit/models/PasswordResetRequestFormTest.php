<?php
namespace company\tests\unit\models;

use staff\models\Staff;
use Yii;
use Codeception\Specify;
use staff\fixtures\Staff as StaffFixture;
use staff\models\PasswordResetRequestForm;

class PasswordResetRequestFormTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    protected function _before()
    {
        $this->tester->haveFixtures([
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staff.php'
            ],
        ]);
    }


    /**
     * test case for email validations
     */

    public function testEmailValidation() {


        $this->specify('Validate Empty Testing',function(){

            $model = new PasswordResetRequestForm();
            $model->validate();
            expect('required validation error',$model->errors)->hasKey('email');
            expect('required validation error message',$model->errors['email'][0])->contains('Email cannot be blank.');
        });

        $this->specify('Validate valid Email',function(){

            $model = new PasswordResetRequestForm();
            $model->email = 'email';
            $model->validate();
            expect('valid email error',$model->errors)->hasKey('email');
            expect('valid email error message',$model->errors['email'][0])->contains('Email is not a valid email address.');
        });


        $this->specify('Non-Existing email validation ',function(){

            $model = new PasswordResetRequestForm();
            $model->email = 'staff-mail@gmail.com';
            $model->validate();
            expect('valid email error',$model->errors)->hasKey('email');
            expect('valid email error message',$model->errors['email'][0])->contains('Email is invalid.');
        });


        $this->specify('Working Email Validation ',function(){

            $model = new PasswordResetRequestForm();
            $model->email = 'staff@gmail.com';
            $model->validate();
            expect('zero error',count($model->errors))->equals(0);
        });
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