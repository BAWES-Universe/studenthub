<?php
namespace common\tests;

use Yii;
use common\models\CandidateToken;
use common\fixtures\CandidateToken as CandidateTokenFixture;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Country as CountryFixture;
use common\fixtures\University as UniversityFixture;
use common\fixtures\Store as StoreFixture;
use Codeception\Specify;

class CandidateTokenTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'candidates' => [
                'class' => CandidateFixture::className(),
                'dataFile' => codecept_data_dir() . 'candidate.php'
            ],         
            'country' => [
                'class' => CountryFixture::className(),
                'dataFile' => codecept_data_dir() . 'country.php'
            ],         
            'university' => [
                'class' => UniversityFixture::className(),
                'dataFile' => codecept_data_dir() . 'university.php'
            ],         
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => codecept_data_dir() . 'store.php'
            ],
            'candidateToken' => [
                'class' => CandidateTokenFixture::className(),
                'dataFile' => codecept_data_dir() . 'candidateToken.php'
            ]
        ]);
        
        Yii::$app->params['candidate_max_hourly_rate'] = 2;
    }

    protected function _after()
    {
    }

    /**
     * testing validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Token is in the table', CandidateToken::findOne(['candidate_id'=>'1']))->notNull();
        });


        $this->specify('Test Validator', function() {
            $model = new CandidateToken();
            $model->validate();
            expect('Candidate_id required error',$model->errors)->hasKey('candidate_id');
            expect('token_value required error',$model->errors)->hasKey('token_value');
            expect('token_status required error',$model->errors)->hasKey('token_status');
            expect('total 3 errors',count($model->errors))->equals(3);
        });
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Token is in the table', CandidateToken::findOne(['candidate_id'=>'1']))->notNull();
        });


        $this->specify('Test existing Token', function() {
            expect('unique token string',strlen(CandidateToken::generateUniqueTokenString()))->greaterThan(31);
        });

        $this->specify('relation testing', function() {
            expect('relative data testing', CandidateToken::findOne(['candidate_id'=>'1'])->getCandidate()->one()->candidate_email)->equals($this->tester->grabFixture('candidates', 0)->candidate_email);
        });
    }
}