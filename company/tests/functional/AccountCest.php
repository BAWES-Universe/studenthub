<?php
namespace company\tests;

use Yii;
use company\tests\FunctionalTester;
use company\models\CompanyToken;
use common\fixtures\CompanyFixture;
use common\fixtures\CompanyTokenFixture;
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
    public function testChangePassword(FunctionalTester $I)
    {
        $I->wantTo('trying to change password');
        $I->amBearerAuthenticated($this->token);
        $I->sendPOST('v1/account/change-password', [
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
