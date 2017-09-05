<?php
namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Store;
use admin\models\Company;
use admin\models\Candidate;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;

class StatisticsTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

	public function _fixtures()
	{
		return [
			'transferCandidate' => TransferCandidateFixture::className(),
			'invoice' => InvoiceFixture::className()
		];
	}

    /**
     * test admin statistics
     */
    public function testStatisticsFixtureLoaded()
    {
        expect('Company is in the table', Company::findOne(['company_id' => 1]))->notNull();
        expect('Store is in the table', Store::find()->one())->notNull();
        expect('Candidate is in the table', Candidate::find()->one())->notNull();
        expect('Transfer is in the table', Transfer::find()->one())->notNull();
        expect('Transfer Candidate is in the table', TransferCandidate::find()->one())->notNull();
    }

    /**
     * test payable candidate count
     */
    public function testStatisticsPayableCandidate()
    {
        $payableDetail = Candidate::getTotalPayableCandidate();

        $totalPayableCandidate = TransferCandidate::find()
            ->joinWith('transfer')
            ->where([
                'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                'paid' => 0,
            ])
            ->count();

        expect('Total payable candidate', $totalPayableCandidate)->equals($payableDetail['payable']);
    }

    /**
     * test payable candidate amount
     */
    public function testStatisticsPayableCandidateAmount()
    {
        $payableDetail = Candidate::getTotalPayableCandidate();
        $totalPayable = TransferCandidate::find()
            ->joinWith('transfer')
            ->where([
                'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                'paid' => 0,
            ])
            ->sum('(candidate_hourly_rate * hours) + bonus');

        expect('Total payable amount to candidate', $totalPayable)->equals($payableDetail['amount']);
    }

    /**
     * test total candidate
     */
    public function testStatisticsTotalCandidate()
    {
        $totalCandidate = Candidate::find()
            ->where(['deleted' => 0])
            ->count();

        expect('Total candidates', $totalCandidate)->equals(Candidate::candidateCountByCondition());
    }

    /**
     * test total Assigned Candidates
     */
    public function testStatisticsTotalAssignedCandidate()
    {
        $totalAssignedToWork = Candidate::find()
            ->joinWith('store')
            ->where([
                '{{%candidate}}.deleted' => 0
            ])
            ->count();

        expect('Total assigned candidates', $totalAssignedToWork)->equals(Candidate::candidateCountByCondition('assigned'));
    }

    /**
     * test total Approved Candidates
     */
    public function testStatisticsTotalApprovedCandidate()
    {
        $approved = Candidate::find()
            ->where([
                'approved' => 1,
                'deleted' => 0
            ])
            ->count();

        expect('Total approved candidates', $approved)->equals(Candidate::candidateCountByCondition('approved'));
    }

    /**
     * test total Locked Transfer
     */
    public function testStatisticsTotalLockedTransfer()
    {
        $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK);

        $locked = Transfer::find()
            ->where([
                'deleted' => 0,
                'transfer_status' => Transfer::STATUS_LOCK
            ])
            ->count();

        expect('Total locked transfer', $locked)
            ->equals((isset($lockedTransfers['total'])) ? (int)$lockedTransfers['total'] : 0);
    }

    /**
     * test total sent Transfer
     */
    public function testStatisticsTotalSentTransfer()
    {
        $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT);
        $paymentSent = Transfer::find()
            ->where([
                'deleted' => 0,
                'transfer_status' => Transfer::STATUS_PAYMENT_SENT
            ])
            ->count();

        expect('Total "Payment Sent" transfer', $paymentSent)
                ->equals((isset($paymentSentTransfers['total']))? (int)$paymentSentTransfers['total'] : 0);
    }
}
