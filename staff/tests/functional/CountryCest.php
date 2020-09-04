<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\CandidateFixture;
use common\fixtures\StaffTokenFixture;
use Codeception\Util\HttpCode;

class CountryCest
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
     * List country record with pagination
     * @param FunctionalTester $I
     */
    public function restCallToListCountriesWithPagination(FunctionalTester $I)
    {
        $I->wantTo('get Country listing with pagination');
        $I->sendGET('v1/countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['country_id'=>1,'total_candidates'=>7]);
    }

    /**
     * View country detail
     * @param FunctionalTester $I
     */
    public function restCallToViewCountryDetail(FunctionalTester $I)
    {
        $I->wantTo('get country detail');
        $I->sendGET('v1/countries/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['country_id'=>1]);
    }

    /**
     * list all country without pagination
     * @param FunctionalTester $I
     */
    public function restCallToListCountriesWithoutPagination(FunctionalTester $I)
    {
        $I->wantTo('get all Country listing without pagination');
        $I->sendGET('v1/countries/all');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['country_id'=>1,'total_candidates'=>7]);
    }
}


