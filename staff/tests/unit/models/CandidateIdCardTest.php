<?php

namespace staff\tests\models;

use Yii;
use staff\models\Store;
use staff\models\Company;
use staff\models\Candidate;
use staff\models\CandidateIdCard;
use common\fixtures\CandidateIdCardFixture;


class CandidateIdCardTest extends \Codeception\Test\Unit {

    /**
     * @var \staff\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'candidates' => \common\fixtures\CandidateFixture::className(),
            'candidateIdCardFixture' => CandidateIdCardFixture::className()
        ];
    }

    protected function _after() {
        
    }

    /**
     * Fixtures should be loaded
     */
    public function testFixtureLoad() {
        expect('Company data loaded', Company::find()->count())->notNull();
        expect('Store data loaded', Store::find()->count())->notNull();
        expect('Candidate data loaded', Candidate::find()->count())->notNull();
        expect('Candidate ID data loaded', \common\models\CandidateIdCard::find()->count())->notNull();
    }

    /**
     * CandidateIdCard model function to generate
     * zip file containing ID Card details
     *
    public function testGenerateZipFile() {
        $candidates = Candidate::find()
                ->limit(2)
                ->all();

        $result = CandidateIdCard::createIdCards($candidates);
        expect('Check generating zip', file_exists($result['zip']))->true();
    }*/

    /**
     * Tests Create for New Candidate ID Card with existing candidate id in table
     */
    public function testCrudErrorForNewCandidateIDCardWhenCandidateIDAlreadyExist() {
        $candidateIdCard = CandidateIdCard::find()
            ->andWhere(['deleted' => 0])
            ->one();

        $model = new CandidateIdCard();
        $model->candidate_id = $candidateIdCard->candidate_id;
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        //$model->deleted = 0;
        $model->validate();

        expect('error found', $model->errors)->hasKey('candidate_id');
        expect('Record is in database', $model->errors['candidate_id'][0])->contains('Candidate Id already exist.');
    }

    /**
     * Tests Create for New Candidate ID Card
     */
    public function testCrudForNewCandidateIDCard() {
        
        //remove old data 
        
        CandidateIdCard::deleteAll(['candidate_id' => 3]);
        
        $model = new CandidateIdCard();
        $model->candidate_id = 3;
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        expect('error found', count($model->errors))->equals(0);
        expect('Created successfully', $model->save())->true();
        expect('Record is in database', $model->findOne(['candidate_id' => 3]))->notNull();
    }

    /**
     * Tests Create for Update Candidate ID Card
     */
    public function testCrudForUpdateCandidateIDCard() {
        $model = CandidateIdCard::find()->one();
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        expect('updated successfully', $model->save())->true();
        expect('Updated Record is in database', $model->findOne(['candidate_id' => 1]))->notNull();
    }

}
