<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\models\Bank;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\BankFixture;
use Codeception\Util\HttpCode;


class BankCest
{
    public $token, $bank_id = 2;

    public function _fixtures()
    {
        return [
            'admin' => AdminFixture::className(),
            'adminToken' => AdminTokenFixture::className(),
            'bank' => BankFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate bank api response for listing');
        $I->sendGET('v1/banks');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'bank_id' => 1
        ]);
    }
    
    /**
     * View bank detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $bank = Bank::find()->one();
        $I->wantTo('Validate bank api to view bank detail');
        $I->sendGET('v1/banks/' . $bank->bank_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'bank_id' => $bank->bank_id
        ]);
    }

    /**
     * Try to create new bank
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a bank via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/banks',
            [
                'name' => 'davert',
                'bank_iban_code' => 'asdas',
                'swift_code' => 'HDFCIN010000',
                'address' => '201, Albert Street',
                'type' => 'LCL'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank created successfully"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a bank via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/banks/' . $this->bank_id,
            [
                'name' => 'davert',
                'bank_iban_code' => 'asdas',
                'swift_code' => 'HDFCIN010000',
                'address' => '201, Albert Street',
                'type' => 'LCL'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank successfully updated"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete bank via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/banks/' . $this->bank_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        /*$I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Bank deleted successfully"
        ]);*/
    }
}
