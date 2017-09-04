<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use staff\fixtures\StoreFixture;
use staff\fixtures\CompanyFixture;
use staff\fixtures\StaffTokenFixture;
use staff\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class StoreCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'staff'      => [
				'class'    => StaffFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staff.php'
			],
			'staffToken' => [
				'class'    => StaffTokenFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staffToken.php'
			],
			'company'    => [
				'class'    => CompanyFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/company.php'
			],
			'store'      => [
				'class'    => StoreFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/store.php'
			],
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
     * List store
     * @param FunctionalTester $I
     */
    public function restCallToListStores(FunctionalTester $I)
    {
        $I->wantTo('Get Store listing');
        $I->sendGET('v1/stores');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['store_id'=>1],['store_id'=>2],['store_id'=>3]);
    }

    /**
     * error Create store
     * @param FunctionalTester $I
     */
    public function restCallToCreateStoreWithEmptyStoreNameError(FunctionalTester $I)
    {
        $I->wantTo('show error while creating store without store name');
        $I->sendPOST('v1/stores',['name'=>'','company_id'=>'']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>["store_name"=>"Store Name cannot be blank."]]);
    }

    /**
     * error Create store with store name and invalid company id
     * @param FunctionalTester $I
     */
    public function restCallToCreateStoreWithInvalidCompanyID(FunctionalTester $I)
    {
        $I->wantTo('show error while creating store with store name and invalid company id');
        $I->sendPOST('v1/stores',['name'=>'Adidas Store','company_id'=>'200']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>["company_id"=>"Company ID is invalid."]]);
    }

    /**
     * error Create store with store name and company id
     * having sub company
     * @param FunctionalTester $I
     */
    public function restCallToCreateStoreWithCompanyIDHavingSubCompany(FunctionalTester $I)
    {
        $I->wantTo('show error while creating store with company having sub company');
        $I->sendPOST('v1/stores',['name'=>'Adidas Store','company_id'=>'1']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>["company_id"=>"Store can't be assigned to company having sub companies."]]);
    }

    /**
     * Create store successfully
     * @param FunctionalTester $I
     */
    public function restCallToCreateStoreWithoutAnyError(FunctionalTester $I)
    {
        $I->wantTo('Create store without any error');
        $I->sendPOST('v1/stores',['name'=>'Adidas Store','company_id'=>'2']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"success","message"=>"Store successfully created"]);
    }


    /**
     * try to Update store but showing error
     * @param FunctionalTester $I
     */
    public function restCallToUpdateStoreWithInvalidStoreID(FunctionalTester $I)
    {
        $I->wantTo('update store with invalid id');
        $I->sendPATCH('v1/stores/100',['name'=>'Adidas Store','company_id'=>'2']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>"Store not found."]);
    }

    /**
     * Update store successfully
     * @param FunctionalTester $I
     */
    public function restCallToUpdateStoreSuccessfully(FunctionalTester $I)
    {
        $I->wantTo('update store successfully');
        $I->sendPATCH('v1/stores/3',['name'=>'Third Store','company_id'=>'3']);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"success","message"=>"Store successfully updated"]);
    }

    /**
     * delete store but showing error due to invalid id
     * @param FunctionalTester $I
     */
    public function restCallToDeleteStoreButShowingErrorForInvalidID(FunctionalTester $I)
    {
        $I->wantTo('delete store with error');
        $I->sendDELETE('v1/stores/103');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>"Store not found or already deleted."]);
    }

    /**
     * try to delete store but showing error due to having candidate in it
     * @param FunctionalTester $I
     */
    public function restCallToDeleteStoreButShowingErrorForStoreWithCandidates(FunctionalTester $I)
    {
        $I->wantTo('delete store with candidates');
        $I->sendDELETE('v1/stores/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>"Store have some candidates assigned to it."]);
    }

    /**
     * delete store successfully
     * @param FunctionalTester $I
     */
    public function restCallToDeleteStoreSuccessfully(FunctionalTester $I)
    {
        $I->wantTo('delete store successfully');
        $I->sendDELETE('v1/stores/4');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(["operation"=>"error","message"=>"Store deleted successfully"]);
    }
}
