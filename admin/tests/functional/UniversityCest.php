<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\University;
use common\models\AdminToken;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\fixtures\University as UniversityFixture;
use Codeception\Util\HttpCode;

class UniversityCest
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
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/university.php'
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
        
        $I->wantTo('Validate university api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('universities');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        
        //---------------- create ---------------------------
        
        $I->wantTo('create a university via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'universities', 
            [
                'name_en' => 'davert', 
                'name_ar' => 'davert'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University created successfully"
        ]);
        
        $university_id = University::find()
                ->where(['deleted' => 0])
                ->one()
                ->university_id;
        
        //---------------- update ---------------------------
        
        $I->wantTo('update a university via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'universities/' . $university_id, 
            [
                'name_en' => 'davert', 
                'name_ar' => 'davert'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University successfully updated"
        ]);
        
        //---------------- delete ---------------------------
        
        $I->wantTo('delete university via API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('universities/' . $university_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University deleted successfully"
        ]);
    }
}
