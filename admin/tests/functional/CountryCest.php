<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\fixtures\CountryFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use common\models\Country;
use Codeception\Util\HttpCode;


class CountryCest
{
    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'country' => CountryFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List countries
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > countries api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * view country list
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $country = Country::find()->one(); 
        
        $I->wantTo('Validate admin > countries api response for view');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/countries/' . $country->country_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
