<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use staff\fixtures\Candidate as CandidateFixture;
use staff\fixtures\University as UniversityFixture;
use staff\fixtures\Country as CountryFixture;
use staff\fixtures\StaffToken as StaffTokenFixture;
use staff\fixtures\staff as StaffFixture;
use Codeception\Util\HttpCode;

class CountryCest
{
    public $token;

    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/country.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'
            ],
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/university.php'
            ],
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staff.php'
            ],
            'staffToken' => [
                'class' => StaffTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staffToken.php'
            ],
        ]);

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
     * list all country without pagination
     * @param FunctionalTester $I
     */
    public function restCallToListCountriesWithoutPagination(FunctionalTester $I)
    {
        $I->wantTo('get all Country listing without pagination');
        $I->sendGET('v1/countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['country_id'=>1,'total_candidates'=>7]);
    }
}
