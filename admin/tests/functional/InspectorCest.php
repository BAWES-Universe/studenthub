<?php
namespace admin\tests;

use common\fixtures\InspectorFixture;
use common\models\AdminToken;
use Yii;
use admin\tests\FunctionalTester;
use common\models\Inspector;
use common\fixtures\AdminTokenFixture;
use Codeception\Util\HttpCode;


class InspectorCest
{
    public $token;

    public function _fixtures() {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'inspector' => InspectorFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
             ->one()->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate Inspector api response');
        $I->sendGET('v1/inspectors');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * view inspector
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $inspector = Inspector::find()->one();
        
        $I->wantTo('Validate Inspector api response for listing');
        $I->sendGET('v1/inspectors/' . $inspector->inspector_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Create inspector
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a inspector via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/inspectors',
            [
                "name" => "Mohammed Kanso",
                "email" => "inspector@inspector.com",
                "password" => "12345"
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Inspector account successfully created"
        ]);
    }

    /**
     * Try to update staff detail
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $inspector = Inspector::find()->one();
        $I->wantTo('update a inspector via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/inspectors/'.$inspector->inspector_uuid,
            [
                "name" => "Mohammed Kanso",
                "email" => "unique@staff.com",
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Inspector account successfully updated"
        ]);
    }

    /**
     * Delete staff
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete inspector via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/inspectors/'.Inspector::find()->one()->inspector_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Inspector account deleted successfully"
        ]);
    }
    
    /**
     * try to reset candidate password
     * @param FunctionalTester $I
     */
    public function restCallToResetStaffPassword(FunctionalTester $I)
    {
        $I->wantTo('reset inspector password');
        $I->sendPATCH('v1/inspectors/reset-password/' . Inspector::find()->one()->inspector_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson([
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ]);
    }
}
