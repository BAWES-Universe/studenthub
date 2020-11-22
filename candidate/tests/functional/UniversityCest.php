<?php
namespace candidate\tests;

use candidate\models\University;
use common\fixtures\CandidateFixture;
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
            'candidate' => CandidateFixture::className(),
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

    /**
     * Try to add university 
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('Add new university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/universities', [
            'name' => 'new university'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["operation" => "success"]);
    }
        
    /**
     * Try to check if university exist with given name 
     * @param FunctionalTester $I
     */
    public function tryToCheckExists(FunctionalTester $I)
    {
        $I->wantTo('Check if university exists');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST('v1/universities/is-exists', [
            'keyword' => 'Abu Dhabi University'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    } 
}
