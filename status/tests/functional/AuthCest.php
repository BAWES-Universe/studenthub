<?php
namespace status\tests;

use status\tests\FunctionalTester;
use yii;
use status\models\Inspector;
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
            'statuss' => InspectorFixture::className(),
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
//    	$status = Inspector::find()->one();
//
//        $I->wantTo('Validate auth > login api');
//        $I->amHttpAuthenticated($status->status_email, '12345');
//        $I->sendGET('v1/auth/login');
//        $I->seeResponseCodeIs(HttpCode::OK); // 200
//        $I->seeResponseIsJson();
    }

    /**
     * Update password
     * @param FunctionalTester $I
     */
    public function tryToUpdatePassword(FunctionalTester $I)
    {
//        $status = Inspector::find()->one();
//
//        $status->generatePasswordResetToken();
//        $status->save(false);
//
//        $I->wantTo('Validate auth > update password api');
//        $I->sendPATCH('v1/auth/update-password', [
//            'token' => $status->status_password_reset_token,
//            'newPassword' => '12345'
//        ]);
//        $I->seeResponseCodeIs(HttpCode::OK); // 200
//        $I->seeResponseIsJson();
//        $I->seeResponseContainsJson([
//            'operation' => 'success'
//        ]);
    }

    /**
     * Try to reset password
     * @param FunctionalTester $I
     */
    public function tryToResetPassword(FunctionalTester $I) {
//        $status = Inspector::find()->one();
//
//        $I->wantTo('Validate auth > request-reset-password api');
//        $I->sendPOST('v1/auth/request-reset-password', [
//            'email' => $status->status_email
//        ]);
//        $I->seeResponseCodeIs(HttpCode::OK); // 200
//        $I->seeResponseContainsJson([
//            'operation' => 'success'
//        ]);
    }
}
