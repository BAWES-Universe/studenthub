<?php

namespace staff\tests;

use common\models\Mall;
use staff\tests\FunctionalTester;
use common\models\Note;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\MallFixture;
use Codeception\Util\HttpCode;


class MallCest
{
    public $token;
    public $mall;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::class,
            'mall' => MallFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $this->mall = Mall::find()->one();

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate mall api response for listing');
        $I->sendGET('v1/malls');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function tryToListAll(FunctionalTester $I)
    {
        $I->wantTo('Validate mall api response for all listing');
        $I->sendGET('v1/malls/all');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View mall detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate mall api to view note detail');
        $I->sendGET('v1/malls/' . $this->mall->mall_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new mall
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a mall via API');
        $I->sendPOST(
            'v1/malls',
            [
                'name_en' => 'big bazar',
                'name_ar' => 'big bazar'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a mall via API');
        $I->sendPATCH(
            'v1/malls/' . $this->mall->mall_uuid,
            [
                'name_en' => 'Spring specialist',
                'name_ar' => 'Spring specialist'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete mall via API');
        $I->sendDelete('v1/malls/' . $this->mall->mall_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}

