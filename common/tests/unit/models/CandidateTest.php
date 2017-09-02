<?php
namespace common\tests;

use Yii;
use common\models\Store;
use common\models\Candidate;
use common\fixtures\CandidateFixture;
use common\fixtures\CountryFixture;
use common\fixtures\UniversityFixture;
use common\fixtures\StoreFixture;
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

        $this->specify('Candidate beneficiary name and IBAN validation for special characters', function() {
            $candidate = new Candidate;
            $candidate->bank_account_name = '???????';
            $candidate->candidate_iban = '???????';
            expect('Bank account name should not contain special characters', $candidate->validate(['bank_account_name']))->false();
            expect('Candidate IBAN should not contain special characters', $candidate->validate(['candidate_iban']))->false();
            $candidate->bank_account_name = 'Manmohan';
            $candidate->candidate_iban = 'IBAN123456';
            expect('Bank account name should accept valid value', $candidate->validate(['bank_account_name']))->true();
            expect('Candidate IBAN should accept valid value', $candidate->validate(['candidate_iban']))->true();
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
            $candidate->bank_id =   1;
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
     */
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
    }
}
