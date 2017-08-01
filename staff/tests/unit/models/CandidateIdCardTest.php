<?php
namespace staff\tests\models;

use Yii;
use Codeception\Specify;
use staff\models\Store;
use staff\models\Company;
use staff\models\Candidate;
use staff\models\CandidateIdCard;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Store as StoreFixture;

class CandidateIdCardTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \staff\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
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
            ]
        ]);
    }

    protected function _after()
    {
    }

    // tests
    public function testSomeFeature()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Comapny data loaded', Company::findOne(['company_id' => 1]))->notNull();
            expect('Store data loaded', Store::find()->one())->notNull();
            expect('Candidate data loaded', Candidate::find()->one())->notNull();
        });
          
        $this->specify('CandidateIdCard model function to generate zip file containing ID Card details', function () {            
            
            $candidates = Candidate::find()
                ->limit(2)
                ->all();

            $result = CandidateIdCard::createZip($candidates);
            
            expect('Check generating zip', file_exists($result['zip']))->true(); 
        });
    }
    
    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New Candidate ID Card', function () {
            $model = new CandidateIdCard();
            $model->candidate_id = 1;
            $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['candidate_id' => 1]))->notNull();
        });

        $this->specify('Update New Candidate ID Card', function() {
            $model = CandidateIdCard::find()->one();
            $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
            expect('updated successfully', $model->save())->true();
            expect('Updated Record is in database', $model->findOne(['candidate_id' => 1]))->notNull();
        });
    }
}