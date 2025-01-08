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

        $this->assertTrue($model->sendWelcomeEmail());

        // using Yii2 module actions to check email was sent
        $this->tester->seeEmailIsSent();

        $emailMessage = $this->tester->grabLastSentEmail();
        $this->assertInstanceOf('yii\mail\MessageInterface', $emailMessage);
        $this->assertContains($model->candidate_email, $emailMessage->getTo());
        $this->assertContains(Yii::$app->params['supportEmail'], $emailMessage->getFrom());
        $this->assertEquals('Welcome to the '.Yii::$app->name, $emailMessage->getSubject());
        $this->assertContains($model->candidate_name, $emailMessage->toString());
        $this->assertContains($model->candidate_email, $emailMessage->toString());
        $this->assertContains('x12345', $emailMessage->toString());
    }*/
}
