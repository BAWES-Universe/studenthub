<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\fixtures\Company as CompanyFixture;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\models\AdminToken;
use Codeception\Util\HttpCode;

class companyCest
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
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'                
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
        //---------------- list companies ----------------
        
        $I->wantTo('Validate admin > companies api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------------- view company ----------------
        
        $I->wantTo('Validate admin > companies api response for company detail');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('companies/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
                
        //---------------- List Sub Companies for a given company ----------------
        
        $I->wantTo('Validate admin > companies api to list sub companies for a given company');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('companies/sub-companies/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------------- create company account ----------------
        
        $I->wantTo('create a company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'companies', 
            [
                'name' => 'davert', 
                'email' => 'davert@bawes.com',
                'password' => '12345',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully created"
        ]);

        //---------------- create sub company ----------------
        
        $I->wantTo('create a sub company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'companies', 
            [
                'name' => 'davert', 
                'parent' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully created"
        ]);

        //---------------- update company ----------------
        
        $I->wantTo('update company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'companies/1', 
            [
                'name' => 'davert', 
                'email' => 'davert@bawes.com'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully updated"
        ]);
        
        //---------------- delete company ----------------
        
        $I->wantTo('delete company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDELETE('companies/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully deleted"
        ]);        
    }
}
