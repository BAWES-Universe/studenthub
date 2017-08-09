<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\fixtures\Country as CountryFixture;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\models\AdminToken;
use Codeception\Util\HttpCode;

class CountryCest
{
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
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/country.php'                
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
        //---------------- list country ----------------
        
        $I->wantTo('Validate admin > countries api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('countries');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
