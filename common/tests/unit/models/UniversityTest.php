<?php
namespace common\tests;

use common\models\University;

class UniversityTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testSomeFeature()
    {
        $university = new University;
        
        $university->university_name_en = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        expect('should not accept too long university_name_en', $university->validate(['university_name_en']))->false();

        $university->university_name_ar = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongnaaaaaaameeee';
        expect('should not accept too long university_name_ar', $university->validate(['university_name_ar']))->false();
    }
}