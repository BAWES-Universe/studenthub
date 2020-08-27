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


class TransferCest
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
    }
    
    /**
     * list candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfer-candidates?transfer_confirmation_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * list candidate transfers by transfer_id
     * @param FunctionalTester $I
     */
    public function tryToListByTransfer(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > by-transfer api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfer-candidates/by-transfer/' . $this->transfer->transfer_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * list candidate transfers by transfer file id
     * @param FunctionalTester $I
     */
    public function tryToListByTransferFile(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > by-transfer-file api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfer-candidates/by-transfer-file/' . $this->transferFile->transfer_file_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * view candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToViewCandidateTransfer(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > view api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/transfer-candidates/' . $this->transferCandidate->tc_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * mark unpaid candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToMarkUnpaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark unpaid api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/unpaid/' . $this->transferCandidate->tc_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * mark paid candidate transfers 
     * @param FunctionalTester $I
     */
    public function tryToMarkPaid(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark paid api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/paid/' . $this->transferCandidate->tc_id, [
            'transfer_confirmation_id' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * mark multiple candidate transfers as paid 
     * @param FunctionalTester $I
     */
    public function tryToMarkPaidAll(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark-paid-all api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/mark-paid-all', [
            'transferCandidate' => [
                [
                    'tc_id' => $this->transferCandidate->tc_id,
                    'transfer_id' => $this->transfer->transfer_id
                ]
            ]
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * mark multiple candidate transfers as unpaid 
     * @param FunctionalTester $I
     */
    public function tryToMarkUnpaidAll(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > transfer-candidate > mark-unpaid-all api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/transfer-candidates/mark-unpaid-all', [
            'transferCandidate' => [
                [
                    'tc_id' => $this->transferCandidate->tc_id,
                    'transfer_id' => $this->transfer->transfer_id
                ]
            ]
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
