<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\BankFixture;
use staff\fixtures\StaffTokenFixture;
use staff\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class BankCest
{
    public $token;

	public function _fixtures() {
		return [
			'bank'       => [
				'class'    => BankFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/bank.php'
			],
			'staff'      => [
				'class'    => StaffFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staff.php'
			],
			'staffToken' => [
				'class'    => StaffTokenFixture::className(),
				'dataFile' => Yii::getAlias( '@common' ) . '/tests/_data/staffToken.php'
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
     * List Bank record
     * @param FunctionalTester $I
     */
    public function listBankByWithPagination(FunctionalTester $I)
    {
        $I->wantTo('get Bank listing');
        $I->sendGET('v1/banks');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['bank_id'=>1]);
    }

    /**
     * list all bank
     * @param FunctionalTester $I
     */
    public function listBankByWithoutPagination(FunctionalTester $I)
    {
        $I->wantTo('get Bank listing without pagination');
        $I->sendGET('v1/banks');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson(['bank_id'=>2]);
    }
}
