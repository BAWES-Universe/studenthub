<?php
namespace admin\tests\models;

use Yii;
use Codeception\Specify;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use common\fixtures\Transfer as TransferFixture;
use common\fixtures\TransferCandidate as TransferCandidateFixture;

class TransferTest extends \Codeception\Test\Unit
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

    protected function _after()
    {
    }

    public function testSomeFeature()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
            expect('Transfer is in the table', TransferCandidate::find()->one())->notNull();
        });
        
        $this->specify('Transfer model mark Transfer as Payment Received', function () {            
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
            expect('Mark as "Payment Received" from "Payment Sent" Status', $transfer->paymentReceived())->true();
            
            try {
                $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS]);
                $result = $transfer2->paymentReceived();                    
            } catch (yii\base\Exception $ex) {
                $result = false;
            }       
            expect('Mark as "Payment Received" from "Payment Received" Status', $result)->false();
        });
        
        $this->specify('Transfer model mark as Initiated from Lock', function () {            
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
            expect('Unlock Transfer from Lock Status', $transfer->unlock())->true();
                        
            try {
                $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
                $result = $transfer2->unlock();
            } catch (yii\base\Exception $ex) {
                $result = false;
            }       
            expect('Unlock Transfer from Unlock/Initiated Status', $result)->false();
        });
        
        $this->specify('Transfer model mark as Lock from Payment Sent', function () {            
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
            expect('Mark as lock from payment sent status', $transfer->lock())->true();
            
            try {
                $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
                $result = $transfer2->lock();
            } catch (yii\base\Exception $ex) {
                $result = false;
            }     
            expect('Mark as lock from lock status', $result)->false();
        });
    }
}