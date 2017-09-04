<?php

use common\fixtures\CountryFixture;

class CountryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return ['country' => CountryFixture::className()];
    }

    protected function _before(){}

    protected function _after(){}

    public function testValidate()
    {
        $country = $this->tester->grabFixture('country', 0);

        expect('model adding new country', $country->save())->true();

        $country->country_name_en = null;
        expect('country_name_en Validating', $country->validate(['country_name_en']))->false();

        $country->country_nationality_name_en = null;
        expect('country_nationality_name_en Validating', $country->validate(['country_nationality_name_en']))->false();
    }
}
