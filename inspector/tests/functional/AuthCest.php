<?php
namespace inspector\tests;

use yii;
use inspector\models\Inspector;
use common\models\InspectorToken;
use common\fixtures\InspectorTokenFixture;
use common\fixtures\InspectorFixture;
use Codeception\Util\HttpCode;


class AuthCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'inspectors' => InspectorFixture::className(),
            'token' => InspectorTokenFixture::className()
        ];
	}

	public function _before(FunctionalTester $I)
	{
		$this->token = InspectorToken::find()
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
    	$inspector = Inspector::find()->one();

        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($inspector->inspector_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update password
     * @param FunctionalTester $I
     *
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $this->token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }*/
}
