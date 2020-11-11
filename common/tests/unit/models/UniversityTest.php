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
        return ['university' => UniversityFixture::className()];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff testing-staff is in the table',
                University::findOne(['university_name_en'=>'Gulf University for Science and Technology'])
            )->notNull();
        });


        $this->specify('University fields characters limits', function () {

            $university = new University;
            $university->university_name_en = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongas';
            expect('should not accept too long university_name_en', $university->validate(['university_name_en']))->false();
            $university->university_name_ar = 'toolooooongnaaaaaaameeeetoolooooongnaaaaaaameeeetoolooooongas';
            expect('should not accept too long university_name_ar', $university->validate(['university_name_ar']))->false();
        });
    }


    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New University', function () {
            $model = new University();
            $model->university_name_en = 'Punjab Technical University';
            $model->university_name_ar = 'PTU';
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['university_name_ar'=>'PTU']))->notNull();
        });

        $this->specify('Update university Data', function() {
            $model = University::findOne(['university_name_ar'=>'PTU']);
            $model->university_name_ar = 'Punjab TU';
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['university_name_ar'=>'Punjab TU']))->notNull();
        });
    }

    /**
     * Tests soft Delete
     */
    public function testSoftDelete()
    {
        $this->specify('University check record exist', function () {
            expect('Staff testing-staff is in the table',
                University::findOne(['university_name_en'=>'Kuwait University'])
            )->notNull();
        });

        $this->specify('University test soft delete', function () {
            $model = University::findOne(['university_name_en'=>'Kuwait University']);
            $model->deleted = '1';
            expect('updated successfully', $model->save())->true();
            expect('checking is soft delete Record updated in database', $model->findOne(['university_name_en'=>'Kuwait University']))->null();
        });
    }
}
