<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\models\Candidate;
use common\models\AdminToken;
use admin\fixtures\CompanyFixture;
use admin\fixtures\StoreFixture;
use admin\fixtures\CandidateFixture;
use admin\fixtures\TransferFixture;
use admin\fixtures\TransferCandidateFixture;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\CountryFixture;
use Codeception\Util\HttpCode;

class CandidateCest
{
    public $token, $candidate_id;

    public function _before(FunctionalTester $I)
    {
        $I->haveFixtures([
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/admin.php'
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/adminToken.php'
            ],
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/country.php'
            ],
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

        $this->token = AdminToken::find()
                ->one()
                ->token_value;

        $this->candidate_id = Candidate::find()
            ->one()
            ->candidate_id;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * list candidates to review
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api response for review listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=review&review=0&expand=store,university,country,company,bank');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Approve candidate
     * @param FunctionalTester $I
     */
    public function tryToApprove(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to approve candidate');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPATCH('v1/candidates/approve/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by country
     * @param FunctionalTester $I
     */
    public function tryToListByCountry(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by country');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=country_id&country_id=168');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by store
     * @param FunctionalTester $I
     */
    public function tryToListByStore(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by store');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=store_id&store_id=5');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List candidates by university
     * @param FunctionalTester $I
     */
    public function tryToListByUniversity(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/search?by=university_id&university_id=1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get total no of candidate to review
     * @param FunctionalTester $I
     */
    public function getTotalCandidates(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates by university');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/total-to-review');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get candidate salary transfer
     * @param FunctionalTester $I
     */
    public function getSalaryTransfers(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/transfers/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Get candidate's work history
     * @param FunctionalTester $I
     */
    public function getWorkHistory(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > candidates api to list candidates\' salary transfer');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/candidates/work-history/' . $this->candidate_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
