<?php
namespace company\tests\models;

use Yii;
use Codeception\Specify;
use company\models\Store;
use company\models\Company;
use company\models\Candidate;
use company\models\Transfer;
use company\models\TransferCandidate;
use common\fixtures\CompanyFixture;
use common\fixtures\TransferFixture;
use common\fixtures\TransferCandidateFixture;
use yii\db\Expression;


class TransferTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \admin\tests\UnitTester
     */
    protected $tester;

    protected function _before() {
        Yii::$app->params['inCodeception']             = true;
        Yii::$app->params['transfer_cost']             = 0.35;
    }

    public function _fixtures()
    {
        return [
            'company' => CompanyFixture::class,
            'transfer' => TransferFixture::class,
            'transferCandidate' => TransferCandidateFixture::class
        ];
    }

    protected function _after(){}

    /**
     * Check if fixtures have loaded.
     */
    public function testFixturesHaveLoaded()
    {
        $this->assertNotNull(Transfer::findOne(['transfer_id' => 1]));
        $this->assertNotNull(TransferCandidate::find()->one());
    }

    /**
     * Check that we can mark Transfer as payment sent from locked status
     */
    public function testMarkTransferAsPaymentSentFromLockedStatus()
    {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
        $this->assertTrue($transfer->paymentSent());
    }

    /**
     * Make sure error is thrown when you try to mark a transfer as payment sent
     * when it is already marked as payment sent.
     */
    public function testMarkTransferAsPaymentSentWhenSetAsAlreadyPaymentSent()
    {
        $this->expectException("yii\base\Exception");

        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
        $transfer->paymentSent();
    }

    /**
     * Company should be able to lock a transfer draft/initiated
     */
    public function testCompanyCanLockATransferDraft()
    {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
        $this->assertTrue($transfer->lock());
    }

    /**
     * Company shouldnt be able to mark a transfer as locked when its already locked.
     */
    public function testCompanyCantMarkAsLockedWhenAlreadyLocked()
    {
        $this->expectException("yii\base\Exception");
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
        $transfer->lock();
    }

    /**
     * See if company can delete a transfer with status which isnt a draft
     */
    public function testCompanyCantDeleteTransferWhichIsntADraft()
    {
        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_INITIATED]);
        $this->assertTrue(Transfer::deleteTransfer($transfer));

        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_LOCK]);
        $this->assertTrue(Transfer::deleteTransfer($transfer));

        $transfer = Transfer::findOne(['transfer_status' => Transfer::STATUS_PAYMENT_SENT]);
        $this->assertFalse(Transfer::deleteTransfer($transfer));
    }

    /**
     * Test case for Transfer model with / without child company
     */
    public function testTransferModel()
    {
        // Transfer Model Without Child Company ============================================

        //$this->specify('Transfer model without child company', function () {

            $transfer = Transfer::find()
                ->andWhere([
                    'company_id' => 5,
                    'transfer_status' => Transfer::STATUS_INITIATED
                ])
                ->one();

            //generate invoice
            $transfer->lock();

            $this->assertEquals(1, count($transfer->invoices));

            $this->assertEquals(0, sizeof($transfer->childTransfers));

            /*$total = $transfer
                ->getTransferCandidates()
                ->sum('(candidate_hourly_rate * hours) + bonus + transfer_cost');

            expect('Testing transfer total field', number_format($total, 3, '.', ''))
                ->equals($transfer->total);

            $company_total = $transfer
                ->getTransferCandidates()
                ->sum('(company_hourly_rate * hours) + bonus');

            expect('Testing transfer company total field', number_format($company_total, 3, '.', ''))
                ->equals($transfer->company_total);*/
        //});


        // Test case for Transfer model with child company ============================================

       // $this->specify('Transfer model with child company', function () {
            $transfer = Transfer::find()
                ->andWhere([
                    'company_id' => 1,
                    'transfer_status' => Transfer::STATUS_INITIATED
                ])
                ->one();

            $companiesCount = $transfer
                ->getTransferCandidates()
                ->groupByCompany($transfer->company_id)
                ->count();

            //generate invoice
            $transfer->lock();

            $this->assertEquals($companiesCount, sizeof($transfer->invoices));

            $this->assertEquals($companiesCount, sizeof($transfer->childTransfers));

            $this->assertEquals($companiesCount, sizeof($transfer->childTransferInvoices));

            //for main transfer

            /*$total = $transfer
                ->getTransferCandidates()
                ->sum('(candidate_hourly_rate * hours) + bonus - bonus_commission + transfer_cost');

            expect('Testing main transfer total field', number_format($total, 3, '.', ''))
                ->equals($transfer->total);

            $company_total = $transfer
                ->getTransferCandidates()
                ->sum('(company_hourly_rate * hours) + bonus');

            expect('Testing main transfer company total field', number_format($company_total, 3, '.', ''))
                ->equals($transfer->company_total);*/

            //for child transfer

            foreach ($transfer->childTransfers as $childTransfer)
            {
                //todo: include tests for minutes + seconds

                $total = $childTransfer
                    ->getTransferCandidates()
                    ->sum('candidate_total');//(candidate_hourly_rate * hours) + bonus - bonus_commission

                $this->assertEquals(number_format($total, 3, '.', ''), $childTransfer->total);

                $company_total = $childTransfer
                    ->getTransferCandidates()
                    ->sum('company_total');//(company_hourly_rate * hours) + bonus + transfer_cost

                $this->assertEquals(number_format($company_total, 3, '.', ''), $childTransfer->company_total);
            }
     //   });
    }

    /**
     * For company with sub companies
     */
