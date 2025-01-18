<?php
namespace common\tests;

use common\models\University;
use common\fixtures\UniversityFixture;
use Codeception\Specify;

class UniversityTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return ['university' => UniversityFixture::class];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Tests validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(University::findOne(['university_name_en'=>'Gulf University for Science and Technology']));
        //});


        //$this->specify('University fields characters limits', function () {

            $university = new University;
            $university->university_name_en = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongas';
            $this->assertFalse($university->validate(['university_name_en']));
            $university->university_name_ar = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongas';
            $this->assertFalse($university->validate(['university_name_ar']));
        //});
    }


    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        //$this->specify('Create New University', function () {
            $model = new University();
            $model->university_name_en = 'Punjab Technical University';
            $model->university_name_ar = 'PTU';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['university_name_ar'=>'PTU']));
        //});

        //$this->specify('Update university Data', function() {
            $model = University::findOne(['university_name_ar'=>'PTU']);
            $model->university_name_ar = 'Punjab TU';
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['university_name_ar'=>'Punjab TU']));
        //});
    }

    /**
     * Tests soft Delete
     */
    public function testSoftDelete()
    {
        //$this->specify('University check record exist', function () {
            $this->assertNotNull(University::findOne(['university_name_en'=>'Kuwait University']));
        //});

        //$this->specify('University test soft delete', function () {
            $model = University::findOne(['university_name_en'=>'Kuwait University']);
            $model->deleted = '1';
            $this->assertTrue($model->save());
            $this->assertNull(University::findOne(['university_name_en'=>'Kuwait University']));
        //});
    }
}
