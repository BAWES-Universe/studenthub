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
use yii\db\Expression;

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
			'transferCandidate' => TransferCandidateFixture::class,
			'invoice' => InvoiceFixture::class
		];
	}

    /**
     * test admin statistics
     */
    public function testStatisticsFixtureLoaded()
    {
        $this->assertNotNull(Company::findOne(['company_id' => 1]));
        $this->assertNotNull(Store::find()->one());
        $this->assertNotNull(Candidate::find()->one());
        $this->assertNotNull(Transfer::find()->one());
        $this->assertNotNull(TransferCandidate::find()->one());
    }

    /**
     * test payable candidate count
     */
    public function testStatisticsPayableCandidate()
    {
        $payableDetail = Candidate::getTotalPayableCandidate();

        $totalPayableCandidate = TransferCandidate::find()
            ->joinWith('transfer')
            ->andWhere([
                'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
            ])/*
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->andWhere([
                'paid' => 0,
            ])
            ->count();

        $this->assertEquals($totalPayableCandidate, $payableDetail['payable']);
    }

    /**
     * test payable candidate amount
     */
    public function testStatisticsPayableCandidateAmount()
    {
        $payableDetail = Candidate::getTotalPayableCandidate();
        $totalPayable = TransferCandidate::find()
            ->joinWith('transfer')
            ->andWhere([
                'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
            ])/*
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->andWhere([
                'paid' => 0,
            ])
            ->sum('candidate_total');//(candidate_hourly_rate * hours) + bonus - bonus_commission

        $this->assertEquals($totalPayable, $payableDetail['amount']);
    }

    /**
     * test total candidate
     */
    public function testStatisticsTotalCandidate()
    {
        $totalCandidate = Candidate::find()
            ->andWhere(['candidate.deleted' => 0])
            ->count();

        $this->assertEquals($totalCandidate, Candidate::candidateCountByCondition());
    }

    /**
     * test total Assigned Candidates
     *
    public function testStatisticsTotalAssignedCandidate()
    {
        $totalAssignedToWork = Candidate::find()
            ->joinWith('store')
            ->andWhere([
                '{{%candidate}}.deleted' => 0,
                "{{%candidate}}.currency_code" => "KWD"
            ])
            ->andWhere(new Expression("store.store_id IS NOT null"))
            ->count();

        $this->assertEquals($totalAssignedToWork, Candidate::candidateCountByCondition('assigned'));
    }
*/
    /**
     * test total Approved Candidates
     */
    public function testStatisticsTotalApprovedCandidate()
    {
        $approved = Candidate::find()
            ->andWhere([
                'approved' => 1,
                'deleted' => 0
            ])
            ->count();

        $this->assertEquals($approved, Candidate::candidateCountByCondition('approved'));
    }

    /**
     * test total Locked Transfer
     *
    public function testStatisticsTotalLockedTransfer()
    {
        $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK);

        $locked = Transfer::find()
            ->andWhere([
                'deleted' => 0,
                'transfer_status' => Transfer::STATUS_LOCK
            ])
            ->count();

        $this->assertEquals($locked, (isset($lockedTransfers['total'])) ? (int)$lockedTransfers['total'] : 0);
    }*/

    /**
     * test total sent Transfer
     */
    public function testStatisticsTotalSentTransfer()
    {
        $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT);

        $paymentSent = Transfer::find()
            ->andWhere([
                'deleted' => 0,
                'transfer_status' => Transfer::STATUS_PAYMENT_SENT
            ])
            ->count();

        $this->assertEquals($paymentSent, (isset($paymentSentTransfers['total']))? (int)$paymentSentTransfers['total'] : 0);
    }
}
