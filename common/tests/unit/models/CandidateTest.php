<?php

namespace common\tests\unit\models;

use Yii;
use common\models\Store;
use common\models\Candidate;
use common\models\CandidateExperience;
use common\models\CandidateSkill;
use common\models\CandidateIdCard;
use common\models\CandidateToken;
use common\models\CandidateWorkHistory;
use common\models\TransferCandidate;
use common\fixtures\CandidateFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\StoreFixture;
use common\fixtures\CandidateIdCardFixture;
use common\fixtures\CandidateSkillFixture;
use common\fixtures\TransferFixture;
use common\fixtures\BankFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\CandidateWorkHistoryFixture;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\CandidateExperienceFixture;
use Codeception\Specify;


class CandidateTest extends \Codeception\Test\Unit
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
            'country'    => CountryFixture::class,
            'bank'    => BankFixture::class,
            'university' => UniversityFixture::class,
            'store'      => StoreFixture::class,
            'transfer'      => TransferFixture::class,
            'transferCandidate' => TransferCandidateFixture::class,
            'workHistory' => CandidateWorkHistoryFixture::class,
            'accessToken' => CandidateTokenFixture::class,
            'candidateIdCards' => CandidateIdCardFixture::class,
            'candidateSkills' => CandidateSkillFixture::class,
            'candidateExperience' => CandidateExperienceFixture::class,
        ];
    }

    public function _before()
    {
        \Yii::$app->params['algolia_candidate_index'] = 'test_candidate_public';
    }

    protected function _after()
    {
    }

    public function testValidations()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Candidate::findOne(['candidate_id' => 1]), 'Candidate #1 is in the table');
            $this->assertNotNull(Store::findOne(['store_id' => 1]), 'Store #1 is in the table');
             
        //});

        //$this->specify('Candidate model required field validation', function() {
            $candidate = new Candidate;
           // $this->assertFalse($candidate->validate(['store_id']), 'Store ID should be required');
            $this->assertFalse($candidate->validate(['university_id']), 'University ID should be required');
            $this->assertFalse($candidate->validate(['country_id']), 'Country ID should be required');
            $this->assertFalse($candidate->validate(['candidate_email']), 'Candidate email should be required');
            $this->assertFalse($candidate->validate(['candidate_birth_date']), 'Candidate birth date should be required');
            $this->assertFalse($candidate->validate(['candidate_civil_photo_front']), 'Candidate civil photo front should be required');
            $this->assertFalse($candidate->validate(['candidate_civil_photo_back']), 'Candidate civil photo back should be required');
            $this->assertFalse($candidate->validate(['candidate_personal_photo']), 'Candidate personal photo should be required');
            $this->assertFalse($candidate->validate(['candidate_password_hash']), 'Candidate password hash should be required');
           
        //});

        //$this->specify('Candidate model integer field validation', function() {
            $candidate = new Candidate;

            $candidate->store_id = 'test';
            $candidate->candidate_status = 'test';
            $this->assertFalse($candidate->validate(['candidate_status']), 'String value passed for candidate_status');

            $candidate->approved = 'test';
            $this->assertFalse($candidate->validate(['approved']), 'String value passed for approved');

            $candidate->candidate_gender = 'test';
            $this->assertFalse($candidate->validate(['candidate_gender']), 'String value passed for candidate_gender');

            $candidate->candidate_gender = 1;
            $this->assertTrue($candidate->validate(['candidate_gender']), 'Valid value passed for candidate_gender');
            
             
        //});

        //$this->specify('Candidate model string field validation', function() {
            $candidate = new Candidate;
            
            $candidate->candidate_name = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_name']), 'Too long value passed for candidate_name');
            
            $candidate->candidate_name_ar = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_name_ar']), 'Too long value passed for candidate_name_ar');
            
            $candidate->candidate_address_line1 = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_address_line1']), 'Too long value passed for candidate_address_line1');
            

            $candidate->bank_account_name = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['bank_account_name']), 'Too long value passed for bank_account_name');
            

            $candidate->candidate_auth_key = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_auth_key']), 'Too long value passed for candidate_auth_key');

            $candidate->candidate_uid = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_uid']), 'Too long value passed for candidate_uid');
            
            $candidate->candidate_iban = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            $this->assertFalse($candidate->validate(['candidate_iban']), 'Too long value passed for candidate_iban');
             
            //$candidate->candidate_phone = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            //expect('Too long value passed for candidate_phone', $candidate->validate(['candidate_phone']))->false();
        //});

        //$this->specify('Candidate model foreign key validation', function() {
            $candidate = new Candidate;
            //store
            $store = Store::find()->one();

            $candidate->store_id = 9999;
            $this->assertFalse($candidate->validate(['store_id']), 'Invalid Store ID passed');
            $candidate->store_id = $store->store_id;
            $this->assertTrue($candidate->validate(['store_id']), 'Valid Store ID passed');
            
            //bank
            $bank = $this->tester->grabFixture('bank', 0);
            $candidate->bank_id = 9999;
            $this->assertFalse($candidate->validate(['bank_id']), 'Invalid Bank ID passed');
            
            $candidate->bank_id = $bank->bank_id;
            $this->assertTrue($candidate->validate(['bank_id']), 'Valid Bank ID passed');
            
            //country
            $country = $this->tester->grabFixture('country', 0);

            // ... existing code ...

            $candidate->country_id = 9999;
            $this->assertFalse($candidate->validate(['country_id']), 'Invalid Country ID passed');
            $candidate->country_id = $country->country_id;
            $this->assertTrue($candidate->validate(['country_id']), 'Valid Country ID passed');

            //university
            $univesity = $this->tester->grabFixture('university', 0);

            $candidate->university_id = 9999;
            $this->assertFalse($candidate->validate(['university_id']), 'Invalid University ID passed');
            $candidate->university_id = $univesity->university_id;
            $this->assertTrue($candidate->validate(['university_id']), 'Valid University ID passed');
        //});

        //$this->specify('Candidate model store validation', function() {
            $candidate = new Candidate;
            $candidate->store_id = 9999;
            $candidate->store_id = Store::find()->one()->store_id;
            $this->assertTrue(!isset($candidate->errors['store_id']), 'Valid Store ID passed');
        //});

        //$this->specify('Candidate model hourly rate validation', function() {
            $candidate = new Candidate;

            // get max allowed value 

            $max = 0;

            if($candidate->company && $candidate->company->company_hourly_rate)
            {
                $max = $candidate->company->company_hourly_rate;
            }
            elseif($candidate->company && $candidate->company->parentCompany)
            {
                $max =  $candidate->company->parentCompany->company_hourly_rate;
            }

            if(!$max)
                return null;

            $candidate->candidate_hourly_rate = 0;
            $this->assertFalse($candidate->validate(['candidate_hourly_rate']), 'Invalid value passed');
            $candidate->candidate_hourly_rate = $max + 1;
            $this->assertFalse($candidate->validate(['candidate_hourly_rate']), 'Higher than max allowed value passed');
            $candidate->candidate_hourly_rate = $max;
            $this->assertTrue($candidate->validate(['candidate_hourly_rate']), 'Valid Hourly rate passed');
        //});

        //$this->specify('Candidate email validation', function() {
            $candidateData = Candidate::findOne(['deleted'=>'0']);

            $candidate = new Candidate;

            $candidate->candidate_email = $candidateData->candidate_email;
            $this->assertFalse($candidate->validate(['candidate_email']), 'Duplicate email passed');

            $candidate->candidate_email = 'test';
            $this->assertFalse($candidate->validate(['candidate_email']), 'Random string passed');

            $candidate->candidate_email = 'candidate1@unique.net';
            $this->assertTrue($candidate->validate(['candidate_email']), 'Valid email passed');

            //candidate_new_email

            $candidate->candidate_new_email = $candidateData->candidate_email;
            $this->assertFalse($candidate->validate(['candidate_new_email']), 'Duplicate new email passed');

            $candidate->candidate_new_email = 'test';
            $this->assertFalse($candidate->validate(['candidate_new_email']), 'Random string passed for candidate new email');

            $candidate->candidate_new_email = 'candidate2@unique.net';
            $this->assertTrue($candidate->validate(['candidate_new_email']), 'Valid new email passed');

        //});

        //$this->specify('Candidate civil id validation', function() {
            $candidate = new Candidate;
            //$candidate->candidate_civil_id = '54747771714';
            //assert('Duplicate candidate_civil_id passed', $candidate->validate(['candidate_civil_id']) === false);

            $candidate->candidate_civil_id = '241397002346';
            $this->assertTrue($candidate->validate(['candidate_civil_id']), 'Valid candidate_civil_id passed');
        //});

        //$this->specify('Candidate candidate civil expiry date validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('-1 day'));
            $this->assertFalse($candidate->validate(['candidate_civil_expiry_date']), 'Invalid value passed');
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('+1 day'));
            $this->assertTrue($candidate->validate(['candidate_civil_expiry_date']), 'Valid value passed');

        //});

        //$this->specify('Candidate beneficiary name and IBAN validation for special characters', function() {
            $candidate = new Candidate;
            $candidate->bank_account_name = '???????';
            $candidate->candidate_iban = '???????';
            $this->assertFalse($candidate->validate(['bank_account_name']), 'Bank account name should not contain special characters');
            $this->assertFalse($candidate->validate(['candidate_iban']), 'Candidate IBAN should not contain special characters');
            $candidate->bank_account_name = 'Manmohan Kumar';
            $candidate->candidate_iban = 'KWKW12345612345612345612345612';
            $this->assertTrue($candidate->validate(['bank_account_name']), 'Bank account name should accept valid value');
            $this->assertTrue($candidate->validate(['candidate_iban']), 'Candidate IBAN should accept valid value');
        //});
    }

    /*public function testAccountMerge()
    {
        //$this->specify ('Merge source account to target', function () {

             $destination = new Candidate();
             $destination->candidate_uid =  '110011001100';
             $destination->store_id =   1;
             $destination->university_id =   1;
             $destination->country_id =   1;
             $destination->bank_account_name =  '       Akshay Bhatia        ';
             $destination->candidate_iban =  '        IBAN123400        ';
             $destination->candidate_name =  'Akshay Bhatia';
             $destination->candidate_name_ar =  'أكشاي باتيا';
             $destination->candidate_personal_photo =  'photos/photo-1497874516406.png';
             $destination->candidate_email =  'candidate111@bawes.net';
             $destination->candidate_phone =   '989898989898';
             $destination->candidate_address_line1 =  '106, BHAYLI CANAL RD';
             $destination->candidate_birth_date =  '1992-11-11';
             $destination->candidate_civil_id =  'XIS2222121';
             $destination->candidate_civil_expiry_date =   date('Y-m-d', strtotime('+1 month'));
             $destination->candidate_civil_photo_front =  'photos/photo-1497874516406.png';
             $destination->candidate_civil_photo_back =  'photos/photo-1497874516406.png';
             $destination->candidate_hourly_rate =   1.7;
             $destination->candidate_auth_key =  'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6';
             $destination->candidate_password_hash =   \Yii::$app->getSecurity()->generatePasswordHash('123456');
             $destination->candidate_password_reset_token =   NULL;
             $destination->candidate_status =   1;
             $destination->approved =   1;
             $destination->candidate_created_at =  '2017-02-23 19:53:20';

             $destination->candidate_created_at =  '2017-02-23 19:53:20';
             $destination->candidate_updated_at =  '2017-02-23 19:53:20';
             $candidate->currency_code = "KWD";
             $destination->save(false);//without validation

             //get candidate with transfer, transfer candidate, work history, candidate_token, candidate_id_card, candidate_skill, candidate_experience

             $source = Candidate::find()->one();

             expect ('Candidate have required data',
                 $source && sizeof ($source->transfers) > 0 && sizeof ($source->transferCandidate) > 0 && sizeof ($source->workHistory) > 0 &&
                 sizeof($source->accessTokens) > 0 && sizeof($source->candidateIdCards) > 0 && sizeof($source->candidateSkills) > 0 &&
                 sizeof($source->candidateExperiences) > 0
             )->true();

             Candidate::merge($source->candidate_id, $destination->candidate_id);

             //make sure transfer moved

             expect ('Transfer moved from source',
                 TransferCandidate::findOne (['candidate_id' => $source->candidate_id])
             )->null();

             expect ('Transfer moved to destination',
                 TransferCandidate::findOne (['candidate_id' => $destination->candidate_id])
             )->notNull();

             //make sure work history moved

             expect ('Candidate Work History Removed For Source',
                 CandidateWorkHistory::findOne (['candidate_id' => $source->candidate_id])
             )->null();

             expect ('Candidate Work History Added in Destination',
                 CandidateWorkHistory::findOne (['candidate_id' => $destination->candidate_id])
             )->notNull();

             //make sure old candidate, candidate_token, candidate_id_card, candidate_skill, candidate_experience deleted

             expect ('Candidate deleted',
                 Candidate::findOne (['candidate_id' => $source->candidate_id])
             )->null();

             expect ('Source Candidate Token deleted',
                 CandidateToken::findOne (['candidate_id' => $source->candidate_id])
             )->null ();

             expect ('Source Candidate ID card deleted',
                 CandidateIdCard::findOne (['candidate_id' => $source->candidate_id])
             )->null ();

             expect ('Source Candidate Skill deleted',
                 CandidateSkill::findOne (['candidate_id' => $source->candidate_id])
             )->null ();

             expect ('Source Candidate Experience deleted',
                 CandidateExperience::findOne (['candidate_id' => $source->candidate_id])
             )->null ();
         //});
    }*/

    /**
     * test case to check
     * white space in fields
     */
    public function testCaseForTrimFields() {
        //$this->specify('Test case for space validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_uid =  '110011001100';
            $candidate->store_id =   1;
            $candidate->university_id =   1;
            $candidate->country_id =   1;
            $candidate->bank_account_name =  '       Akshay Bhatia        ';
            $candidate->candidate_iban =  '        IBAN123400        ';
            $candidate->candidate_name =  'Akshay Bhatia';
            $candidate->candidate_name_ar =  'أكشاي باتيا';
            $candidate->candidate_personal_photo =  'photos/photo-1497874516406.png';
            $candidate->candidate_email =  'candidate111@bawes.net';
            $candidate->candidate_phone =   '989898989898';
            $candidate->candidate_address_line1 =  '106, BHAYLI CANAL RD';
            $candidate->candidate_birth_date =  '1992-11-11';
            $candidate->candidate_civil_id =  'XIS2222121';
            $candidate->candidate_civil_expiry_date =   date('Y-m-d', strtotime('+1 month'));
            $candidate->candidate_civil_photo_front =  'photos/photo-1497874516406.png';
            $candidate->candidate_civil_photo_back =  'photos/photo-1497874516406.png';
            $candidate->candidate_hourly_rate =   1.7;
            $candidate->candidate_auth_key =  'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6';
            $candidate->candidate_password_hash =   \Yii::$app->getSecurity()->generatePasswordHash('123456');
            $candidate->candidate_password_reset_token =   NULL;
            $candidate->candidate_status =   1;
            $candidate->approved =   1;
            $candidate->candidate_created_at =  '2017-02-23 19:53:20';
            $candidate->candidate_updated_at =  '2017-02-23 19:53:20';
            $candidate->currency_code = "KWD";

            $this->assertEquals(strlen($candidate->candidate_iban),26, 'expect string length of candidate_iban with space');
            $this->assertEquals(strlen($candidate->bank_account_name),28, 'expect string length of bank_account_name with space');

            $candidate->validate();

            $this->assertEquals(strlen($candidate->candidate_iban),10, 'not expect string length of candidate_iban with space');
            $this->assertEquals(strlen($candidate->bank_account_name),13, 'not expect string length of bank_account_name with space');

            $this->assertEquals(strlen($candidate->candidate_iban),10, 'expect string length of candidate_iban with trim');
            $this->assertEquals(strlen($candidate->bank_account_name),13, 'expect string length of bank_account_name with trim');
        //});
    }

    /*
     * tet case for migration query
     * is also working fine
     *
    public function testCaseForMigrationCommand() {
        //$this->specify('check if fixture loaded', function() {
            expect_that(Candidate::findOne(7));
        //});

        //$this->specify('checking is space data available', function() {

            $Candidate5Data = Candidate::findOne(5);
            $Candidate6Data = Candidate::findOne(6);
            $Candidate7Data = Candidate::findOne(7);

            expect('str 25',(strlen($Candidate5Data->candidate_iban) == 25))->true();
            expect('str 18',(strlen($Candidate5Data->bank_account_name) == 18))->true();

            expect('str 24',(strlen($Candidate6Data->candidate_iban) == 24))->true();
            expect('str 15',(strlen($Candidate6Data->bank_account_name) == 15))->true();

            expect('str 16',(strlen($Candidate7Data->candidate_iban) == 16))->true();
            expect('str 11',(strlen($Candidate7Data->bank_account_name) == 11))->true();

        //});

        //$this->specify('checking is space remove from available data after migration commands run', function() {

            Yii::$app->db->createCommand("UPDATE `candidate` set `candidate_iban` = TRIM(`candidate_iban`)")->execute();
            Yii::$app->db->createCommand("UPDATE `candidate` set `bank_account_name` = TRIM(`bank_account_name`)")->execute();

            $Candidate5Data = Candidate::findOne(5);
            $Candidate6Data = Candidate::findOne(6);
            $Candidate7Data = Candidate::findOne(7);

            expect('str 18',(strlen($Candidate5Data->bank_account_name) == 18))->false();
            expect('str 25',(strlen($Candidate5Data->candidate_iban) == 25))->false();

            expect('after trim str 5',(strlen($Candidate5Data->bank_account_name) == 5))->true();
            expect('after trim str 10',(strlen($Candidate5Data->candidate_iban) == 10))->true();


            expect('str 24',(strlen($Candidate6Data->candidate_iban) == 24))->false();
            expect('str 15',(strlen($Candidate6Data->bank_account_name) == 15))->false();

            expect('after trim str 20',(strlen($Candidate6Data->candidate_iban) == 20))->true();
            expect('after trim str 5',(strlen($Candidate6Data->bank_account_name) == 5))->true();


            expect('str 16',(strlen($Candidate7Data->candidate_iban) == 16))->false();
            expect('str 11',(strlen($Candidate7Data->bank_account_name) == 11))->false();

            expect('after trim str 9',(strlen($Candidate7Data->candidate_iban) == 9))->true();
            expect('after trim str 5',(strlen($Candidate7Data->bank_account_name) == 5))->true();
        //});
    }*/
}
