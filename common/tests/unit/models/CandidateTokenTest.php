<?php
namespace common\tests;

use Yii;
use common\models\CandidateToken;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\StoreFixture;
use Codeception\Specify;


class CandidateTokenTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'candidates' => CandidateFixture::className(),
            'country' => CountryFixture::className(),
            'university' => UniversityFixture::className(),
            'store' => StoreFixture::className(),
            'candidateToken' => CandidateTokenFixture::className(),
        ];
    }

    protected function _before() 
    {	    
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
            
            $candidate_email = CandidateToken::findOne(['candidate_id'=>'1'])->candidate->candidate_email;
           
            expect('relative data testing', $candidate_email)->equals($this->tester->grabFixture('candidates', 'candidate0')->candidate_email);
        });
    }
}
