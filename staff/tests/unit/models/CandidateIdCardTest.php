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
            'candidates' => \common\fixtures\CandidateFixture::class,
            'candidateIdCardFixture' => CandidateIdCardFixture::class
        ];
    }

    protected function _after() {
        
    }

    /**
     * Fixtures should be loaded
     */
    public function testFixtureLoad() {
        $this->assertNotNull(Company::find()->count());
        $this->assertNotNull(Store::find()->count());
        $this->assertNotNull(Candidate::find()->count());
        $this->assertNotNull(\common\models\CandidateIdCard::find()->count());
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

        $this->assertArrayHasKey('candidate_id', $model->errors);
        $this->assertEquals('Candidate Id already exist.', $model->errors['candidate_id'][0]);
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
        $this->assertEquals(0, count($model->errors));
        $this->assertTrue($model->save());
        $this->assertNotNull($model->findOne(['candidate_id' => 3]));
    }

    /**
     * Tests Create for Update Candidate ID Card
     */
    public function testCrudForUpdateCandidateIDCard() {
        $model = CandidateIdCard::find()->one();
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        $this->assertTrue($model->save());
        $this->assertNotNull($model->findOne(['candidate_id' => 1]));
    }

}
