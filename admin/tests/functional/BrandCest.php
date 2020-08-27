<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\Brand;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\BrandFixture;
use Codeception\Util\HttpCode;


class BrandCest
{
    public $token, $brand_uuid = 1;

    public function _fixtures()
    {
        return [
            'admin' => AdminFixture::className(),
            'adminToken' => AdminTokenFixture::className(),
            'brand' => BrandFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate brand api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/brands');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View brand detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $brand = Brand::find()->one();
        
        $I->wantTo('Validate brand api to view brand detail');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/brands/' . $brand->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new brand
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a brand via API');
        $I->amBearerAuthenticated($this->token);
        $I->sendPOST(
            'v1/brands',
            [
                'name_en' => 'davert',
                'name_ar' => 'asdas',
                'company_id' => '1'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand created successfully"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a brand via API');
        $I->amBearerAuthenticated($this->token);
        $I->sendPATCH(
            'v1/brands/' . $this->brand_uuid,
            [
                'name_en' => 'davert',
                'name_ar' => 'asdas',
                'company_id' => '1'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand successfully updated"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete brand via API');
        $I->amBearerAuthenticated($this->token);
        $I->sendDelete('v1/brands/' . $this->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
