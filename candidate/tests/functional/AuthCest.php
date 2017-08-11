<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use candidate\models\CandidateToken;
use candidate\fixtures\Candidate as CandidateFixture;
use candidate\fixtures\CandidateToken as CandidateTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
    public $token;
    
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'                
            ],
            'candidateToken' => [
                'class' => CandidateTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidateToken.php'
            ]
        ]);
        
        $this->token = CandidateToken::find()
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        //---------- login ------------------
        
        $I->wantTo('Validate auth > login api');
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('candidate1@bawes.net:123456'));        
        $I->sendGET('auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
                
        //---------- upadate password ------------------
        
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('auth/update-password', [
            'token' => 'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
