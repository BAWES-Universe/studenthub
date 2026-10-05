<?php
namespace admin\tests;

use admin\tests\FunctionalTester;
use Codeception\Util\HttpCode;

class AwsCest
{
    /**
     * Verifies anonymous callers cannot fetch AWS config.
     * @param FunctionalTester $I
     */
    public function tryToListWithoutToken(FunctionalTester $I)
    {
        $I->wantTo('Validate aws config requires authentication');
        $I->sendGET('v1/aws/config');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
