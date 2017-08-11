<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\Bank;
use common\models\AdminToken;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\fixtures\Bank as BankFixture;
use Codeception\Util\HttpCode;

class BankCest
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
            'bank' => [
                'class' => BankFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/bank.php'
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
        $bank_id = 2;
        
        //---------------- listing ---------------------------
        
        $I->wantTo('Validate bank api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('banks');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        //---------------- create ---------------------------
        
        $I->wantTo('create a bank via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'banks', 
            [
                'name' => 'davert', 
                'swift_code' => 'HDFCIN010000',
                'address' => '201, Albert Street',
                'type' => 'LCL'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank created successfully"
        ]);
        
        //---------------- update ---------------------------
        
        $I->wantTo('update a bank via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'banks/' . $bank_id, 
            [
                'name' => 'davert', 
                'swift_code' => 'HDFCIN010000',
                'address' => '201, Albert Street',
                'type' => 'LCL'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank successfully updated"
        ]);
        
        //---------------- delete ---------------------------
        
        $I->wantTo('delete bank via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('banks/' . $bank_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank deleted successfully"
        ]);
    }
}
