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

class UniversityCest
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
