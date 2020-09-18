<?php
namespace company\tests;

use common\fixtures\InvoiceFixture;
use Yii;
use common\fixtures\CompanyTokenFixture;
use common\fixtures\CandidateFixture;
use company\tests\FunctionalTester;
use company\models\Transfer;
use company\models\Company;
use common\components\Excel;
use Codeception\Util\HttpCode;


class TransferForWithChildCest
{
    public $token, $model;

    public function _before(FunctionalTester $I)
    {
        Yii::$app->params['inCodeception'] = true;
        Yii::$app->params['transfer_cost'] = 0.35;

        $this->model = Company::findOne(1);
        $this->token = $this->model->accessToken->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    public function _fixtures()
    {
            return [
                    'companyToken' => CompanyTokenFixture::className(),
                    'candidate'    => CandidateFixture::className(),
                    'invoice'    => InvoiceFixture::className()
            ];
    }
    
    /**
     * List transfers with relations for company with child
     * @param FunctionalTester $I
     */
    public function tryToListWithRelations(FunctionalTester $I)
    {
        $I->wantTo('List transfers with relations for company with child');
        $I->sendGET('v1/transfers?expand=invoices,transferCandidates');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Create transfers for company with child by excel
     * @param FunctionalTester $I
     */
    public function tryToCreateTransferByExcel(FunctionalTester $I)
    {
        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'transferByExcelForCompanyWithChild.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->model->candidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
            'columns' => [
                [
                    'header' => 'candidate_id',
                    'value' => function($data) {
                        return $data->candidate_id;
                    }
                ],
                [
                    'header' => 'candidate_name',
                    'value' => function($data) {
                        return $data->candidate_name;
                    }
                ],
                [
                    'header' => 'hours',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ]
            ]
        ]);

        //save in S3 temp bucket

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'temp-' . $fileName,
            [],
            sys_get_temp_dir() . '/' . $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        
        $I->wantTo('Create transfer for company with child by excel upload');
        $I->haveHttpHeader('Content-Type', 'form-data');
        $I->sendPOST('v1/transfers/create-by-excel', [
            "excel" => basename($response['ObjectURL'])
        ]);
        $I->seeResponseMatchesJsonType([
             'transfer_id' => 'integer'
        ]);

        unlink(sys_get_temp_dir() . '/' . $fileName);
    }

    /**
     * Edit transfers for company with child by excel
     * @param FunctionalTester $I
     */
    public function tryToEditTransferByExcel(FunctionalTester $I)
    {
        $transfer = $this->model
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->isParentTransfer()
            ->one();

        //create excel

        $I->wantTo('Create excel to upload @ ' . sys_get_temp_dir());

        $fileName = 'transferByExcelForCompanyWithChild.xlsx';

        Excel::export([
            'isMultipleSheet' => false,
            'models' => $this->model->candidates,
            'savePath' => sys_get_temp_dir(),
            'fileName' => $fileName,
            'columns' => [
                [
                    'header' => 'candidate_id',
                    'value' => function($data) {
                        return $data->candidate_id;
                    }
                ],
                [
                    'header' => 'candidate_name',
                    'value' => function($data) {
                        return $data->candidate_name;
                    }
                ],
                [
                    'header' => 'hours',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ],
                [
                    'header' => 'bonus',
                    'value' => function() {
                        return rand(1, 100);
                    }
                ]
            ]
        ]);

        //save in S3 temp bucket

        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'temp-' . $fileName,
            [],
            sys_get_temp_dir() . '/' . $fileName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
           
        $I->wantTo('Create transfer for company with child by excel upload');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'form-data');
        $I->sendPATCH('v1/transfers/edit-by-excel/' . $transfer->transfer_id, [
            "excel" => basename($response['ObjectURL'])
        ]);
        $I->seeResponseContainsJson([
             'operation' => 'success'
        ]);
        
        unlink(sys_get_temp_dir() . '/' . $fileName);
    }

    /**
     * Create transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToCreateTransfer(FunctionalTester $I)
    {
        $candidates = $this->model
                ->getCandidates()
                ->all();

        $arrCandidate = [];

        foreach ($candidates as $value)
        {
            $arrCandidate[] = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => $value->candidate_id
            ];
        }

        $I->wantTo('Create transfer for company with child');
        $I->sendPOST('v1/transfers', [
            'candidates' => $arrCandidate
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Edit transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToEditTransfer(FunctionalTester $I)
    {
        $candidates = $this->model
                ->getCandidates()
                ->all();

        $arrCandidate = [];

        foreach ($candidates as $value)
        {
            $arrCandidate[] = [
                'bonus' => rand(0, 10),
                'hours' => rand(0, 100),
                'candidate_id' => $value->candidate_id
            ];
        }

        $transfer = $this->model
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();

        $I->wantTo('Edit transfer for company with child');
        $I->sendPATCH('v1/transfers/'. $transfer->transfer_id, [
            'candidates' => $arrCandidate
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

	/**
	 * Mark transfers as "Locked" for company with child
	 * @param FunctionalTester $I
	 */
	public function tryToMarkLocked(FunctionalTester $I)
	{
		$transfer = $this->model
			->getTransfers()
			->where(['transfer_status' => Transfer::STATUS_INITIATED])
			->one();

		$I->wantTo('Mark transfer as "Locked" for company with child');
		$I->sendPATCH('v1/transfers/lock/' . $transfer->transfer_id);
		$I->seeResponseCodeIs(HttpCode::OK); // 200
		$I->seeResponseIsJson();
	}

	/**
     * Mark transfers as "Payment Sent" for company with child
     * @param FunctionalTester $I
     */
    public function tryToMarkPaymentSent(FunctionalTester $I)
    {
        $transfer = $this->model
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_LOCK])
            ->one();
        $I->wantTo( 'Mark transfer as "Payment Sent" for company with child' );
        $I->sendPATCH( 'v1/transfers/payment-sent/' . $transfer->transfer_id );
        $I->seeResponseCodeIs( HttpCode::OK ); // 200
        $I->seeResponseIsJson();
    }

	/**
	 * View transfers for company with child
	 * @param FunctionalTester $I
	 */
	public function tryToView(FunctionalTester $I)
	{
		$transfer = $this->model->getTransfers()->one();
		$I->wantTo( 'View transfer with relations for company with child' );
		$I->sendGET( 'v1/transfers/' . $transfer->transfer_id . '?expand=invoices,transferCandidates' );
		$I->seeResponseCodeIs( HttpCode::OK ); // 200
		$I->seeResponseIsJson();

	}

	/**
     * Delete transfers for company with child
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $transfer = $this->model
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_INITIATED])
            ->one();

        $I->wantTo('Delete transfer for company with child');
        $I->sendDELETE('v1/transfers/' . $transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Download invoice for company with child
     * @param FunctionalTester $I
     */
    public function tryToDownloadInvoice(FunctionalTester $I)
    {
        $transfer = $this->model
            ->getTransfers()
            ->where(['transfer_status' => Transfer::STATUS_LOCK])
            ->one();

        $invoice = $transfer
            ->getInvoices()
            ->one();

        $I->wantTo('Download invoice for company with child');
        $I->sendGET('v1/transfers/pdf/' . $invoice->invoice_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
