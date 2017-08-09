<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\Staff;
use common\models\AdminToken;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\fixtures\Staff as StaffFixture;
use Codeception\Util\HttpCode;

class StaffCest
{
    public $token;
    
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/admin.php'                
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/adminToken.php'
            ],
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staff.php'
            ]
        ]);
        
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        //---------------- listing ---------------------------
        
        $I->wantTo('Validate staff api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('staff');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------------- create ---------------------------
        
        $I->wantTo('create a staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'staff', 
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
        
        $staff_id = Staff::find()
                ->where(['deleted' => 0])
                ->one()
                ->staff_id;
        
        //---------------- update ---------------------------
        
        $I->wantTo('update a staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'staff/' . $staff_id, 
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
        
        //---------------- delete ---------------------------
        
        $I->wantTo('delete staff via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('staff/' . $staff_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Staff account deleted successfully"
        ]);
    }
}
