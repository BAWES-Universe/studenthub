<?php

namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use common\fixtures\TransferCandidateFixture;

class TransferTest extends \Codeception\Test\Unit {

    use Specify;

    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'transferCandidate' => TransferCandidateFixture::className(),
        ];
    }

    /**
     * test fixture loaded
     */
    public function testFixtureLoad() {
        expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
        expect('Transfer is in the table', TransferCandidate::find()->one())->notNull();
    }

    /**
     * test case for multiple payment status like Lock | Unlock | Payment received
     */

    /**
     * Test payment status when
     * Transfer is sent
     */
    public function testMarkPaymentStatusReceivedWhenTransferIsSent() {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
        expect('Mark as "Payment Received" from "Payment Sent" Status', $transfer->paymentReceived())->true();

        try {
            $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS]);
            $result = $transfer2->paymentReceived();
        } catch (yii\base\Exception $ex) {
            $result = false;
        }
        expect('Mark as "Payment Received" from "Payment Received" Status', $result)->false();
    }

    /**
     * Transfer model mark as Initiated from Lock
     */
    public function testMarkPaymentStatusUnlockWhenTransferStatusIsLocked() {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
        expect('Unlock Transfer from Lock Status', $transfer->unlock())->true();

        try {
            $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
            $result = $transfer2->unlock();
        } catch (yii\base\Exception $ex) {
            $result = false;
        }
        expect('Unlock Transfer from Unlock/Initiated Status', $result)->false();
    }

    /**
     * Transfer model mark as Lock from Payment Sent
     */
    public function testMarkPaymentStatusLockedWhenTransferStatusIsSent() {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
        expect('Mark as lock from payment sent status', $transfer->lock())->true();

        try {
            $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
            $result = $transfer2->lock();
        } catch (yii\base\Exception $ex) {
            $result = false;
        }
        expect('Mark as lock from lock status', $result)->false();
    }

    /**
     * Transfer model statistics for company without child
     */
    public function testStatisticsForTransferCostWhenCompanyWithoutChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 3])
                ->one();

        $transfer_cost = TransferCandidate::find()
                ->where(['transfer_id' => $transfer->transfer_id])
                ->sum('transfer_cost');

        expect('Check transfer cost getting calculated properly', $transfer_cost)
                ->equals(floatval(Transfer::getTransferCost($transfer->transfer_id)));
    }

    /**
     * test to check total paid transfer
     * when company without child
     */
    public function testStatisticsForTotalPaidTransferWhenCompanyWithoutChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 3])
                ->one();

        $totalPaid = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id,
                    'paid' => 1
                ])
                ->count();

        expect('Checking total no of candidate paid in transfer', $totalPaid)
                ->equals($transfer->getTotalPaid());
    }

    /**
     * test to check calculation of total unpaid
     * transfer when company without child
     */
    public function testStatisticsForTotalUnpaidTransferCandidateWhenCompanyWithoutChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 3])
                ->one();

        $totalUnpaid = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id,
                    'paid' => 0
                ])
                ->count();

        expect('Checking total no of candidate unpaid in transfer', $totalUnpaid)
                ->equals($transfer->getTotalUnpaid());
    }

    /**
     * test to check calculation of total profit of
     * transfer when company without child
     */
    public function testStatisticsForTransferProfitWhenCompanyWithoutChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 3])
                ->one();

        $profit = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id
                ])
                ->sum('((company_hourly_rate - candidate_hourly_rate ) * hours) - transfer_cost + bonus_commission');

        expect('Checking profit from transfer getting calculated properly', $profit)
                ->equals($transfer->getProfit());
    }

    /**
     * test to check calculation of total cost of
     * transfer when company with child
     */
    public function testStatisticsForTransferCostWhenCompanyWithChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 1])
                ->one();

        $transfer_cost = TransferCandidate::find()
                ->where(['transfer_id' => $transfer->transfer_id])
                ->sum('transfer_cost');

        expect('Check transfer cost getting calculated properly', $transfer_cost)
                ->equals(floatval(Transfer::getTransferCost($transfer->transfer_id)));
    }

    /**
     * test to check calculation of total paid candidate of
     * transfer when company with child
     */
    public function testStatisticsForPaidTransferCandidateWhenCompanyWithChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 1])
                ->one();

        $totalPaid = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id,
                    'paid' => 1
                ])
                ->count();

        expect('Checking total no of candidate paid in transfer', $totalPaid)
                ->equals($transfer->getTotalPaid());
    }

    /**
     * test to check calculation of total unpaid candidate of
     * transfer when company with child
     */
    public function testStatisticsForUnPaidTransferCandidateWhenCompanyWithChild() {
        $transfer = Transfer::find()
                ->where(['company_id' => 1])
                ->one();

        $totalUnpaid = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id,
                    'paid' => 0
                ])
                ->count();

        expect('Checking total no of candidate unpaid in transfer', $totalUnpaid)
                ->equals($transfer->getTotalUnpaid());
    }

    /**
     * test to check calculation of total Profit of
     * transfer when company with child
     */
    public function testStatisticsForTransferProfitWhenCompanyWithChild() {
        
        $transfer = Transfer::find()
                ->where(['company_id' => 1])
                ->one();
        
        $profit = TransferCandidate::find()
                ->where([
                    'transfer_id' => $transfer->transfer_id
                ])
                ->sum('((company_hourly_rate - candidate_hourly_rate ) * hours) - transfer_cost + bonus_commission');

        expect('Checking profit from transfer getting calculated properly', $profit)
                ->equals($transfer->getProfit());
    }

}
