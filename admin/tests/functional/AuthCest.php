<?php

namespace admin\tests;

use yii;
use admin\tests\FunctionalTester;
use Codeception\Util\HttpCode;


class AuthCest {

    public $token;

    public function _fixtures() {
        return [
        ];
    }

    public function _before(FunctionalTester $I) {
        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I) {
        
    }

    /**
     * Login
     * @param FunctionalTester $I
     */
    public function tryToLogin(FunctionalTester $I) {
        $admin = new \admin\models\Admin;
        $admin->admin_name = 'Test';
        $admin->admin_email = 'test@me.admin';
        $admin->admin_auth_key = '';
        $admin->admin_status = '10';
        $admin->setPassword('12345');
        $admin->save();
        
        $I->wantTo('Validate auth > login api');
        $I->amHttpAuthenticated($admin->admin_email, '12345');
        $I->sendGET('v1/auth/login');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
