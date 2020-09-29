<?php
namespace staff\tests;

use yii;
use common\models\Brand;
use common\models\StaffToken;
use common\fixtures\BrandFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\StaffTokenFixture;
use common\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class BrandCest
{
    public $token;

	public function _fixtures() {
		return [
			'company'       => CompanyFixture::className(),
			'brand'       => BrandFixture::className(),
			'staffToken' => StaffTokenFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * List Brand record
     * @param FunctionalTester $I
     */
    public function listBrandByWithPagination(FunctionalTester $I)
    {
        $I->wantTo('get Brand listing');
        $I->sendGET('v1/brands');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['brand_id'=>1]);
    }

    public function tryToAddBrandDetail(FunctionalTester $I)
    {
        $I->wantTo('Add Brand detail');
        $I->sendPOST('v1/brands', [
        	'name_en' => 'test',
        	'name_ar' => 'test',
        	'company_id' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function tryToUpdateBrandDetail(FunctionalTester $I)
    {
    	$brand = Brand::find()->one();

        $I->wantTo('Update Brand detail');
        $I->sendPATCH('v1/brands/' . $brand->brand_uuid, [
        	'name_en' => 'test',
        	'name_ar' => 'test',
        	'company_id' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function tryToGetBrandDetail(FunctionalTester $I)
    {
    	$brand = Brand::find()->one();

        $I->wantTo('get Brand detail');
        $I->sendGET('v1/brands/'. $brand->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function tryToDeleteBrandDetail(FunctionalTester $I)
    {
    	$brand = Brand::find()->one();

        $I->wantTo('get Brand detail');
        $I->sendDELETE('v1/brands/'. $brand->brand_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
