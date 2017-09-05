<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\CandidateFixture;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class UniversityCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'candidate'  => CandidateFixture::className(),
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
    public function listUniversityByStudentRecords(FunctionalTester $I)
    {
        $I->wantTo('get university listing');
        $I->sendGET('v1/universities');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['university_id'=>1,'total_candidates'=>7],['university_id'=>3,'total_candidates'=>0]);
    }

    /**
     * list all universities by id
     * @param FunctionalTester $I
     */
    public function listUniversity(FunctionalTester $I)
    {
        $I->wantTo('get university listing');
        $I->sendGET('v1/universities/all');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['university_id'=>1]);
    }
}
