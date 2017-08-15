<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use candidate\models\CandidateToken;
use candidate\fixtures\Candidate as CandidateFixture;
use candidate\fixtures\CandidateToken as CandidateTokenFixture;
use candidate\fixtures\Transfer as TransferFixture;
use candidate\fixtures\TransferCandidate as TransferCandidateFixture;
use common\fixtures\Invoice as InvoiceFixture;
use Codeception\Util\HttpCode;

class AccountCest
{
    public $token;
    
    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'candidate' => [
                'class' => CandidateFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidate.php'                
            ],
            'candidateToken' => [
                'class' => CandidateTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidateToken.php'
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
            ]
        ]);
        
        $this->token = CandidateToken::find()->one()->token_value;
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function SalaryMethodTest(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Salary Method response');
        $I->sendGET('v1/account/salary');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(['transfer_id'=>5,'total'=>27]);
    }

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function validatePassword(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password with empty fields');
        $I->sendPOST('v1/account/change-password', array('old_password' => '', 'new_password' => ''));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty old password"]);
    }
    
    public function tryNewPasswordEmpty(FunctionalTester $I) 
    {
        $I->amGoingTo('Validate Change Password with new password empty field');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123', 'new_password' => ''));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty new password"]);
    }
    
    public function tryOldPasswordEmpty(FunctionalTester $I) 
    {
        $I->amGoingTo('Validate Change Password with old password empty field');
        $I->sendPOST('v1/account/change-password', array('old_password' => '', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty old password"]);
    }

    public function trySamePassword(FunctionalTester $I) 
    {
        $I->amGoingTo('Validate Change Password for same old and new password');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "New password should not be same as old password"]);
    }
    
    public function tryInvalidOldPassword(FunctionalTester $I) 
    {
        $I->amGoingTo('Validate Change Password for 123456');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123123123', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Invalid Old Password"]);
    }
    
    public function tryInvalidPasswordLength(FunctionalTester $I) 
    {
        $I->amGoingTo('Validate Change Password for new password length');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123456', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "New password length should be great then equal to 5"]);
    }

    public function tryValidPassword(FunctionalTester $I) 
    {
        $I->amGoingTo('Successful test for change password');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123456', 'new_password' => '1234567'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Password changed successfully!"]);
    }
}
