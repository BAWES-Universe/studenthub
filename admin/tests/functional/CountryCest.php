<?php
namespace admin\tests;

use admin\tests\FunctionalTester;
use Yii;
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

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
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
        $country = Country::find()->one();
        $I->wantTo('Validate admin > countries api response for listing');
        $I->sendGET('v1/countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'country_id' => $country->country_id
        ]);
    }
    
    /**
     * view country list
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $country = Country::find()->one(); 
        
        $I->wantTo('Validate admin > countries api response for view');
        $I->sendGET('v1/countries/' . $country->country_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'country_id' => $country->country_id
        ]);
    }

    /**
     * Create
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('add a country via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/countries',
            [
                'name_en' => 'davert',
                'name_ar' => 'davert',
                'nationality_name_en' => 'davert',
                'nationality_name_ar' => 'davert',
                'google_map' => false
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Country created successfully"
        ]);
    }

    /**
     * Update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a country via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/countries/1',
            [
                'name_en' => 'davert',
                'name_ar' => 'davert',
                'nationality_name_en' => 'davert',
                'nationality_name_ar' => 'davert',
                'google_map' => false
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Country successfully updated"
        ]);
    }
}
