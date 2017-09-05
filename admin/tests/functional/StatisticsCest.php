<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\fixtures\AdminTokenFixture;
use common\fixtures\CandidateIdCardFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
{
    public $token;

    public function _fixtures() 
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'candidateIdCard' =>  CandidateIdCardFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()->one()->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Get statistics
     * @param FunctionalTester $I
     */
    public function tryToGetStatistics(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > statistics api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
