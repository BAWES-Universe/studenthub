<?php
namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Store;
use admin\models\Company;
use admin\models\Candidate;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use admin\fixtures\Company as CompanyFixture;
use admin\fixtures\Store as StoreFixture;
use admin\fixtures\Candidate as CandidateFixture;
use admin\fixtures\Transfer as TransferFixture;
use admin\fixtures\TransferCandidate as TransferCandidateFixture;
use admin\fixtures\Invoice as InvoiceFixture;

class StatisticsTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/store.php'
            ],
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'
            ],
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transfer.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transferCandidate.php'
            ],
            'invoice' => [
                'class' => InvoiceFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/invoice.php'
            ],
        ]);
    }

    protected function _after(){}

    /**
     * test admin statistics
     */
    public function testStatistics()
    {
        $this->specify('Fixtures should be loaded', function () {
            expect('Company is in the table', Company::findOne(['company_id' => 1]))->notNull();
            expect('Store is in the table', Store::find()->one())->notNull();
            expect('Candidate is in the table', Candidate::find()->one())->notNull();
            expect('Transfer is in the table', Transfer::find()->one())->notNull();
            expect('Transfer Candidate is in the table', TransferCandidate::find()->one())->notNull();
        });
        
        $this->specify('Candidate statistics', function () {
            $payableDetail = Candidate::getTotalPayableCandidate();

            $totalPayableCandidate = TransferCandidate::find()
                ->joinWith('transfer')
                ->where([
                    'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    'paid' => 0,
                ])
                ->count();

            expect('Total payable candidate', $totalPayableCandidate)->equals($payableDetail['payable']);
        });

        $this->specify('Candidate statistics', function () {
            $payableDetail = Candidate::getTotalPayableCandidate();
            $totalPayable = TransferCandidate::find()
                ->joinWith('transfer')
                ->where([
                   'transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                   'paid' => 0,
                ])
               ->sum('(candidate_hourly_rate * hours) + bonus');
            
            expect('Total payable amount to candidate', $totalPayable)->equals($payableDetail['amount']);
            
            $totalCandidate = Candidate::find()
                    ->where(['deleted' => 0])
                    ->count();
            
            expect('Total candidates', $totalCandidate)->equals(Candidate::candidateCountByCondition());
            
            $totalAssignedToWork = Candidate::find()
                ->joinWith('store')
                ->where([
                    '{{%candidate}}.deleted' => 0
                ])
                ->count();
            
            expect('Total assigned candidates', $totalAssignedToWork)->equals(Candidate::candidateCountByCondition());
            
            $approved = Candidate::find()
                ->where([
                    'approved' => 1,
                    'deleted' => 0
                ])
                ->count();
            
            expect('Total approved candidates', $approved)->equals(Candidate::candidateCountByCondition('approved'));
        });
        
        $this->specify('Transfer statistics', function () {            
            $lockedTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_LOCK);
            $paymentSentTransfers = Transfer::getTransferStatusRecordDetail(Transfer::STATUS_PAYMENT_SENT);
        
            $locked = Transfer::find()
                ->where([
                    'deleted' => 0,
                    'transfer_status' => Transfer::STATUS_LOCK
                ])
                ->count();
            
            expect('Total locked transfer', $locked)
                    ->equals((isset($lockedTransfers['total']))? (int)$lockedTransfers['total'] : 0);
            
            $paymentSent = Transfer::find()
                ->where([
                    'deleted' => 0,
                    'transfer_status' => Transfer::STATUS_PAYMENT_SENT
                ])
                ->count();
            
            expect('Total "Payment Sent" transfer', $paymentSent)
                    ->equals((isset($paymentSentTransfers['total']))? (int)$paymentSentTransfers['total'] : 0);            
        });
    }
}