//    public function testSaveTransfer()
//    {
//        // Test Save Transfer With Child ================================================
//
//        $this->specify('Fixtures should be loaded', function() {
//            expect('Company fixture loaded', Company::findOne(['company_id' => 1]))->notNull();
//            expect('Store fixture loaded', Store::findOne(['store_id' => 1]))->notNull();
//            expect('Candidate fixture loaded', Candidate::findOne(['candidate_id' => 1]))->notNull();
//        });
//
//        $this->specify('Add new transfer for company with child', function() {
//
//            $company = Company::find()
//                ->andWhere('parent_company_id > 0')
//                ->one()
//                ->parentCompany;
//
//            $candidates = $company
//                ->getCandidates()
//                ->all();
//
//            $arrCandidate = [];
//            $total = 0;
//            $company_total = 0;
//
//            foreach ($candidates as $value)
//            {
//                $data = [
//                    'bonus' => rand(0, 10),
//                    'hours' => rand(0, 100),
//                    'candidate_id' => $value->candidate_id
//                ];
//
//                $company_bonus_commission = $value->company->company_bonus_commission;
//                $company_hourly_rate = $value->company->company_hourly_rate;
//
//                //if value not set take from parent company
//
//                if(($company_bonus_commission + $company_hourly_rate == 0) && $value->company->parentCompany)
//                {
//                    $company_bonus_commission = $value->company->parentCompany->company_bonus_commission;
//                    $company_hourly_rate = $value->company->parentCompany->company_hourly_rate;
//                }
//
//                $bonus_commission = $data['bonus'] * $company_bonus_commission / 100;
//
//                if ((int)$data['hours'] > 0 || $data['bonus'] > 0) {
//                    $total += $data['bonus'] - $bonus_commission + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
//                    $company_total += $data['bonus'] + ($data['hours'] * $company_hourly_rate);
//                }
//
//                $arrCandidate[] = $data;
//            }
//
//            $start_date = '2010/10/10';
//            $end_date = '2010/12/10';
//            $result = Transfer::saveTransfer($company, $arrCandidate,$start_date,$end_date);
//
//            expect('Transfer should saved', $result)->hasKey('transfer_id');
//
//            $transfer = Transfer::findOne($result['transfer_id']);
//
//            expect('Transfer total - admin will pay', $transfer->total)
//                ->equals(number_format($total, 3, '.', ''));
//
//            expect('Transfer company total - company will pay', $transfer->company_total)->equals(number_format($company_total, 3, '.', ''));
//        });
//
//
//        // For company without sub companies ================================================
//        // Save Transfer Without Child
//        $this->specify('Add new transfer for company without child', function() {
//
//            $company = Company::findOne(3);
//
//            $candidates = $company
//                ->getCandidates()
//                ->all();
//
//            $arrCandidate = [];
//            $total = 0;
//            $company_total = 0;
//
//            foreach ($candidates as $value)
//            {
//                $data = [
//                    'bonus' => rand(0, 10),
//                    'hours' => rand(0, 100),
//                    'candidate_id' => $value->candidate_id
//                ];
//
//                $company_bonus_commission = $value->company->company_bonus_commission;
//                $company_hourly_rate = $value->company->company_hourly_rate;
//
//                //if value not set take from parent company
//
//                if(($company_bonus_commission + $company_hourly_rate == 0) && $value->company->parentCompany)
//                {
//                    $company_bonus_commission = $value->company->parentCompany->company_bonus_commission;
//                    $company_hourly_rate = $value->company->parentCompany->company_hourly_rate;
//                }
//
//                $bonus_commission = $data['bonus'] * $company_bonus_commission / 100;
//
//                if ((int)$data['hours']>0 || $data['bonus'] > 0) {
//                    $total += $data['bonus'] - $bonus_commission + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
//                    $company_total += $data['bonus'] + ($data['hours'] * $company_hourly_rate);
//                }
//
//                $arrCandidate[] = $data;
//            }
//
//            $start_date = '2010/10/10';
//            $end_date = '2010/12/10';
//            $result = Transfer::saveTransfer($company, $arrCandidate,$start_date,$end_date);
//
//            expect('Transfer should saved', $result)->hasKey('transfer_id');
//
//            $transfer = Transfer::findOne($result['transfer_id']);
//
//            expect('Transfer total - admin will pay', $transfer->total)
//                ->equals(number_format($total, 3, '.', ''));
//
//            expect('Transfer company total - company will pay', $transfer->company_total)->equals(number_format($company_total, 3, '.', ''));
//        });
//    }
//
//    /**
//     * success test case For company with child
//     */
//    public function testSuccessUpdateTransferWithChild()
//    {
//
//        // test Success Update Transfer With Child ============================================================
//
//        $this->specify ('fixture loaded data', function () {
//            expect ('is file exist', Transfer::find ()->one ())->notNull ();
//        });
//
//        $this->specify ('Update transfer for company with child', function () {
//
//            $child = Company::find ()
//                ->filterChild ()
//                ->one ();
//
//            $company = $child->parentCompany;
//
//            $candidates = $company
//                ->getCandidates ()
//                ->all ();
//
//            $transfer = $company
//                ->getTransfers ()
//                ->andWhere (['transfer_status' => Transfer::STATUS_INITIATED])
//                ->one ();
//
//            $arrCandidate = [];
//            $total = 0;
//            $company_total = 0;
//
//            foreach ($candidates as $value) {
//                $data = [
//                    'bonus' => rand (0, 10),
//                    'hours' => rand (0, 100),
//                    'candidate_id' => $value->candidate_id
//                ];
//
//                $company_bonus_commission = $value->company->company_bonus_commission;
//                $company_hourly_rate = $value->company->company_hourly_rate;
//
//                //if value not set take from parent company
//
//                if (($company_bonus_commission + $company_hourly_rate == 0) && $value->company->parentCompany) {
//                    $company_bonus_commission = $value->company->parentCompany->company_bonus_commission;
//                    $company_hourly_rate = $value->company->parentCompany->company_hourly_rate;
//                }
//
//                $bonus_commission = $data['bonus'] * $company_bonus_commission / 100;
//
//                if ((int)$data['hours'] > 0 || $data['bonus'] > 0) {
//                    $total += $data['bonus'] - $bonus_commission + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
//                    $company_total += $data['bonus'] + ($data['hours'] * $company_hourly_rate);
//                }
//
//                $arrCandidate[] = $data;
//            }
//            $start_date = '2010/10/10';
//            $end_date = '2010/12/10';
//
//            $result = $transfer->updateTransfer ($arrCandidate, $start_date, $end_date);
//
//            expect ('Transfer should updated', $result['message'])->contains ('Your transfer has been updated.');
//
//            $transfer = Transfer::findOne ($transfer->transfer_id);
//
//            expect ('Transfer total - admin will pay', $transfer->total)
//                ->equals (number_format ($total, 3, '.', ''));
//
//            expect ('Transfer company total - company will pay', $transfer->company_total)->equals (number_format ($company_total, 3, '.', ''));
//
//            //check invoice after update
//
//            $transfer->lock ();//generate invoices
//
//            //expect('Should generate transfer for each sub company', sizeof($transfer->invoices))
//            //    ->equals(sizeof($company->subCompanies));
//        });
//
//    }
//
//    /**
//     * success test case For company without child/parent
//     */
//    public function testSuccessUpdateTransferWithoutChild() {
//
//        // test Success Update Transfer Without Child ============================================================
//
//        $this->specify('Update transfer for company without child', function() {
//
//            $company = Company::find()
//                ->filterWithoutChild()
//                ->one();
//
//            $transfer = $company
//                ->getTransfers()
//                ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//                ->one();
//
//            $candidates = $company
//                ->getCandidates()
//                ->all();
//
//            $arrCandidate = [];
//            $total = 0;
//            $company_total = 0;
//
//            foreach ($candidates as $value)
//            {
//                $data = [
//                    'bonus' => rand(0, 10),
//                    'hours' => rand(0, 100),
//                    'candidate_id' => $value->candidate_id
//                ];
//
//                $company_bonus_commission = $value->company->company_bonus_commission;
//                $company_hourly_rate = $value->company->company_hourly_rate;
//
//                //if value not set take from parent company
//
//                if(($company_bonus_commission + $company_hourly_rate == 0) && $value->company->parentCompany)
//                {
//                    $company_bonus_commission = $value->company->parentCompany->company_bonus_commission;
//                    $company_hourly_rate = $value->company->parentCompany->company_hourly_rate;
//                }
//
//                $bonus_commission = $data['bonus'] * $company_bonus_commission / 100;
//
//                if ((int)$data['hours']>0 || $data['bonus'] > 0) {
//                    $total += $data['bonus'] - $bonus_commission + ($data['hours'] * $value->candidate_hourly_rate) + Yii::$app->params['transfer_cost'];
//                    $company_total += $data['bonus'] + ($data['hours'] * $company_hourly_rate);
//                }
//
//                $arrCandidate[] = $data;
//            }
//
//            $start_date = '2010/10/10';
//            $end_date = '2010/12/10';
//
//            $result = $transfer->updateTransfer($arrCandidate,$start_date,$end_date);
//
//            expect('Transfer should updated', $result['message'])->contains('Your transfer has been updated.');
//
//            $transfer = Transfer::findOne($transfer->transfer_id);
//
//            expect('Transfer total - admin will pay', $transfer->total)
//                ->equals(number_format($total, 3, '.', ''));
//
//            expect('Transfer company total - company will pay', $transfer->company_total)->equals(number_format($company_total, 3, '.', ''));
//
//            //check invoice after update
//
//            $transfer->lock();//generate invoices
//
//            expect('Should generate invoice for transfer', $transfer->invoices)->notNull();
//        });
//
//    }
//
//    public function testFixtureLoaded(){
//        expect('is file exist',Transfer::findOne(9))->notNull();
//    }
//
//    /**
//     * fail test case For company with sub companies
//     * when transfer is with empty candidate
//     */
//    public function testFailUpdateTransferForEmptyCandidateWhenCompanyWithoutChild()
//    {
//        $company = Company::find()
//            ->filterWithoutChild()
//            ->one();//a company without parent + child
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer([], $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('Candidate not found');
//    }
//
//    /**
//     * Fail For Invalid Candidates
//     */
//    public function testFailUpdateTransferForInvalidCandidateWhenCompanyWithoutChild()
//    {
//        $company = Company::find()
//            ->filterWithoutChild()
//            ->one();//a company without parent + child
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [
//            [
//                'bonus' => rand(0, 10),
//                'hours' => rand(0, 100),
//                'candidate_id' => 205
//            ]
//        ];
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate, $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('Candidate not found');
//    }
//
//    /**
//     * Fail For Zero Total
//     */
//    public function testFailUpdateTransferForTotalZeroWhenCompanyWithoutChild()
//    {
//        $company = Company::find()
//            ->filterWithoutChild()
//            ->one();//a company without parent + child
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [];
//
//        foreach($transfer->transferCandidates as $transferCandidate) {
//            $arrCandidate[] = [
//                'bonus' => 0,
//                'hours' => 0,
//                'candidate_id' => $transferCandidate->candidate_id
//            ];
//        }
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate, $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('transfer total can not be zero!');
//    }
//
//    /**
//     * Fail For Negative Hours
//     */
//    public function testFailUpdateTransferWithNegativeHoursWhenCompanyWithChild()
//    {
//        $child = Company::find()
//            ->filterChild ()
//            ->one();
//
//        $company = $child->parentCompany;
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [];
//
//        foreach($transfer->transferCandidates as $transferCandidate) {
//            $arrCandidate[] = [
//                'bonus' => 0,
//                'hours' => -2,
//                'candidate_id' => $transferCandidate->candidate_id
//            ];
//        }
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate, $start_date, $end_date);
//
//        expect('Transfer should return error', $result['operation'])->equals ('error');
//    }
//
//    /**
//     * Fail For Negative Bonus
//     */
//    public function testFailUpdateTransferWithNegativeBonusWhenCompanyWithChild()
//    {
//        $child = Company::find()
//            ->filterChild ()
//            ->one();
//
//        $company = $child->parentCompany;
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [];
//
//        foreach($transfer->transferCandidates as $transferCandidate) {
//            $arrCandidate[] = [
//                'bonus' => -10,
//                'hours' => 2,
//                'candidate_id' => $transferCandidate->candidate_id
//            ];
//        }
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate, $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->hasKey('candidates');
//    }
//
//    /**
//     * Fail For Empty Candidates
//     */
//    public function testFailUpdateTransferWithEmptyCandidatesWhenCompanyWithChild()
//    {
//        $child = Company::find()
//            ->filterChild ()
//            ->one();
//
//        $company = $child->parentCompany;
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer([], $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('Candidate not found');
//    }
//
//    /**
//     * Fail For Invalid Candidates
//     */
//    public function testFailUpdateTransferWithInvalidCandidatesWhenCompanyWithChild()
//    {
//        $child = Company::find()
//            ->filterChild ()
//            ->one();
//
//        $company = $child->parentCompany;
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [
//            [
//                'bonus' => rand(0, 10),
//                'hours' => rand(0, 100),
//                'candidate_id' => 205
//            ]
//        ];
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate, $start_date, $end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('Candidate not found');
//    }
//
//    /**
//     * Fail For Zero Total
//     */
//    public function testFailUpdateTransferWithZeroTotalWhenCompanyWithChild()
//    {
//        $child = Company::find()
//            ->filterChild ()
//            ->one();
//
//        $company = $child->parentCompany;
//
//        $transfer = $company
//            ->getTransfers()
//            ->andWhere(['transfer_status' => Transfer::STATUS_INITIATED])
//            ->one();
//
//        $arrCandidate = [];
//
//        foreach($transfer->transferCandidates as $transferCandidate) {
//            $arrCandidate[] = [
//                'bonus' => 0,
//                'hours' => 0,
//                'candidate_id' => $transferCandidate->candidate_id
//            ];
//        }
//
//        $start_date = '2010/10/10';
//        $end_date = '2010/12/10';
//
//        $result = $transfer->updateTransfer($arrCandidate,$start_date,$end_date);
//
//        expect('Transfer should return error', $result['message'])->contains('transfer total can not be zero!');
//    }
}
