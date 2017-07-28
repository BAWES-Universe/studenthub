<?php
namespace common\tests;

use Yii;
use common\models\Store;
use common\models\Candidate;
use common\fixtures\Candidate as CandidateFixture;
use common\fixtures\Country as CountryFixture;
use common\fixtures\University as UniversityFixture;
use common\fixtures\Store as StoreFixture;
use Codeception\Specify;

class CandidateTest extends \Codeception\Test\Unit
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
            ] 
        ]);
        
        Yii::$app->params['candidate_max_hourly_rate'] = 2;
    }

    protected function _after()
    {
    }

    public function testValidations()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Candidate #1 is in the table',
                Candidate::findOne(['candidate_id' => 1])
            )->notNull();
            
            expect('Store #1 is in the table',
                Store::findOne(['store_id' => 1])
            )->notNull();
        });
        
        $this->specify('Candidate model required field validation', function() {
            $candidate = new Candidate;
            expect('Bank ID should be required field', $candidate->validate(['bank_id']))->false();
            expect('Unibersity ID should be required field', $candidate->validate(['university_id']))->false();
            expect('Country ID should be required field', $candidate->validate(['country_id']))->false();
            expect('Bank account name should be required field', $candidate->validate(['bank_account_name']))->false();
            expect('Candidate IBAN should be required field', $candidate->validate(['candidate_iban']))->false();
            expect('Candidate name should be required field', $candidate->validate(['candidate_name']))->false();
            expect('Candidate name - Arabic should be required field', $candidate->validate(['candidate_name_ar']))->false();
            expect('Candidate email should be required field', $candidate->validate(['candidate_email']))->false();
            expect('Candidate phone should be required field', $candidate->validate(['candidate_phone']))->false();
            expect('Candidate birth date should be required field', $candidate->validate(['candidate_birth_date']))->false();
            expect('Candidate civil ID should be required field', $candidate->validate(['candidate_civil_id']))->false();
            expect('Candidate civil id expiry date should be required field', $candidate->validate(['candidate_civil_expiry_date']))->false();
            expect('Candidate civil photo front date should be required field', $candidate->validate(['candidate_civil_photo_front']))->false();
            expect('Candidate civil photo back should be required field', $candidate->validate(['candidate_civil_photo_back']))->false();
            expect('Candidate hourly rate should be required field', $candidate->validate(['candidate_hourly_rate']))->false();
            expect('Candidate personel photo should be required field', $candidate->validate(['candidate_personal_photo']))->false();                    
            expect('Candidate password hash should be required field', $candidate->validate(['candidate_personal_photo']))->false();                    
        });
        
        $this->specify('Candidate model integer field validation', function() {
            $candidate = new Candidate;
            $candidate->store_id = 'test';
            expect('String value passed for store_id', $candidate->validate(['store_id']))->false();                                
            $candidate->candidate_status = 'test';
            expect('String value passed for candidate_status', $candidate->validate(['candidate_status']))->false();                    
            $candidate->approved = 'test';
            expect('String value passed for approved', $candidate->validate(['approved']))->false();                    
            $candidate->bank_id = 'test';
            expect('String value passed for bank_id', $candidate->validate(['bank_id']))->false();                    
        });
            
        $this->specify('Candidate model string field validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_iban = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for candidate_iban', $candidate->validate(['candidate_iban']))->false();                                
            $candidate->candidate_address_line1 = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for candidate_address_line1', $candidate->validate(['candidate_address_line1']))->false();                                
            $candidate->bank_account_name = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for bank_account_name', $candidate->validate(['bank_account_name']))->false();                                
            $candidate->candidate_auth_key = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for candidate_auth_key', $candidate->validate(['candidate_auth_key']))->false();                                
            $candidate->candidate_uid = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for candidate_uid', $candidate->validate(['candidate_uid']))->false();                                
            $candidate->candidate_phone = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            expect('Too long value passed for candidate_phone', $candidate->validate(['candidate_phone']))->false();                                
        });
                         
        $this->specify('Candidate model foreign key validation', function() {
            $candidate = new Candidate;
            
            //country 
            $candidate->country_id = 9999;
            expect('Invalid Country ID passed', $candidate->validate(['country_id']))->false();                                
            $candidate->country_id = $this->tester->grabFixture('country', 0);
            expect('Valid Country ID passed', $candidate->validate(['country_id']))->true();                                
            
            //university 
            $candidate->university_id = 9999;
            expect('Invalid University ID passed', $candidate->validate(['university_id']))->false();                                
            $candidate->university_id = $this->tester->grabFixture('university', 0);
            expect('Valid University ID passed', $candidate->validate(['university_id']))->true();                                
        });
         
        $this->specify('Candidate model store validation', function() {
            $candidate = new Candidate;
            $candidate->store_id = 9999;
            $candidate->store_id = Store::find()->one()->store_id;
            expect('Valid Store ID passed', $candidate->errors)->hasntKey('store_id');          
        });
        
        $this->specify('Candidate model hourly rate validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_hourly_rate = 0;
            expect('Invalid value passed', $candidate->validate(['candidate_hourly_rate']))->false();                                
            $candidate->candidate_hourly_rate = 9999;
            expect('Higher than max allowed value passed', $candidate->validate(['candidate_hourly_rate']))->false();                                
            $candidate->candidate_hourly_rate = 1;
            expect('Valid Hourly rate passed', $candidate->validate(['candidate_hourly_rate']))->true();          
        });
        
        $this->specify('Candidate email validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_email = 'candidate1@bawes.net';
            expect('Duplicate email passed', $candidate->validate(['candidate_email']))->false();                                
            $candidate->candidate_email = 'test';
            expect('Random string passed', $candidate->validate(['candidate_email']))->false();                                
            $candidate->candidate_email = 'candidate1@unique.net';
            expect('Valid email passed', $candidate->validate(['candidate_email']))->true();                                
        });
        
        $this->specify('Candidate civil id validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_civil_id = 'XIS1212121';
            expect('Duplicate candidate_civil_id passed', $candidate->validate(['candidate_civil_id']))->false();                                
            $candidate->candidate_civil_id = 'XIS1212121unique';
            expect('Valid candidate_civil_id passed', $candidate->validate(['candidate_civil_id']))->true();                                
        });
        
        $this->specify('Candidate birth date validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_birth_date = date('Y-m-d', strtotime('-25 year'));
            expect('Invalid value passed', $candidate->validate(['candidate_birth_date']))->false();                                
            $candidate->candidate_birth_date = date('Y-m-d', strtotime('-18 year'));
            expect('Lower bound value passed', $candidate->validate(['candidate_birth_date']))->true();                                
            $candidate->candidate_birth_date = date('Y-m-d', strtotime('-24 year'));
            expect('Upper bound value passed', $candidate->validate(['candidate_birth_date']))->true();                                
        });
        
        $this->specify('Candidate candidate civil expiry date validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('-1 day'));
            expect('Invalid value passed', $candidate->validate(['candidate_civil_expiry_date']))->false();                                
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('+1 day'));
            expect('Valid value passed', $candidate->validate(['candidate_civil_expiry_date']))->true();                                
        });
    }
}