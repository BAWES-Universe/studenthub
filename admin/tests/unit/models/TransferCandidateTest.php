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
            'transferCandidate' => TransferCandidateFixture::className()
        ];
    }

    /**
     * test for fixture load
     */
    public function testTransferCandidateFixtures() {
        expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
        expect('Transfer is in the table', TransferCandidate::find()->one())->notNull();
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer id is invalid
     */
    public function testMarkUnpaidWithInvalidTransferCandidateID() {
        $response = TransferCandidate::markUnpaid(1000);
        expect('Invalid id error', $response['message'])->equals('Candidate Transfer not found');
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer candidate is zero total
     */
    public function testMarkUnpaidWhenZeroAmountTransferError() {
        $transferCandidate = TransferCandidate::findOne(33);
        expect('zero hours', $transferCandidate->hours)->equals(0);
        expect('status paid ', $transferCandidate->paid)->equals(TransferCandidate::PAID);

        $response = TransferCandidate::markUnpaid(33);

        expect('error response', $response['operation'])->equals('error');
        expect('error response message', $response['message'])->equals("Candidate Transfer can't be mark as unpaid. As total paid amount is equal to zero");
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer is completed
     */
    public function testMarkUnpaidWhenTransferIsCompleted() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(25);
        expect('candidate transfer not non-paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->false();

        expect('candidate transfer is paid', $transferCandidate->paid)->equals(TransferCandidate::PAID);
        expect('main transfer is completed', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_TRANSFER_COMPLETE);

        // modifying fixture data
        $response = TransferCandidate::markUnpaid(25);
        expect('unpaid candidate transfer for completed transfer', $response['message'])->equals('Candidate Transfer marked as "unpaid" with transfer status changed to salary distribution in progress successfully');

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(25);
        expect('candidate transfer not paid', ($transferCandidate->paid == TransferCandidate::PAID))->false();
        expect('candidate transfer is paid', $transferCandidate->paid)->equals(TransferCandidate::UNPAID);
        expect('main transfer is in progress', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
        expect('main transfer is not completed', (Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();
    }

    /**
     * test case for mark unpaid transfer candidate when
     * transfer is not completed
     */
    public function testMarkUnpaidWhenTransferIsNotCompleted() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(24);

        expect('candidate transfer not non-paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->false();
        expect('candidate transfer is paid', $transferCandidate->paid)->equals(TransferCandidate::PAID);
        expect('main transfer is completed', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

        // modifying fixture data
        $response = TransferCandidate::markUnpaid(24);
        expect('unpaid candidate transfer for completed transfer', $response['message'])->equals('Candidate Transfer marked as "unpaid" successfully');

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(24);
        expect('candidate transfer not paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->true();
        expect('candidate transfer is unpaid', $transferCandidate->paid)->equals(TransferCandidate::UNPAID);
        expect('main transfer is still in progress', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
        expect('main transfer is not completed', (Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();
    }

    /**
     * test case for mark paid transfer candidate when
     * transfer id is invalid
     */
    public function testMarkPaidWithInvalidTransferCandidateID() {
        $response = TransferCandidate::markUnpaid(1000);
        expect('Invalid id error', $response['message'])->equals('Candidate Transfer not found');
    }

    /**
     * test case for mark unpaid transfer candidate when
     * salary distribution is in progress
     */
    public function testMarkPaidWhenSalaryDistributionInProgress() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(22);
        
        expect('candidate transfer not non-paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->true();

        expect('candidate transfer is unpaid', $transferCandidate->paid)->equals(TransferCandidate::UNPAID);
        expect('main transfer is in salary distribution process', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

        // modifying fixture data
        $response = TransferCandidate::markPaid(22,"1122");
        expect('paid candidate transfer', $response['operation'])->equals('success');

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(22);
        expect('candidate transfer not no-paid', ($transferCandidate->paid == TransferCandidate::PAID))->true();
        expect('candidate transfer is paid', $transferCandidate->paid)->equals(TransferCandidate::PAID);
        expect('main transfer is in progress', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
        expect('main transfer is not completed', (Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();
    }

    /**
     * testing for case when main transfer is
     * remain 1 candidate to mark it completed
     */
    public function testMarkPaidWhenOneCandidateRemainToCompeteTransfer() {
        // checking with existing without modifying fixture data
        $transferCandidate = TransferCandidate::findOne(34);

        expect('candidate transfer not non-paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->true();
        expect('candidate transfer is non paid', $transferCandidate->paid)->equals(TransferCandidate::UNPAID);
        expect('main transfer is in progress', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

        $Transfer = Transfer::findOne(17);
        expect('Transfer to be in progress', ($Transfer->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS))->true();

        $count = TransferCandidate::find()->where(['transfer_id' => 17, 'paid' => 0])->count();
        expect('one unpaid candidate', $count)->equals(1);

        // modifying fixture data
        $response = TransferCandidate::markPaid(34,"1122");
        expect('paid candidate transfer', $response['operation'])->equals('success');
       
        $count = TransferCandidate::find()->where(['transfer_id' => 17, 'paid' => 0])->count();
        expect('all candidate paid', $count)->equals(0);

        // checking after modifying fixture data
        $transferCandidate = TransferCandidate::findOne(34);
        expect('candidate transfer paid', ($transferCandidate->paid == TransferCandidate::UNPAID))->false();
        expect('candidate transfer is paid', $transferCandidate->paid)->equals(TransferCandidate::PAID);
        expect('main transfer is completed', Transfer::findOne($transferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_TRANSFER_COMPLETE);
        expect('main transfer is not in progress anymore', (Transfer::findOne($transferCandidate->transfer_id)->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS))->false();

        //check is transfer is marked as completed

        $Transfer = Transfer::findOne(17);
        expect('Transfer to be completed', ($Transfer->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->true();
    }

}
