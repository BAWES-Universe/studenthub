<?php

namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use common\fixtures\TransferCandidateFixture;

class TransferCandidateTest extends \Codeception\Test\Unit {

    use Specify;

    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'transferCandidate' => TransferCandidateFixture::class
        ];
    }

    /**
     * test for fixture load
     */
    public function testTransferCandidateFixtures() {
        $this->assertNotNull(Transfer::findOne(['transfer_id' => 1]));
        $this->assertNotNull(TransferCandidate::find()->one());
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer id is invalid
     */
    public function testMarkUnpaidWithInvalidTransferCandidateID() {
        $response = TransferCandidate::markUnpaid(1000);
        $this->assertEquals('Candidate Transfer not found', $response['message']);
    }

    /**
     * todo: fix
     * test case for mark unpaid transfer candidate when
     * transfer candidate is zero total
     *
    public function testMarkUnpaidWhenZeroAmountTransferError() {
        $transferCandidate = TransferCandidate::findOne(33);
        $this->assertEquals(0, $transferCandidate->hours);
        $this->assertEquals(0, $transferCandidate->minutes);
        $this->assertEquals(0, $transferCandidate->seconds);
        $this->assertEquals(0, $transferCandidate->bonus);
        $this->assertEquals(0, $transferCandidate->candidate_total);
        $this->assertEquals(0, $transferCandidate->company_total);

        $this->assertEquals(TransferCandidate::PAID, $transferCandidate->paid);

        $response = TransferCandidate::markUnpaid(33);

        $this->assertEquals('error', $response['operation']);
        $this->assertEquals("Candidate Transfer can't be mark as unpaid. As total paid amount is equal to zero", $response['message']);
    }*/

    /**
     * test case for mark unpaid transfer candidate when
     * transfer is completed
     *
    public function testMarkUnpaidWhenTransferIsCompleted() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(25);
        $this->assertFalse(($transferCandidate->paid == TransferCandidate::UNPAID));

        $this->assertEquals(TransferCandidate::PAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_TRANSFER_COMPLETE, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);

        // modifying fixture data
        $response = TransferCandidate::markUnpaid(25);
        $this->assertEquals('Candidate Transfer marked as "unpaid" with transfer status changed to salary distribution in progress successfully', $response['message']);

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(25);
        $this->assertFalse(($transferCandidate->paid == TransferCandidate::PAID));
        $this->assertEquals(TransferCandidate::UNPAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);
        $this->assertFalse(Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE);
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer is not completed
     *
    public function testMarkUnpaidWhenTransferIsNotCompleted() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(24);

        $this->assertFalse(($transferCandidate->paid == TransferCandidate::UNPAID));
        $this->assertEquals(TransferCandidate::PAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);

        // modifying fixture data
        $response = TransferCandidate::markUnpaid(24);
        $this->assertEquals('Candidate Transfer marked as "unpaid" successfully', $response['message']);

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(24);
        $this->assertTrue(($transferCandidate->paid == TransferCandidate::UNPAID));
        $this->assertEquals(TransferCandidate::UNPAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);
        expect('main transfer is not completed', (Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();
    }*/

    /**
     * test case for mark paid transfer candidate when
     * transfer id is invalid
     */
    public function testMarkPaidWithInvalidTransferCandidateID() {
        $response = TransferCandidate::markUnpaid(1000);
        $this->assertEquals('Candidate Transfer not found', $response['message']);
    }

    /**
     * test case for mark unpaid transfer candidate when
     * salary distribution is in progress
     *
    public function testMarkPaidWhenSalaryDistributionInProgress() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(22);
        
        $this->assertTrue(($transferCandidate->paid == TransferCandidate::UNPAID));

        $this->assertEquals(TransferCandidate::UNPAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);

        // modifying fixture data
        $response = TransferCandidate::markPaid(22,"1122");
        $this->assertEquals('success', $response['operation']);

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(22);
        $this->assertTrue(($transferCandidate->paid == TransferCandidate::PAID));
        $this->assertEquals(TransferCandidate::PAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);
        $this->assertFalse(Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE);
    }

    /**
     * testing for case when main transfer is
     * remain 1 candidate to mark it completed
     *
    public function testMarkPaidWhenOneCandidateRemainToCompeteTransfer() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(34);

        expect('candidate transfer not non-paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->true();
        $this->assertEquals(TransferCandidate::UNPAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);

        $Transfer = Transfer::findOne(17);
        $this->assertEquals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, $Transfer->transfer_status);

        $count = TransferCandidate::find()->andWhere(['transfer_id' => 17, 'paid' => 0])->count();
        $this->assertEquals(1, $count);

        // modifying fixture data
        $response = TransferCandidate::markPaid(34,"1122");
        $this->assertEquals('success', $response['operation']);
       
        $count = TransferCandidate::find()->andWhere(['transfer_id' => 17, 'paid' => 0])->count();
        $this->assertEquals(0, $count);

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(34);
        $this->assertFalse(($transferCandidate->paid == TransferCandidate::UNPAID));
        $this->assertEquals(TransferCandidate::PAID, $transferCandidate->paid);
        $this->assertEquals(Transfer::STATUS_TRANSFER_COMPLETE, Transfer::findOne($transferCandidate->transfer_id)->transfer_status);
        $this->assertFalse(Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

        //check is transfer is marked as completed

        $Transfer = Transfer::findOne(17);
        $this->assertEquals(Transfer::STATUS_TRANSFER_COMPLETE, $Transfer->transfer_status);
    }*/

}
