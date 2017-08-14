<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\CompanyToken as CompanyTokenFixture;
use Codeception\Util\HttpCode;

class AccountCest
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
        $I->wantTo('Validate company > account api to change password');
        $I->amBearerAuthenticated($this->token);        
        $I->sendPOST('account/change-password', [
            'old_password' => '123456',
            'new_password' => 'newPassword'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Password changed successfully!"
        ]);
    }
}
