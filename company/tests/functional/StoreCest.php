<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\CompanyToken as CompanyTokenFixture;
use common\fixtures\Store as StoreFixture;
use Codeception\Util\HttpCode;

class StoreCest
{
    public $token;

    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'                
            ],
            'companyToken' => [
                'class' => CompanyTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/companyToken.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'                
            ]
        ]);

        $this->token = CompanyToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        //------------ List stores 

        $I->wantTo('Validate company > stores api');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        //------------ List sub company stores 

        $I->wantTo('Validate company > stores api to list sub company\'s stores');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('stores/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();

        //------------ List store and sub companies 

        $I->wantTo('Validate company > stores api to list stores and sub company');
        $I->amBearerAuthenticated($this->token);        
        $I->sendGET('stores/company-store');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
