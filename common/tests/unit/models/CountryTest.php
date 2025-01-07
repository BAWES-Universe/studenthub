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
        return ['country' => CountryFixture::class];
    }

    protected function _before(){}

    protected function _after(){}

    public function testValidate()
    {
        $country = $this->tester->grabFixture('country', 0);

        $this->assertTrue($country->save());

        $country->country_name_en = null;
        $this->assertFalse($country->validate(['country_name_en']));

        $country->country_nationality_name_en = null;
        $this->assertFalse($country->validate(['country_nationality_name_en']));
    }
}
