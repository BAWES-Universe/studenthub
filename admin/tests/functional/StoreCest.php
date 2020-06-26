<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\models\Store;
use common\fixtures\AdminTokenFixture;
use common\fixtures\StoreFixture;
use Codeception\Util\HttpCode;


class StoreCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'store' => StoreFixture::className(),
        ];
    }
    
    public function _before(FunctionalTester $I)
    {
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
    
    /**
     * view stores
     * @param FunctionalTester $I
     */
    public function tryToViewStore(FunctionalTester $I)
    {
        $store = Store::find()->one(); 
        
        $I->wantTo('Validate admin > stores api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/stores/' . $store->store_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
