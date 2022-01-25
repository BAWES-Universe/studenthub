<?php
namespace common\tests;

use common\fixtures\CompanyContactFixture;
use common\models\CompanyContact;
use Yii;
use Codeception\Specify;
use common\fixtures\CandidateFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\InvoiceFixture;
use company\models\Transfer;
use common\fixtures\CompanyFixture;
use common\models\TransferCandidate;
use company\models\Company;
use company\models\Invoice;


class TransferTest extends \Codeception\Test\Unit
{
    use Specify;

    public $token, $model;
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'contactToken' => ContactTokenFixture::className(),
            'company'      => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'candidate'    => CandidateFixture::className(),
            'invoice'      => InvoiceFixture::className()
        ];
    }

    public function _before()
    {
        \Yii::$app->params['inCodeception'] = true;
        \Yii::$app->params['transfer_cost'] = 0.35;

        $this->model = Company::findOne(1);

        $companyContact = CompanyContact::find()
            ->andWhere (['company_id' => 1])
            ->one();

        $this->token = $companyContact->contact->getAccessToken()->token_value;
    }

    protected function _after(){}

    public function testValidations()
    {
        $this->specify('Transfer model : Company ID validation', function () {
            $model = new Transfer();

            $model->company_id = 'test';
            expect('passing random string', $model->validate(['company_id']))->false();

            $model->company_id = 1;
            expect('passing valid company id', $model->validate(['company_id']))->true();

            $model->company_id = 9999;
            expect('passing invalid company id', $model->validate(['company_id']))->false();
        });

        $this->specify('Transfer model : Transfer status ID validation', function () {
            $model = new Transfer();

            $model->transfer_status = Transfer::STATUS_INITIATED;
            expect('passing valid transfer status', $model->validate(['transfer_status']))->true();

            $model->transfer_status = 99;
            expect('passing invalid transfer status', $model->validate(['transfer_status']))->false();
        });

        $this->specify('Transfer model : Transfer total validation', function () {
            $model = new Transfer();

            $model->total = 43.56;
            expect('passing valid transfer total', $model->validate(['total']))->true();

            $model->total = 'test';
            expect('passing invalid transfer total', $model->validate(['total']))->false();
        });

        $this->specify('Transfer model : Transfer company total validation', function () {
            $model = new Transfer();

            $model->company_total = 43.56;
            expect('passing valid transfer company total', $model->validate(['company_total']))->true();

            $model->company_total = 'test';
            expect('passing invalid transfer company total', $model->validate(['company_total']))->false();
        });
    }

    public function testSaveTransfer() {

        foreach ($this->model->getCandidates()->all() as $candidate) {
            $candidates[] = ['candidate_id'=>$candidate->candidate_id,'candidate' =>$candidate,'bonus'=>1,'hours'=>1];
        }

        $company = $this->model;
        $start_date = "2020-11-11";
        $end_date = "2020-12-10";
        //save transfer
        $response = Transfer::saveTransfer($company, $candidates, $start_date, $end_date);
        expect('expecting true', $response['operation'])->equals('success');

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one());
        expect_that(TransferCandidate::find()->andWhere(['transfer_id'=>$response['transfer_id']])->count() == count($candidates));
    }

    /**
     * check transfer delete
     */
    public function testDeleteWithDraftTransfer() {

        foreach ($this->model->getCandidates()->all() as $candidate) {
            $candidates[] = ['candidate_id'=>$candidate->candidate_id,'candidate' =>$candidate,'bonus'=>1,'hours'=>1];
        }

        $company = $this->model;
        $start_date = "2020-11-11";
        $end_date = "2020-12-10";

        //save transfer

        $response = Transfer::saveTransfer($company, $candidates, $start_date, $end_date);

        expect('expecting true', $response['operation'])->equals('success');

        //expect transfer got saved

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one());

        //expect transfer candidates saved

        expect_that(TransferCandidate::find()->andWhere(['transfer_id'=>$response['transfer_id']])->count() == count($candidates));

        $model = Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one();

        //transfer getting delete

        expect_that(Transfer::deleteTransfer($model) == true);

        //test transfer deleted

        expect_not(Transfer::findOne($model->transfer_id));

        //test invoices deleted

        expect_not(Invoice::findOne(['transfer_id' => $response['transfer_id']]));

        //test candidate transfer entries deleted

        expect_not(TransferCandidate::findOne(['transfer_id' => $response['transfer_id']]));

        $childTransfers = Yii::$app->db->createCommand('select * from transfer where parent_transfer_id="' . $response['transfer_id'] . '"')->queryAll();

        foreach($childTransfers as $child) {

            //test child transfer deleted

            expect_not(Transfer::findOne($child['transfer_id']));

            //test child invoices deleted

            expect_not(Invoice::findOne(['transfer_id' => $child['transfer_id']]));

            //test child candidate transfer entries deleted

            expect_not(TransferCandidate::findOne(['transfer_id' => $child['transfer_id']]));
        }
    }

    /**
     * try to delete locked transfer
     * @throws \yii\db\Exception
     */
    public function testDeleteWithLockTransfer() {

        foreach ($this->model->getCandidates()->all() as $candidate) {
            $candidates[] = ['candidate_id'=>$candidate->candidate_id,'candidate' =>$candidate,'bonus'=>1,'hours'=>1];
        }

        $company = $this->model;
        $start_date = "2020-11-11";
        $end_date = "2020-12-10";
        //save transfer
        $response = Transfer::saveTransfer($company, $candidates, $start_date, $end_date);
        expect('expecting true', $response['operation'])->equals('success');

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one());

        expect_that(TransferCandidate::find()->andWhere(['transfer_id'=>$response['transfer_id']])->count() == count($candidates));

        $model = Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one();

        expect_that($model->lock() == true);

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id'],'transfer_status'=>Transfer::STATUS_LOCK])->one());

        expect_that($model->getInvoices()->count() > 0);

        $transferLock = Transfer::findOne(['transfer_status'=>Transfer::STATUS_LOCK]);

        expect_that(Transfer::deleteTransfer($model) == true);

        //test transfer deleted

        expect_not(Transfer::findOne($model->transfer_id));

        //test invoices deleted

        expect_not(Invoice::findOne(['transfer_id' => $response['transfer_id']]));

        //test candidate transfer entries deleted

        expect_not(TransferCandidate::findOne(['transfer_id' => $response['transfer_id']]));

        $childTransfers = Yii::$app->db->createCommand('select * from transfer where parent_transfer_id="' . $response['transfer_id'] . '"')->queryAll();

        foreach($childTransfers as $child) {

            //test child transfer deleted

            expect_not(Transfer::findOne($child['transfer_id']));

            //test child invoices deleted

            expect_not(Invoice::findOne(['transfer_id' => $child['transfer_id']]));

            //test child candidate transfer entries deleted

            expect_not(TransferCandidate::findOne(['transfer_id' => $child['transfer_id']]));
        }
    }

    /**
     * try to delete transfer with payment sent status 
     */
    public function testDeleteWithPaymentSentTransfer() {

        foreach ($this->model->getCandidates()->all() as $candidate) {
            $candidates[] = ['candidate_id'=>$candidate->candidate_id,'candidate' =>$candidate,'bonus'=>1,'hours'=>1];
        }

        $company = $this->model;
        $start_date = "2020-11-11";
        $end_date = "2020-12-10";
        //save transfer
        $response = Transfer::saveTransfer($company, $candidates, $start_date, $end_date);
        expect('expecting true', $response['operation'])->equals('success');
        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one());
        expect_that(TransferCandidate::find()->andWhere(['transfer_id'=>$response['transfer_id']])->count() == count($candidates));

        $model = Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id']])->one();

        expect_that($model->lock() == true);

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id'],'transfer_status'=>Transfer::STATUS_LOCK])->one());

        expect_that($model->getInvoices()->count() > 0);

        expect_that($model->paymentSent() == true);

        expect_that(Transfer::find()->andWhere(['transfer_id'=>$response['transfer_id'],'transfer_status'=>Transfer::STATUS_PAYMENT_SENT])->one());

        expect_that(Transfer::deleteTransfer($model) == false);
    }
}
