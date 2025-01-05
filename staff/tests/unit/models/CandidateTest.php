<?php
namespace company\tests\unit\models;

use Yii;
use common\fixtures\CandidateFixture;
use staff\models\Candidate;

class CandidateTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    protected function _before() {
	    Yii::$app->params['candidate_max_hourly_rate'] = 2;
    }

    public function _fixtures()
	{
        return [
            'candidates' => CandidateFixture::class,
        ];
    }

    /**
     * test case to test send welcome email
     *
    public function testSendWelcomeEmail() {

        Yii::$app->params['supportEmail'] = 'testing@testing.com';
        $rand = rand(1111,9999);

        $model = Candidate::findOne(1);
        $model->password = 'x12345';

        expect_that($model->sendWelcomeEmail());

        // using Yii2 module actions to check email was sent
        $this->tester->seeEmailIsSent();

        $emailMessage = $this->tester->grabLastSentEmail();
        expect('valid email is sent', $emailMessage)->isInstanceOf('yii\mail\MessageInterface');
        expect($emailMessage->getTo())->hasKey($model->candidate_email);
        expect($emailMessage->getFrom())->hasKey(Yii::$app->params['supportEmail']);
        expect($emailMessage->getSubject())->equals('Welcome to the '.Yii::$app->name);
        expect($emailMessage->toString())->contains($model->candidate_name);
        expect($emailMessage->toString())->contains($model->candidate_email);
        expect($emailMessage->toString())->contains('x12345');
    }*/
}
