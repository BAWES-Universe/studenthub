<?php
namespace company\tests\unit\models;

use staff\models\Staff;
use Yii;
use Codeception\Specify;
use staff\fixtures\Staff as StaffFixture;
use staff\models\LoginForm;

class LoginFormTest extends \Codeception\Test\Unit
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
     * test case for login
     */

    public function testLoginCorrect()
    {

        $this->specify('fixture data loading test case', function () {
            expect('check if fixture data loaded',\staff\models\Staff::find()->count())->greaterThan(0);
        });


        $this->specify('validate login errors', function (){

            $model = new LoginForm([
                'email' => '',
                'password' => '',
            ]);
            $model->validate();
            expect('Email error', $model->errors)->hasKey('email');
            expect('Email error', $model->errors['email'][0])->equals('Email cannot be blank.');
            expect('Password error', $model->errors)->hasKey('password');
            expect('Password error', $model->errors['password'][0])->equals('Password cannot be blank.');
        });


        $this->specify('Invalid Email Address', function (){

            $model = new LoginForm([
                'email' => 'test',
                'password' => 'test',
            ]);
            $model->validate();
            expect('invalid Email error', $model->errors)->hasKey('email');
            expect('invalid Email error', $model->errors['email'][0])->equals('Email is not a valid email address.');
        });


        $this->specify('Valid Email Address & invalid password', function (){

            $staff = Staff::findOne(1);
            $model = new LoginForm([
                'email' => $staff->staff_email,
                'password' => 'invalid password',
            ]);
            $model->validate();
            expect('invalid Password error', $model->errors)->hasKey('password');
            expect('invalid Password error', $model->errors['password'][0])->equals('Incorrect email or password.');
        });

        $this->specify('Valid Email Address & valid password', function (){

            $staff = Staff::findOne(1);
            $model = new LoginForm([
                'email' => $staff->staff_email,
                'password' => '12345',
            ]);
            $model->validate();
            $this->specify('user should be able to login with correct credentials', function () use ($model) {
                expect('model should login user', $model->login())->true();
                expect('error message should not be set', $model->errors)->hasntKey('password');
                expect('user should be logged in', Yii::$app->user->isGuest)->false();
            });
        });
    }
}