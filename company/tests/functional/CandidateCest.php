<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\CompanyToken as CompanyTokenFixture;
use common\fixtures\Candidate as CandidateFixture;
use Codeception\Util\HttpCode;

class CandidateCest
{
    public $token;

    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'                
            ],
            'companyToken' => [
                'class' => CompanyTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/companyToken.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'                
            ]
        ]);

        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        //-------------- list candidates 
        
        $I->wantTo('Validate company > candidates api');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        //-------------- get total candidates
        
        $I->wantTo('Validate company > candidates/total api to get total candidates');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('candidates/total');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        //-------------- Get candidate work history 

        $I->wantTo('Validate company > candidates/work-history/1 api to list work history');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('candidates/work-history/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
