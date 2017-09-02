<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\CompanyFixture;
use common\fixtures\CompanyTokenFixture;
use common\fixtures\CandidateFixture;
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

    /**
     * list candidates
     * @param FunctionalTester $I
     */
    public function tryListCandidates(FunctionalTester $I)
    {
        $I->wantTo('Validate company > candidates api');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/candidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * get total candidates
     * @param FunctionalTester $I
     */
    public function getCandidateCount(FunctionalTester $I)
    {
        $I->wantTo('Validate company > candidates/total api to get total candidates');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/candidates/total');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get candidate work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $I->wantTo('Validate company > candidates/work-history/1 api to list work history');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/candidates/work-history/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
