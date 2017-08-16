<?php
namespace company\tests\unit\models;

use Yii;
use staff\fixtures\Country as CountryFixture;
use staff\fixtures\Candidate as CandidateFixture;
use staff\fixtures\University as UniversityFixture;
use common\fixtures\Store as StoreFixture;
use staff\models\Candidate;

class CandidateTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    protected function _before()
    {
        $this->tester->haveFixtures([
            'candidates' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'
            ],
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/country.php'
            ],
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/university.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'
            ]
        ]);

        Yii::$app->params['candidate_max_hourly_rate'] = 2;
    }

    /**
     * test case to test password email
     */
    public function testPasswordMail() {

        Yii::$app->params['supportEmail'] = 'testing@testing.com';

        $model = Candidate::findOne(1);

        expect_that(Candidate::passwordMail($model,'x12345'));

        // using Yii2 module actions to check email was sent
        $this->tester->seeEmailIsSent();

        $emailMessage = $this->tester->grabLastSentEmail();
        expect('valid email is sent', $emailMessage)->isInstanceOf('yii\mail\MessageInterface');
        expect($emailMessage->getTo())->hasKey($model->candidate_email);
        expect($emailMessage->getFrom())->hasKey(Yii::$app->params['supportEmail']);
        expect($emailMessage->getSubject())->equals('Your internship account password has been reset');
        expect($emailMessage->toString())->contains($model->candidate_name);
        expect($emailMessage->toString())->contains('x12345');
    }

    /**
     * test case to test send welcome email
     */
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
    }
}