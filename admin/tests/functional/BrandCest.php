<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\Brand;
use common\models\Company;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\BrandFixture;
use common\fixtures\CompanyFixture;
use Codeception\Util\HttpCode;


class BrandCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'admin' => AdminFixture::class,
            'adminToken' => AdminTokenFixture::class,
            'brand' => BrandFixture::class,
            'company' => CompanyFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
        
        $this->company = Company::find()->one();

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $brand = Brand::find()->one();
        $I->wantTo('Validate brand api response for listing');
        $I->sendGET('v1/brands');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "brand_uuid" => $brand->brand_uuid
        ]);
    }
    
    /**
     * View brand detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $brand = Brand::find()->one();
        
        $I->wantTo('Validate brand api to view brand detail');
        $I->sendGET('v1/brands/' . $brand->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "brand_uuid" => $brand->brand_uuid
        ]);
    }

    /**
     * Try to create new brand
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a brand via API');

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
        $brand = Brand::find()->one();

        $I->wantTo('update a brand via API');

        $I->sendPATCH(
            'v1/brands/' . $brand->brand_uuid,
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
        $brand = Brand::find()->one();

        $I->wantTo('delete brand via API');
        $I->sendDelete('v1/brands/' . $brand->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Brand deleted successfully"
        ]);
    }
}
