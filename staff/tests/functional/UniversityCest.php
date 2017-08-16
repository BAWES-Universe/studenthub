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
    }

    public function _after(FunctionalTester $I){}

    /**
     * List sub companies
     * @param FunctionalTester $I
     */
    public function listUniversity(FunctionalTester $I)
    {
        $staff = StaffToken::find()->all();
        $I->amBearerAuthenticated($this->token);
        $I->wantTo('get university listing');
        $I->sendGET('v1/universities');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
