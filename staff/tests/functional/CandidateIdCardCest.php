<?php

namespace staff\tests;

use yii;
use yii\helpers\ArrayHelper;
use common\models\StaffToken;
use staff\models\Candidate;
use common\models\CandidateIdCard;
use common\fixtures\StaffTokenFixture;
use common\fixtures\CandidateIdCardFixture;
use Codeception\Util\HttpCode;


class CandidateIdCardCest {

    public $token;

    public function _fixtures() {
        return [
            'staffToken' => StaffTokenFixture::className(),
            'candidateIdCard' => CandidateIdCardFixture::className()
        ];
    }

    public function _before(FunctionalTester $I) {
        Yii::$app->params['inCodeception'] = true;
        
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * List Candidates having ID Cards
     * @param FunctionalTester $I
     */
    public function listCandidatesHavingIdCards(FunctionalTester $I) {
        $I->wantTo('List Candidates having ID Cards');
        $I->sendGET('v1/candidate-id-cards/list-candidate-ids?page=1&candidate_name=');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * List Candidates to Generate ID Cards
     * @param FunctionalTester $I
     */
    public function listCandidatesToGenerateIdCards(FunctionalTester $I) {
        $I->wantTo('List Candidates to Generate ID Cards');
        $I->sendGET('v1/candidate-id-cards/list-candidates');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Generate ID Cards
     * @param FunctionalTester $I
     */
    public function tryToGenerateIdCards(FunctionalTester $I) {
        //candidate not having cards
        $candidates = Candidate::find()
            ->andWhere('candidate_id NOT IN(select candidate_id from candidate_id_card)')
            ->all();

        $arrCandidates = ArrayHelper::map($candidates, 'candidate_id', 'candidate_id');

        $I->wantTo('Generate ID Cards');
        $I->sendPOST('v1/candidate-id-cards/generate', [
            'candidates' => $arrCandidates
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * List Expired ID
     * @param FunctionalTester $I
     */
    public function listExpiredIdCards(FunctionalTester $I) {
        $I->wantTo('List Expired ID');
        $I->sendGET('v1/candidate-id-cards/list-expired');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Renew ID Cards
     * @param FunctionalTester $I
     */
    public function renewIDCards(FunctionalTester $I) {
        $arrCandidates = [1, 2];

        $I->wantTo('Renew ID Cards');
        $I->sendPOST('v1/candidate-id-cards/renew', [
            'candidates' => $arrCandidates
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Total Expired ID
     * @param FunctionalTester $I
     */
    public function getTotalExpiredIdCards(FunctionalTester $I) {
        $I->wantTo('List Expired ID');
        $I->sendGET('v1/candidate-id-cards/total-expired');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Get ID
     * @param FunctionalTester $I
     * giving while running test case
     *
    public function getID(FunctionalTester $I) {

        $model = CandidateIdCard::find()->one();

        $I->wantTo('View ID');
        $I->sendGET('v1/candidate-id-cards/' . $model->id .'/'. $this->token);
        $I->seeResponseCodeIs(HttpCode::OK);
    }*/
}                      
                   