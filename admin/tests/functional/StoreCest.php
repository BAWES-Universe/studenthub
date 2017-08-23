<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\fixtures\Admin as AdminFixture;
use common\fixtures\AdminToken as AdminTokenFixture;
use common\fixtures\Store as StoreFixture;
use Codeception\Util\HttpCode;

class StoreCest
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
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'
            ]
        ]);
        
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List stores 
     * @param FunctionalTester $I
     */
    public function tryToListStores(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > stores api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);        
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
