<?php
namespace common\tests;

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
            'candidates' => CandidateFixture::className(),
            'country'    => CountryFixture::className(),
            'bank'    => BankFixture::className(),
            'university' => UniversityFixture::className(),
            'store'      => StoreFixture::className(),
            'transfer'      => TransferFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'workHistory' => CandidateWorkHistoryFixture::className(),
            'accessToken' => CandidateTokenFixture::className(),
            'candidateIdCards' => CandidateIdCardFixture::className(),
            'candidateSkills' => CandidateSkillFixture::className(),
            'candidateExperience' => CandidateExperienceFixture::className(),
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
            expect('Unibersity ID should be required field', $candidate->validate(['university_id']))->false();
            expect('Country ID should be required field', $candidate->validate(['country_id']))->false();
//            expect('Candidate name should be required field', $candidate->validate(['candidate_name']))->false();
//            expect('Candidate name - Arabic should be required field', $candidate->validate(['candidate_name_ar']))->false();
            expect('Candidate email should be required field', $candidate->validate(['candidate_email']))->false();
            expect('Candidate phone should be required field', $candidate->validate(['candidate_phone']))->false();
            expect('Candidate birth date should be required field', $candidate->validate(['candidate_birth_date']))->false();
            expect('Candidate civil ID should be required field', $candidate->validate(['candidate_civil_id']))->false();
            expect('Candidate civil id expiry date should be required field', $candidate->validate(['candidate_civil_expiry_date']))->false();
            expect('Candidate civil photo front date should be required field', $candidate->validate(['candidate_civil_photo_front']))->false();
            expect('Candidate civil photo back should be required field', $candidate->validate(['candidate_civil_photo_back']))->false();
           // expect('Candidate hourly rate should be required field', $candidate->validate(['candidate_hourly_rate']))->false();
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

            $candidate->candidate_gender = 'test';
            expect('String value passed for candidate_gender', $candidate->validate(['candidate_gender']))->false();

            $candidate->candidate_gender = 1;
            expect('Valid value passed for candidate_gender', $candidate->validate(['candidate_gender']))->true();
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
            //$candidate->candidate_phone = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
            //expect('Too long value passed for candidate_phone', $candidate->validate(['candidate_phone']))->false();
        });

        $this->specify('Candidate model foreign key validation', function() {
            $candidate = new Candidate;

            //bank
            $bank = $this->tester->grabFixture('bank', 0);

            $candidate->bank_id = 9999;
            expect('Invalid Bank ID passed', $candidate->validate(['bank_id']))->false();

            $candidate->bank_id = $bank->bank_id;
            expect('Valid Bank ID passed', $candidate->validate(['bank_id']))->true();

            //country
            $country = $this->tester->grabFixture('country', 0);

            $candidate->country_id = 9999;
            expect('Invalid Country ID passed', $candidate->validate(['country_id']))->false();
            $candidate->country_id = $country->country_id;
            expect('Valid Country ID passed', $candidate->validate(['country_id']))->true();

            //university
            $univesity = $this->tester->grabFixture('university', 0);

            $candidate->university_id = 9999;
            expect('Invalid University ID passed', $candidate->validate(['university_id']))->false();
            $candidate->university_id = $univesity->university_id;
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
            expect('Invalid value passed', $candidate->validate(['candidate_hourly_rate']))->false();
            $candidate->candidate_hourly_rate = $max + 1;
            expect('Higher than max allowed value passed', $candidate->validate(['candidate_hourly_rate']))->false();
            $candidate->candidate_hourly_rate = $max;
            expect('Valid Hourly rate passed', $candidate->validate(['candidate_hourly_rate']))->true();
        });

        $this->specify('Candidate email validation', function() {
            $candidateData = Candidate::findOne(['deleted'=>'0']);

            $candidate = new Candidate;

            $candidate->candidate_email = $candidateData->candidate_email;
            expect('Duplicate email passed', $candidate->validate(['candidate_email']))->false();

            $candidate->candidate_email = 'test';
            expect('Random string passed', $candidate->validate(['candidate_email']))->false();

            $candidate->candidate_email = 'candidate1@unique.net';
            expect('Valid email passed', $candidate->validate(['candidate_email']))->true();

            //candidate_new_email

            $candidate->candidate_new_email = $candidateData->candidate_email;
            expect('Duplicate new email passed', $candidate->validate(['candidate_new_email']))->false();

            $candidate->candidate_new_email = 'test';
            expect('Random string passed for candidate new email ', $candidate->validate(['candidate_new_email']))->false();

            $candidate->candidate_new_email = 'candidate2@unique.net';
            expect('Valid new email passed', $candidate->validate(['candidate_new_email']))->true();

        });

        $this->specify('Candidate civil id validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_civil_id = '54747771714';
            expect('Duplicate candidate_civil_id passed', $candidate->validate(['candidate_civil_id']))->false();

            $candidate->candidate_civil_id = '241397002346';
            expect('Valid candidate_civil_id passed', $candidate->validate(['candidate_civil_id']))->true();
        });

        $this->specify('Candidate candidate civil expiry date validation', function() {
            $candidate = new Candidate;
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('-1 day'));
            expect('Invalid value passed', $candidate->validate(['candidate_civil_expiry_date']))->false();
            $candidate->candidate_civil_expiry_date = date('Y-m-d', strtotime('+1 day'));
            expect('Valid value passed', $candidate->validate(['candidate_civil_expiry_date']))->true();

        });

        $this->specify('Candidate beneficiary name and IBAN validation for special characters', function() {
            $candidate = new Candidate;
            $candidate->bank_account_name = '???????';
            $candidate->candidate_iban = '???????';
            expect('Bank account name should not contain special characters', $candidate->validate(['bank_account_name']))->false();
            expect('Candidate IBAN should not contain special characters', $candidate->validate(['candidate_iban']))->false();
            $candidate->bank_account_name = 'Manmohan Kumar';
            $candidate->candidate_iban = 'KWKW12345612345612345612345612';
            expect('Bank account name should accept valid value', $candidate->validate(['bank_account_name']))->true();
            expect('Candidate IBAN should accept valid value', $candidate->validate(['candidate_iban']))->true();
        });
    }

    public function testAccountMerge()
    {
        $this->specify ('Merge source account to target', function () {

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
             $destination->candidate_updated_at =  '2017-02-23 19:53:20';
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
         });
    }

    /**
     * test case to check
     * white space in fields
     */
    public function testCaseForTrimFields() {
        $this->specify('Test case for space validation', function() {
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

            expect('expect string length of candidate_iban with space',strlen($candidate->candidate_iban))->equals(26);
            expect('expect string length of bank_account_name with space',strlen($candidate->bank_account_name))->equals(28);

            $candidate->validate();

            expect('not expect string length of candidate_iban with space',(strlen($candidate->candidate_iban) == 26))->false();
            expect('not expect string length of bank_account_name with space',(strlen($candidate->bank_account_name) == 28))->false();

            expect('expect string length of candidate_iban with trim',strlen($candidate->candidate_iban))->equals(10);
            expect('expect string length of bank_account_name with trim',strlen($candidate->bank_account_name))->equals(13);
        });
    }

    /*
     * tet case for migration query
     * is also working fine
     *
    public function testCaseForMigrationCommand() {
        $this->specify('check if fixture loaded', function() {
            expect_that(Candidate::findOne(7));
        });

        $this->specify('checking is space data available', function() {

            $Candidate5Data = Candidate::findOne(5);
            $Candidate6Data = Candidate::findOne(6);
            $Candidate7Data = Candidate::findOne(7);

            expect('str 25',(strlen($Candidate5Data->candidate_iban) == 25))->true();
            expect('str 18',(strlen($Candidate5Data->bank_account_name) == 18))->true();

            expect('str 24',(strlen($Candidate6Data->candidate_iban) == 24))->true();
            expect('str 15',(strlen($Candidate6Data->bank_account_name) == 15))->true();

            expect('str 16',(strlen($Candidate7Data->candidate_iban) == 16))->true();
            expect('str 11',(strlen($Candidate7Data->bank_account_name) == 11))->true();

        });

        $this->specify('checking is space remove from available data after migration commands run', function() {

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
        });
    }*/
}
