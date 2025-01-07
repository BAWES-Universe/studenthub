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
            'candidates' => CandidateFixture::class,
            'country' => CountryFixture::class,
            'university' => UniversityFixture::class,
            'store' => StoreFixture::class,
            'candidateToken' => CandidateTokenFixture::class,
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
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(CandidateToken::findOne(['candidate_id'=>'1']));
        //});

        //$this->specify('Test Validator', function() {
            $model = new CandidateToken();
            $model->validate();
            $this->assertEquals(isset($model->errors['candidate_id']),true);
            $this->assertEquals(isset($model->errors['token_value']),true);
            $this->assertEquals(isset($model->errors['token_status']),true);
            $this->assertEquals(count($model->errors),3);
        //});
    }

    /**
     * testing generate token
     * testing relating data
     */
    public function testGenerateToken()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(CandidateToken::findOne(['candidate_id'=>'1']));
        //});


        //$this->specify('Test existing Token', function() {
            $this->assertGreaterThan(31,strlen(CandidateToken::generateUniqueTokenString()));
        //});

        //$this->specify('relation testing', function() {
            
            $candidate_email = CandidateToken::findOne(['candidate_id'=>'1'])->candidate->candidate_email;
           
            $this->assertEquals($candidate_email,$this->tester->grabFixture('candidates', 'candidate0')->candidate_email);
        //});
    }
}
