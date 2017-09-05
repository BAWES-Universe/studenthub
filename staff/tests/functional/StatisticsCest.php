<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\CandidateIdCardFixture;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'candidateIdCardFixture' => CandidateIdCardFixture::className(),
            'staffToken' => StaffTokenFixture::className()
        ];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * List university record by student records also
     * @param FunctionalTester $I
     */
    public function restCallToListStatistics(FunctionalTester $I)
    {
        $I->wantTo('get statistics');
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson([
            'id_expired'=>0,
            'id_need_generated'=>6,
            'total_candidates'=>7,
            'total_candidates_assigned'=>7,
            'total_candidates_unassigned'=>0,
        ]);
    }
}
