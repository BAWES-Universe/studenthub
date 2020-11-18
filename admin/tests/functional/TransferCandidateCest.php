<?php
namespace admin\tests;

use Yii;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\TransferFileFixture;
use common\fixtures\InvoiceFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use common\models\TransferFile;
use admin\models\Transfer;
use Codeception\Util\HttpCode;


class TransferCandidateCest
{
    public $token;

    public function _fixtures() 
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'transferFile' => TransferFileFixture::className(),
            'invoice' => InvoiceFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
        
        $this->transfer = Transfer::find()
                ->isParentTransfer()
                ->andWhere(['IN', 'transfer.transfer_status', [Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::STATUS_TRANSFER_COMPLETE]])
                ->one();
        
        $this->transferCandidate = $this->transfer->transferCandidates[0];
                
        $this->transferFile = TransferFile::find()->one();
        $I->amBearerAuthenticated($this->token);
    }
    
    /**
     * list candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate api response for listing');
        $I->sendGET('v1/transfer-candidates?transfer_confirmation_id=0');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "transfer_id" => $this->transfer->transfer_id
        ]);
    }
    
    /**
     * list candidate transfers by transfer_id
     * @param FunctionalTester $I
     */
    public function tryToListByTransfer(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > by-transfer api response');
        $I->sendGET('v1/transfer-candidates/by-transfer/' . $this->transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "transfer_id" => $this->transfer->transfer_id
        ]);
    }
    
    /**
     * list candidate transfers by transfer file id
     * @param FunctionalTester $I
     */
    public function tryToListByTransferFile(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > by-transfer-file api response');
        $I->sendGET('v1/transfer-candidates/by-transfer-file/' . $this->transferFile->transfer_file_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * view candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToViewCandidateTransfer(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > view api response');
        $I->sendGET('v1/transfer-candidates/' . $this->transferCandidate->tc_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'tc_id' => $this->transferCandidate->tc_id
        ]);
    }
    
    /**
     * mark unpaid candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToMarkUnpaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark unpaid api response');
        $I->sendPATCH('v1/transfer-candidates/unpaid/' . $this->transferCandidate->tc_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => 'Candidate Transfer marked as "unpaid" successfully'
        ]);
    }
    
    /**
     * mark paid candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToMarkPaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark paid api response');
        $I->sendPATCH('v1/transfer-candidates/paid/' . $this->transferCandidate->tc_id, [
            'transfer_confirmation_id' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => 'Candidate Transfer marked as "paid" successfully'
        ]);
    }
    
    /**
     * mark multiple candidate transfers as paid 
     * @param FunctionalTester $I
     */
    public function tryToMarkPaidAll(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark-paid-all api response');
        $I->sendPATCH('v1/transfer-candidates/mark-paid-all', [
            'transferCandidate' => [
                [
                    'tc_id' => $this->transferCandidate->tc_id,
                    'transfer_id' => $this->transfer->transfer_id
                ]
            ]
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "1 candidates have been marked as paid"
        ]);
    }
    
    /**
     * mark multiple candidate transfers as unpaid 
     * @param FunctionalTester $I
     */
    public function tryToMarkUnpaidAll(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark-unpaid-all api response');
        $I->sendPATCH('v1/transfer-candidates/mark-unpaid-all', [
            'transferCandidate' => [
                [
                    'tc_id' => $this->transferCandidate->tc_id,
                    'transfer_id' => $this->transfer->transfer_id
                ]
            ]
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "1 candidates have been marked as unpaid"
        ]);
    }
}


