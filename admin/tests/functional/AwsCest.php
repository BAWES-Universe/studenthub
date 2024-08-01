<?php
namespace admin\tests;

use admin\tests\FunctionalTester;
use Codeception\Util\HttpCode;

class AwsCest
{
    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate aws api response for config');
        $I->sendGET('v1/aws/config');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}