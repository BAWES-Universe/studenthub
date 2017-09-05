<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use candidate\models\CandidateToken;
use common\fixtures\CandidateTokenFixture;
use Codeception\Util\HttpCode;

class AuthCest
{
    public $token;

	public function _fixtures()
	{
		return [
			'candidateToken' => CandidateTokenFixture::className()
		];
	}
	public function _before(FunctionalTester $I)
	{
        $this->token = CandidateToken::find()
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
        $I->amHttpAuthenticated('candidate1@bawes.net', '123456');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Update password
     * @param FunctionalTester $I
     */
    public function tryToTest(FunctionalTester $I)
    {
        $I->wantTo('Validate auth > update password api');
        $I->sendPATCH('v1/auth/update-password', [
            'token' => 'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
            'newPassword' => '12345'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
