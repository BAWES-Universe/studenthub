<?php
namespace candidate\tests;

use candidate\models\University;
use yii;
use candidate\tests\FunctionalTester;
use common\models\CandidateToken;
use common\fixtures\UniversityFixture;
use common\fixtures\CandidateTokenFixture;
use Codeception\Util\HttpCode;


class UniversityCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'university' => UniversityFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryToTest(FunctionalTester $I)
    {
        $university = University::find()->one();
        $I->wantTo('Validate university > list api response');
        $I->sendGET('v1/universities');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'university_id'=>$university->university_id
        ]);
    }
}
