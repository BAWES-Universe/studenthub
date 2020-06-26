<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\models\Staff;
use common\fixtures\AdminTokenFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;


class StaffCest
{
    public $token;

    public function _fixtures() {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'staff' => StaffFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
             ->one()->token_value;
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
        $I->wantTo('Validate staff api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/staff');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * view staff
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $staff = Staff::find()->one(); 
        
        $I->wantTo('Validate staff api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/staff/' . $staff->staff_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Create staff
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/staff',
            [
                "name" => "Mohammed Kanso",
                "email" => "staff@staff.com",
                "password" => "12345"
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Staff account successfully created"
        ]);
    }

    /**
     * Try to update staff detail
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/staff/1',
            [
                "name" => "Mohammed Kanso",
                "email" => "unique@staff.com",
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Staff account successfully updated"
        ]);
    }

    /**
     * Delete staff
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/staff/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Staff account deleted successfully"
        ]);
    }
    
    /**
     * try to reset candidate password
     * @param FunctionalTester $I
     */
    public function restCallToResetStaffPassword(FunctionalTester $I)
    {
        $staffID = 1;
        $I->wantTo('reset staff password');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/staff/reset-password/' . $staffID);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson([
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ]);
    }
}
