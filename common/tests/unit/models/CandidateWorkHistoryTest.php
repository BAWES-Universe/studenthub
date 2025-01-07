<?php
namespace common\tests;

use common\fixtures\StaffTokenFixture;
use common\models\CandidateWorkHistory;
use common\models\Staff;
use common\models\StaffToken;
use Yii;
use common\models\Store;
use common\models\Candidate;
use common\fixtures\CandidateFixture;
use common\fixtures\CandidateWorkHistoryFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\StoreFixture;
use Codeception\Specify;


class CandidateWorkHistoryTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidates' => CandidateFixture::class,
            'country' => CountryFixture::class,
            'university' => UniversityFixture::class,
            'store' => StoreFixture::class,
            'candidateWorkHistory' => CandidateWorkHistoryFixture::class,
            'staffToken' => StaffTokenFixture::class,
        ];
    }

    protected function _before(){
//        $staff = Staff::find()->one();
//        Yii::$app->user->login($staff);
    }

    protected function _after(){}

    /**
     * test table validation
     */
    public function testValidations()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(CandidateWorkHistory::findOne(['candidate_id' => 1]));
        //});

        //$this->specify('Candidate model required field validation', function() {
            $model = new CandidateWorkHistory();
            $model->candidate_id = 'candidate_id';
            $model->store_id = 'store_id';
            $model->company_id = null;
            $model->validate();
            $this->assertEquals(isset($model->errors['candidate_id']),true);
            $this->assertEquals(isset($model->errors['store_id']),true);
            $this->assertEquals(isset($model->errors['company_id']),true);
            $this->assertEquals($model->errors['store_id']['0'],'Store ID must be an integer.');
        //});

        //$this->specify('fail in case invalid candidate or store id store', function() {
            $model = new CandidateWorkHistory();
            $model->candidate_id = 1011;
            $model->store_id = 1111;
            $model->validate();
            $this->assertEquals(isset($model->errors['candidate_id']),true);
            $this->assertEquals($model->errors['candidate_id']['0'],'Candidate ID is invalid.');
            $this->assertEquals(isset($model->errors['store_id']),true);
            $this->assertEquals($model->errors['store_id']['0'],'Store ID is invalid.');
        //});
    }

    /**
     * test case to test SaveAssignedHistory
     */
    public function testSaveAssignedHistory()
    {
        /*//$this->specify('assigned data not available', function () {
            $this->assertNull(CandidateWorkHistory::findOne(['candidate_id' => 3]));
            $this->assertNotNull(Candidate::findOne(3));
        //});*/

//        //$this->specify('saving assigned Data test', function () {
//            $candidate = Candidate::findOne(3);
//            expect_that(CandidateWorkHistory::saveAssignedHistory($candidate));
//            expect_that(CandidateWorkHistory::findOne(['candidate_id' => 3]));
//        //});
    }

    /**
     * test case to test SaveUnAssignedHistory
     */
    public function testSaveUnAssignedHistory(){


//        //$this->specify('no record found testing', function() {
//            $candidate = Candidate::findOne(4);
//            $response = CandidateWorkHistory::saveUnAssignedHistory($candidate);
//            expect($response)->hasKey('operation');
//            //expect($response['message'])->equals('no record found');
//        //});
//
//        //$this->specify('testing unassigned method data for deletion of today assigned work', function() {
//            $candidate = Candidate::findOne(3);
//            expect('expect to save assigned data',CandidateWorkHistory::saveAssignedHistory($candidate))->notEmpty();
//            expect('expect to find assigned data',CandidateWorkHistory::findOne(['candidate_id'=>3]))->notEmpty();
//            expect('expect to update today assigned data',CandidateWorkHistory::saveUnAssignedHistory($candidate))->notEmpty();
//            //expect('expect to save assigned data',CandidateWorkHistory::findOne(['candidate_id'=>3]))->isEmpty();
//        //});
//
//        //$this->specify('testing unassigned method for old user work history data to make that end', function() {
//            $candidate = Candidate::findOne(2);
//            expect('expect to update assigned data',CandidateWorkHistory::saveUnAssignedHistory($candidate))->notEmpty();
//            expect('expect to find updated data in history',CandidateWorkHistory::findOne(['candidate_id'=>2]))->notEmpty();
////            expect('expect to find today date in work history with same date',CandidateWorkHistory::findOne(['candidate_id'=>2])->end_date)->equals(date('Y-m-d'));
//        //});
    }

    /**
     * test case to test checkTotalHistory
     */
    public function testCheckTotalHistory(){

//        //$this->specify('test existing and not existing data', function() {
//            expect_not(CandidateWorkHistory::checkTotalHistory(Candidate::findOne(2)));
//            expect_that(CandidateWorkHistory::saveAssignedHistory(Candidate::findOne(3)));
//            expect_that(CandidateWorkHistory::checkTotalHistory(Candidate::findOne(3)));
//        //});
    }
}
