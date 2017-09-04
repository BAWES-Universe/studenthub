<?php
namespace staff\tests\models;

use Yii;
use staff\models\Store;
use staff\models\Company;
use staff\models\Candidate;
use staff\models\CandidateIdCard;
use common\fixtures\CompanyFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\CandidateIdCardFixture;
use common\fixtures\StoreFixture;

class CandidateIdCardTest extends \Codeception\Test\Unit
{
    /**
     * @var \staff\tests\UnitTester
     */
    protected $tester;

	public function _fixtures()
	{
		return [
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
			'candidateIdCardFixture' => [
                'class' => CandidateIdCardFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/candidateIdCard.php'
            ]
        ];
    }

    protected function _after()
    {
    }

    /**
     * Fixtures should be loaded
     */
    public function testFixtureLoad()
    {
        expect('Company data loaded', Company::findOne(['company_id' => 1]))->notNull();
        expect('Store data loaded', Store::find()->one())->notNull();
        expect('Candidate data loaded', Candidate::find()->one())->notNull();
    }

    /**
     * CandidateIdCard model function to generate
     * zip file containing ID Card details
     */
    public function testGenerateZipFile()
    {
        $candidates = Candidate::find()
                ->limit(2)
                ->all();
        $result = CandidateIdCard::createZip($candidates);
        expect('Check generating zip', file_exists($result['zip']))->true();
    }

    /**
     * Tests Create for New Candidate ID Card with existing candidate id in table
     */
    public function testCrudErrorForNewCandidateIDCardWhenCandidateIDAlreadyExist()
    {
        $model = new CandidateIdCard();
        $model->candidate_id = 1;
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        $model->validate();
        expect('error found', $model->errors)->hasKey('candidate_id');
        expect('Record is in database', $model->errors['candidate_id'][0])->contains('Candidate ID "1" has already been taken');
    }

    /**
     * Tests Create for New Candidate ID Card
     */
    public function testCrudForNewCandidateIDCard()
    {
        $model = new CandidateIdCard();
        $model->candidate_id = 2;
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        expect('error found', count($model->errors))->equals(0);
        expect('Created successfully', $model->save())->true();
        expect('Record is in database', $model->findOne(['candidate_id' => 2]))->notNull();
    }

    /**
     * Tests Create for Update Candidate ID Card
     */
    public function testCrudForUpdateCandidateIDCard()
    {
        $model = CandidateIdCard::find()->one();
        $model->expiry_date = date('Y-m-d', strtotime('+3 months'));
        expect('updated successfully', $model->save())->true();
        expect('Updated Record is in database', $model->findOne(['candidate_id' => 1]))->notNull();
    }
}
