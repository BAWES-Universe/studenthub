<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\CompanyToken as CompanyTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
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
            ]
        ]);
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        //---------- login ------------------
        
        $I->wantTo('Validate auth > login api');
        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('company@company.com:123456'));        
        $I->sendGET('auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
                
        //---------- upadate password ------------------
        
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('auth/update-password', [
            'token' => 'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
