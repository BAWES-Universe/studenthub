<?php
namespace inspector\tests;

use inspector\tests\FunctionalTester;
use yii;
use inspector\models\Inspector;
use common\models\InspectorToken;
use common\fixtures\InspectorTokenFixture;
use common\fixtures\InspectorFixture;
use Codeception\Util\HttpCode;


class AuthCest
{
    public $token;

    /**
     * @return array
     */
	public function _fixtures()
	{
        return [
            'inspectors' => InspectorFixture::className(),
            'token' => InspectorTokenFixture::className()
        ];
	}

    /**
     * @param \inspector\tests\FunctionalTester $I
     * @return void
     */
	public function _before(FunctionalTester $I)
	{
		$this->token = InspectorToken::find()
                ->one()
                ->token_value;

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * @param \inspector\tests\FunctionalTester $I
     * @return void
     */
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
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
        $inspector = Inspector::find()->one();

        $inspector->generatePasswordResetToken();
        $inspector->save(false);

        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => $inspector->inspector_password_reset_token,
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'operation' => 'success'
        ]);
    }

    /**
     * Try to reset password
     * @param FunctionalTester $I
     */
    public function tryToResetPassword(FunctionalTester $I) {
        $inspector = Inspector::find()->one();

        $I->wantTo('Validate auth > request-reset-password api');
        $I->sendPOST('v1/auth/request-reset-password', [
            'email' => $inspector->inspector_email
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'operation' => 'success'
        ]);
    }
}
