<?php
namespace common\tests;

use common\models\CandidateIdCard;
use common\fixtures\CandidateIdCardFixture;
use Codeception\Specify;

class CandidateIdCardTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidateIdCard' => CandidateIdCardFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after(){}

    // tests
    public function testValidations()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('CandidateIdCard 1 is in the table',
                CandidateIdCard::findOne(['candidate_id'=>1])
            )->notNull();
        });

        $this->specify('CandidateIdCard model required field validation', function() {
            $id = new CandidateIdCard;
            expect('Candidate ID should be required field', $id->validate(['candidate_id']))->false();
            expect('Expiry date should be required field', $id->validate(['expiry_date']))->false();
        });

        $this->specify('CandidateIdCard model should not accept random candidate id', function() {
            $id = new CandidateIdCard;
            $id->candidate_id = 9999999999999999999999;
            expect('Invalid canidate id passed', $id->validate(['candidate_id']))->false();
        });
    }

    /**
     * Tests Create, Update and Delete for the staff model
     */
    public function testCrud()
    {
        $this->specify('Create New Candidate Id Card', function () {
            $model = new CandidateIdCard();
            $model->candidate_id = 2;
            $model->expiry_date = date('Y-m-d', strtotime('+1 month'));
            expect('Created successfully', $model->save())->notNull();
            expect('Record is in database', $model->findOne(['candidate_id' => 2]))->notNull();
        });

        $this->specify('Update Candidate Id Card Data', function() {
            $model = CandidateIdCard::findOne(['candidate_id' => 2]);
            $model->expiry_date = date('Y-m-d', strtotime('+2 month'));
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['candidate_id' => 2]))->notNull();
        });

        $this->specify('Delete Candidate Id Card', function() {
            $model = CandidateIdCard::findOne(['candidate_id' => 2]);
            expect('Deletes record', $model->delete())->equals(1);
            expect('Record no longer exists', $model->findOne(['candidate_id' => 2]))->null();
        });
    }
}
