<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use candidate\models\CandidateToken;
use candidate\fixtures\CandidateFixture;
use candidate\fixtures\CandidateTokenFixture;
use candidate\fixtures\TransferFixture;
use candidate\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use Codeception\Util\HttpCode;

class StatisticsCest
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

        $this->token = CandidateToken::find()
                ->one()
                ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToTest(FunctionalTester $I)
    {
        $I->wantTo('Validate candidate > statistics api response');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/statistics');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
