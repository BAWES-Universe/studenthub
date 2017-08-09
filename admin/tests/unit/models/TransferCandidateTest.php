<?php
namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use common\fixtures\Transfer as TransferFixture;
use common\fixtures\TransferCandidate as TransferCandidateFixture;

class TransferCandidateTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'transfer' => [
                'class' => TransferFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transfer.php'
            ],
            'transferCandidate' => [
                'class' => TransferCandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/transferCandidate.php'
            ]
        ]);
    }

    protected function _after(){}


    /**
     * test case to test mark unpaid candidate transfer
     */
    public function testMarkUnpaid()
    {
        $this->specify('Fixtures should be loaded', function () {
            expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
            expect('Transfer is in the table', TransferCandidate::find()->one())->notNull();
        });

        $this->specify('testing for invalid id', function () {
            $response =  TransferCandidate::markUnpaid(1000);
            expect('Invalid id error', $response['message'])->equals('Candidate Transfer not found');
        });

        $this->specify('testing for case when main transfer is completed', function () {
            // checking with existing without modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(25);
            expect('candidate transfer not non-paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->false();

            expect('candidate transfer is paid',$TransferCandidate->paid)->equals(TransferCandidate::PAID);
            expect('main transfer is completed',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_TRANSFER_COMPLETE);

            // modifying fixture data
            $response =  TransferCandidate::markUnpaid(25);
            expect('unpaid candidate transfer for completed transfer', $response['message'])->equals('Candidate Transfer marked as "unpaid" with transfer status changed to salary distribution in progress successfully');

            // checking after modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(25);
            expect('candidate transfer not paid',($TransferCandidate->paid == TransferCandidate::PAID))->false();
            expect('candidate transfer is paid',$TransferCandidate->paid)->equals(TransferCandidate::UNPAID);
            expect('main transfer is in progress',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
            expect('main transfer is not completed',(Transfer::findOne($TransferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();

        });

        $this->specify('testing for case when main transfer not completed', function () {

            // checking with existing without modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(24);

            expect('candidate transfer not non-paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->false();
            expect('candidate transfer is paid',$TransferCandidate->paid)->equals(TransferCandidate::PAID);
            expect('main transfer is completed',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

            // modifying fixture data
            $response =  TransferCandidate::markUnpaid(24);
            expect('unpaid candidate transfer for completed transfer', $response['message'])->equals('Candidate Transfer marked as "unpaid" successfully');

            // checking after modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(24);
            expect('candidate transfer not paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->true();
            expect('candidate transfer is unpaid',$TransferCandidate->paid)->equals(TransferCandidate::UNPAID);
            expect('main transfer is still in progress',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
            expect('main transfer is not completed',(Transfer::findOne($TransferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();

        });
    }

    /**
     * test case to test mark paid candidate transfer
     */
    public function testMarkPaid()
    {
        $this->specify('Fixtures should be loaded', function () {
            expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
            expect('Transfer is in the table', TransferCandidate::find()->one())->notNull();
        });

        $this->specify('testing for invalid id', function () {
            $response =  TransferCandidate::markUnpaid(1000);
            expect('Invalid id error', $response['message'])->equals('Candidate Transfer not found');
        });

        $this->specify('testing for case when main transfer is progress', function () {
            // checking with existing without modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(22);
            expect('candidate transfer not non-paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->true();

            expect('candidate transfer is unpaid',$TransferCandidate->paid)->equals(TransferCandidate::UNPAID);
            expect('main transfer is in salary distribution process',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

            // modifying fixture data
            $response =  TransferCandidate::markPaid(22);
            expect('paid candidate transfer', $response['message'])->equals('Candidate Transfer marked as "paid" successfully');

            // checking after modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(22);
            expect('candidate transfer not no-paid',($TransferCandidate->paid == TransferCandidate::PAID))->true();
            expect('candidate transfer is paid',$TransferCandidate->paid)->equals(TransferCandidate::PAID);
            expect('main transfer is in progress',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);
            expect('main transfer is not completed',(Transfer::findOne($TransferCandidate->transfer_id)->transfer_status == Transfer::STATUS_TRANSFER_COMPLETE))->false();

        });

        $this->specify('testing for case when main transfer is remain 1 candidate to mark it completed', function () {

            // checking with existing without modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(34);

            expect('candidate transfer not non-paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->true();
            expect('candidate transfer is non paid',$TransferCandidate->paid)->equals(TransferCandidate::UNPAID);
            expect('main transfer is in progress',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS);

            // modifying fixture data
            $response =  TransferCandidate::markPaid(34);
            expect('paid candidate transfer', $response['message'])->equals('Candidate Transfer marked as "paid" with transfer status changed to completed successfully');

            // checking after modifying fixture data
            $TransferCandidate = TransferCandidate::findOne(34);
            expect('candidate transfer paid',($TransferCandidate->paid == TransferCandidate::UNPAID))->false();
            expect('candidate transfer is paid',$TransferCandidate->paid)->equals(TransferCandidate::PAID);
            expect('main transfer is completed',Transfer::findOne($TransferCandidate->transfer_id)->transfer_status)->equals(Transfer::STATUS_TRANSFER_COMPLETE);
            expect('main transfer is not in progress anymore',(Transfer::findOne($TransferCandidate->transfer_id)->transfer_status == Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS))->false();

        });
    }
}