<?php
namespace admin\tests;

use common\fixtures\AdminFixture;
use common\models\Admin;
use common\models\AdminToken;
use Yii;
use admin\tests\FunctionalTester;
use common\fixtures\AdminTokenFixture;
use Codeception\Util\HttpCode;


class AdminCest
{
    public $token;

    public function _fixtures() {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'admin' => AdminFixture::className(),
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
        $I->sendGET('v1/admins');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * view inspector
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $inspector = Admin::find()->one();
        
        $I->wantTo('Validate admin api response for listing');
        $I->sendGET('v1/admins/' . $inspector->admin_id);
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
            'v1/admins',
            [
                "name" => "Mohammed Kanso",
                "email" => "inspector@inspector.com",
                "password" => "12345"
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Admin account successfully created"
        ]);
    }

    /**
     * Try to update admins detail
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $admin = Admin::find()->one();
        $I->wantTo('update a admin via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/admins/'.$admin->admin_id,
            [
                "name" => "Mohammed Kanso",
                "email" => "unique@staff.com",
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Admin account successfully updated"
        ]);
    }

    /**
     * Delete admin
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete inspector via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/admins/'.Admin::find()->one()->admin_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Admin account deleted successfully"
        ]);
    }
    
    /**
     * try to reset admin password
     * @param FunctionalTester $I
     */
    public function restCallToResetStaffPassword(FunctionalTester $I)
    {
        $I->wantTo('reset admin password');
        $I->sendPATCH('v1/admins/reset-password/' . Admin::find()->one()->admin_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson([
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ]);
    }
}
