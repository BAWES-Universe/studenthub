<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use staff\fixtures\StaffTokenFixture;
use staff\fixtures\StaffFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staff.php'
            ],
            'staffToken' => [
                'class' => StaffTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/staffToken.php'
            ]
        ];
	}

	public function _before(FunctionalTester $I)
	{
		$this->token = StaffToken::find()
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated('staff@gmail.com', '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $this->token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
