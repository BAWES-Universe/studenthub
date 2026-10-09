<?php
namespace admin\tests;

use admin\tests\FunctionalTester;

class AwsCest
{
    const FAKE_KEY = 'RETIRED-CONFIG-KEY';
    const FAKE_SECRET = 'RETIRED-CONFIG-SECRET';

    /**
     * Retired browser credential route.
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('retired aws config returns 410 and no credentials');
        putenv('AWS_TEMP_BUCKET_KEY=' . self::FAKE_KEY);
        putenv('AWS_TEMP_BUCKET_SECRET=' . self::FAKE_SECRET);
        $_ENV['AWS_TEMP_BUCKET_KEY'] = self::FAKE_KEY;
        $_ENV['AWS_TEMP_BUCKET_SECRET'] = self::FAKE_SECRET;
        $_SERVER['AWS_TEMP_BUCKET_KEY'] = self::FAKE_KEY;
        $_SERVER['AWS_TEMP_BUCKET_SECRET'] = self::FAKE_SECRET;
        \Yii::$app->params['aws_temp_access_key_id'] = self::FAKE_KEY;
        \Yii::$app->params['aws_temp_secret_access_key'] = self::FAKE_SECRET;

        $I->sendGET('v1/aws/config');
        $I->seeResponseCodeIs(410);
        $I->seeHttpHeader('Cache-Control', 'no-store');
        $I->dontSeeResponseContains(self::FAKE_KEY);
        $I->dontSeeResponseContains(self::FAKE_SECRET);
        $I->dontSeeResponseContains('"key"');
        $I->dontSeeResponseContains('"secret"');
    }

    public function _after(FunctionalTester $I)
    {
        putenv('AWS_TEMP_BUCKET_KEY');
        putenv('AWS_TEMP_BUCKET_SECRET');
        unset($_ENV['AWS_TEMP_BUCKET_KEY'], $_ENV['AWS_TEMP_BUCKET_SECRET']);
        unset($_SERVER['AWS_TEMP_BUCKET_KEY'], $_SERVER['AWS_TEMP_BUCKET_SECRET']);
    }
}
