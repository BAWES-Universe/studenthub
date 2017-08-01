<?php
namespace company\tests\models;

use Yii;
use Codeception\Specify;
use company\models\Store;
use company\models\Company;
use company\models\Candidate;
use company\models\Transfer;
use company\models\TransferCandidate;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Store as StoreFixture;
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
        Yii::$app->params['inCodeception'] = true;
        Yii::$app->params['transfer_cost'] = 0.35;
        Yii::$app->params['candidate_max_hourly_rate'] = 2;

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
            ]
        ]);
    }

    protected function _after(){}

    /**
     * Test case for payment status changes like sent lock delete
     */
    public function testPaymentStatus()
    {
        $this->specify('Fixtures should be loaded', function () {
            expect('Transfer is in the table', Transfer::findOne(['transfer_id' => 1]))->notNull();
            expect('Transfer Candidate is in the table', TransferCandidate::find()->one())->notNull();
        });

        $this->specify('Transfer model mark as Payment Sent', function () {
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
            expect('Mark as "Payment Sent" from "Locked" status', $transfer->paymentSent())->true();

            try {
                $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
                $result = $transfer2->paymentSent();
            } catch (yii\base\Exception $ex) {
                $result = false;
            }
            expect('Mark as "Payment Sent" from "Payment Sent" status', $result)->false();
        });


        //Test case for payment lock ================================================


        $this->specify('Transfer model mark as Lock', function () {
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
            expect('Mark as lock from "Initiated" status', $transfer->lock())->true();

            try {
                $transfer2 = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
                $result = $transfer2->lock();
            } catch (yii\base\Exception $ex) {
                $result = false;
            }
            expect('Mark as lock from lock status', $result)->false();
        });


        //Test case for payment Delete ================================================

        $this->specify('Delete transfer with "Initiated" or "Locked" status', function () {
            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
            expect('Delete transfer having Draft/Initiated status', Transfer::deleteTransfer($transfer))->true();

            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
            expect('Delete transfer having Locked status', Transfer::deleteTransfer($transfer))->true();

            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
            expect('Delete transfer having Payment sent status', Transfer::deleteTransfer($transfer))->false();

            $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
            expect('Delete transfer having Payment sent status', Transfer::deleteTransfer($transfer))->false();
        });
    }

    /**
     * Test case for Transfer model with / without child company
     */
    public function testTransferModel()
    {

        // Transfer Model Without Child Company ============================================

        $this->specify('Transfer model without child company', function () {
            $transfer = Transfer::find()
                ->where([
                    'company_id' => 3,
                    'transfer_status' => Transfer::STATUS_INITIATED
                ])
                ->one();

            //generate invoice
            $transfer->lock();

            expect('Should generate 1 invoice for main transfer', count($transfer->invoices))
                ->equals(1);

            expect('Should generate child transfer for each sub company of candidates in transfer', sizeof($transfer->childTransfers))
                ->equals(0);

            $total = $transfer
                ->getTransferCandidates()
                ->sum('(candidate_hourly_rate * hours) + bonus + transfer_cost');

            expect('Testing transfer total field', $total)
                ->equals($transfer->total);

            $company_total = $transfer
                ->getTransferCandidates()
                ->sum('(company_hourly_rate * hours) + bonus');

            expect('Testing transfer company total field', $company_total)
                ->equals($transfer->company_total);
        });


        // Test case for Transfer model with child company ============================================

        $this->specify('Transfer model with child company', function () {
            $transfer = Transfer::find()
                ->where([
                    'company_id' => 1,
                    'transfer_status' => Transfer::STATUS_INITIATED
                ])
                ->one();

            $companiesCount = $transfer
                ->getTransferCandidates()
                ->groupBy('company_id')
                ->count();

            //generate invoice
            $transfer->lock();

            expect('Should generate invoice for each sub company of candidates in transfer', sizeof($transfer->invoices))
                ->equals($companiesCount);

            expect('Should generate child transfer for each sub company of candidates in transfer', sizeof($transfer->childTransfers))
                ->equals($companiesCount);

            expect('Testing childTransferInvoices method', sizeof($transfer->childTransferInvoices))
                ->equals($companiesCount);

            //for main transfer

            $total = $transfer
                ->getTransferCandidates()
                ->sum('(candidate_hourly_rate * hours) + bonus + transfer_cost');

            expect('Testing main transfer total field', $total)
                ->equals($transfer->total);

            $company_total = $transfer
                ->getTransferCandidates()
                ->sum('(company_hourly_rate * hours) + bonus');

            expect('Testing main transfer company total field', $company_total)
                ->equals($transfer->company_total);

            //for child transfer

            foreach ($transfer->childTransfers as $childTransfer)
            {
                $total = $childTransfer
                    ->getTransferCandidates()
                    ->sum('(candidate_hourly_rate * hours) + bonus + transfer_cost');

                expect('Testing child transfer total field', $total)
                    ->equals($childTransfer->total);

                $company_total = $childTransfer
                    ->getTransferCandidates()
                    ->sum('(company_hourly_rate * hours) + bonus');

                expect('Testing child transfer company total field', $company_total)
                    ->equals($childTransfer->company_total);
            }
        });
    }

    /**
     * For company with sub companies
     */
    public function testSaveTransfer()
    {
        // Test Save Transfer With Child ================================================

        $this->specify('Fixtures should be loaded', function() {
            expect('Company fixture loaded', Company::findOne(['company_id' => 1]))->notNull();
            expect('Store fixture loaded', Store::findOne(['store_id' => 1]))->notNull();
            expect('Candidate fixture loaded', Candidate::findOne(['candidate_id' => 1]))->notNull();
        });

        $this->specify('Add new transfer for company with child', function() {
            $company = Company::find()
                ->where('parent_company_id > 0')
                ->one()
                ->parentCompany;

            $candidates = $company
                ->getCandidates()
                ->all();

            $arrCandidate = [];
            $total = 0;
            $company_total = 0;

            foreach ($candidates as $value)
            {
                $data = [
                    'bonus' => rand(0, 10),
                    'hours' => rand(0, 100),
                    'candidate_id' => $value->candidate_id
                ];

                if ((int)$data['hours']>0) {
                    $total += $data['bonus'] + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
                    $company_total += $data['bonus'] + ($data['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                $arrCandidate[] = $data;
            }

            $result = Transfer::saveTransfer($company, $arrCandidate);

            expect('Transfer should saved', $result)->hasKey('transfer_id');

            $transfer = Transfer::findOne($result['transfer_id']);
            expect('Transfer total - admin will pay', $transfer->total)->equals($total);
            expect('Transfer company total - company will pay', $transfer->company_total)->equals($company_total);
        });


        // For company without sub companies ================================================
        // Save Transfer Without Child
        $this->specify('Add new transfer for company without child', function() {
            $company = Company::findOne(3);

            $candidates = $company
                ->getCandidates()
                ->all();

            $arrCandidate = [];
            $total = 0;
            $company_total = 0;

            foreach ($candidates as $value)
            {
                $data = [
                    'bonus' => rand(0, 10),
                    'hours' => rand(0, 100),
                    'candidate_id' => $value->candidate_id
                ];

                if ((int)$data['hours']>0) {
                    $total += $data['bonus'] + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
                    $company_total += $data['bonus'] + ($data['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                $arrCandidate[] = $data;
            }

            $result = Transfer::saveTransfer($company, $arrCandidate);

            expect('Transfer should saved', $result)->hasKey('transfer_id');

            $transfer = Transfer::findOne($result['transfer_id']);
            expect('Transfer total - admin will pay', $transfer->total)->equals($total);
            expect('Transfer company total - company will pay', $transfer->company_total)->equals($company_total);
        });
    }

    /**
     * success test case For company
     */
    public function testSuccessUpdateTransfer() {

        // test Success Update Transfer With Child ============================================================

        $this->specify('fixture loaded data', function() {
            expect('is file exist',Transfer::findOne(9))->notNull();
        });

        $this->specify('Update transfer for company with child', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $candidates = $company
                ->getCandidates()
                ->all();

            $arrCandidate = [];
            $total = 0;
            $company_total = 0;

            foreach ($candidates as $value)
            {
                $data = [
                    'bonus' => rand(0, 10),
                    'hours' => rand(0, 100),
                    'candidate_id' => $value->candidate_id
                ];

                if ((int)$data['hours']>0) {
                    $total += $data['bonus'] + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
                    $company_total += $data['bonus'] + ($data['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                $arrCandidate[] = $data;
            }

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should updated', $result['message'])->contains('Your transfer has been updated.');

            $transfer = Transfer::findOne($TransferID);
            expect('Transfer total - admin will pay', $transfer->total)->equals($total);
            expect('Transfer company total - company will pay', $transfer->company_total)->equals($company_total);
        });


        // test Success Update Transfer Without Child ============================================================

        $this->specify('Update transfer for company without child', function() {

            $TransferID = 13;
            $CompanyID = 3;
            $company = Company::findOne($CompanyID);

            $candidates = $company
                ->getCandidates()
                ->all();

            $arrCandidate = [];
            $total = 0;
            $company_total = 0;

            foreach ($candidates as $value)
            {
                $data = [
                    'bonus' => rand(0, 10),
                    'hours' => rand(0, 100),
                    'candidate_id' => $value->candidate_id
                ];

                if ((int)$data['hours']>0) {
                    $total += $data['bonus'] + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
                    $company_total += $data['bonus'] + ($data['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                $arrCandidate[] = $data;
            }

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should updated', $result['message'])->contains('Your transfer has been updated.');

            $transfer = Transfer::findOne($TransferID);
            expect('Transfer total - admin will pay', $transfer->total)->equals($total);
            expect('Transfer company total - company will pay', $transfer->company_total)->equals($company_total);
        });

    }

    /**
     * fail test case For company with sub companies
     */
    public function testFailUpdateTransferWithChild() {

        // test fail Update Transfer With Child ============================================================
        $this->specify('fixture loaded data', function() {
            expect('is file exist',Transfer::findOne(9))->notNull();
        });

        $this->specify('Fail For Empty Candidates', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $result = Transfer::updateTransfer($company, $TransferID, []);

            expect('Transfer should return error', $result['message'])->contains('Candidate not found');
        });


        $this->specify('Fail For Invalid Candidates', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $data = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => 205
            ];
            $arrCandidate[] = $data;

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->contains('Candidate not found');
        });


        $this->specify('Fail For Zero Total', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $data = [
                'bonus' => 0,
                'hours' => 0,
                'candidate_id' => 2
            ];
            $arrCandidate[] = $data;

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->contains('transfer total can not be zero!');
        });

        $this->specify('Fail For Negative Hours', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $arrCandidate = [
                [
                    'bonus' => 0,
                    'hours' => 2,
                    'candidate_id' => 2
                ],
                [
                    'bonus' => 0,
                    'hours' => -1,
                    'candidate_id' => 2
                ]
            ];

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->hasKey('candidates');
        });

        $this->specify('Fail For Negative Bonus', function() {

            $TransferID = 9;
            $CompanyID = 1;
            $company = Company::findOne($CompanyID);

            $data = [
                'bonus' => -1,
                'hours' => 1,
                'candidate_id' => 2
            ];
            $arrCandidate[] = $data;

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->hasKey('candidates');
        });



        // test fail Update Transfer Without Child ============================================================
        $this->specify('Fail For Empty Candidates', function() {

            $TransferID = 13;
            $CompanyID = 3;
            $company = Company::findOne($CompanyID);

            $result = Transfer::updateTransfer($company, $TransferID, []);

            expect('Transfer should return error', $result['message'])->contains('Candidate not found');
        });


        $this->specify('Fail For Invalid Candidates', function() {

            $TransferID = 13;
            $CompanyID = 3;
            $company = Company::findOne($CompanyID);

            $data = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => 205
            ];
            $arrCandidate[] = $data;

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->contains('Candidate not found');
        });


        $this->specify('Fail For Zero Total', function() {

            $TransferID = 13;
            $CompanyID = 3;
            $company = Company::findOne($CompanyID);

            $data = [
                'bonus' => 0,
                'hours' => 0,
                'candidate_id' => 2
            ];
            $arrCandidate[] = $data;

            $result = Transfer::updateTransfer($company, $TransferID, $arrCandidate);

            expect('Transfer should return error', $result['message'])->contains('transfer total can not be zero!');
        });
    }
}