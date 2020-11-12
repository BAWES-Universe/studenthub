<?php
namespace candidate\tests;

use common\models\Country;
use yii;
use candidate\tests\FunctionalTester;
use common\models\CandidateToken;
use common\fixtures\CountryFixture;
use common\fixtures\CandidateTokenFixture;
use Codeception\Util\HttpCode;


class CountryCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'country' => CountryFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function tryToTest(FunctionalTester $I)
    {
        $country = Country::find()->one();
        $I->wantTo('Validate country > list api response');
        $I->sendGET('v1/countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'country_id'=>$country->country_id
        ]);
    }
}
