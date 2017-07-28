<?php
namespace common\tests;

use Codeception\Specify;
use common\models\Company;
use common\models\CompanyToken;
use common\models\Store;
use common\fixtures\CompanyToken as CompanyTokenFixture;
use common\fixtures\Company as CompanyFixture;
use common\fixtures\Store as StoreFixture;

class CompanyTokenTest extends \Codeception\Test\Unit
{
    use Specify;
    
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => codecept_data_dir() . 'company.php'
            ],
            'companyToken' => [
                'class' => CompanyTokenFixture::className(),
                'dataFile' => codecept_data_dir() . 'companyToken.php'
            ],
            'store' => [
                'class' => StoreFixture::className(),
                'dataFile' => codecept_data_dir() . 'store.php'
            ]
        ]);
    }

    protected function _after(){}

    // tests
    public function testValidation()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Staff Token is in the table', CompanyToken::findOne(['company_id'=>'1']))->notNull();
        });


        $this->specify('Test Validator', function() {
            $model = new CompanyToken();
            $model->validate();
            expect('company_id required error',$model->errors)->hasKey('company_id');
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
            expect('Company Token is in the table', CompanyToken::findOne(['company_id'=>'1']))->notNull();
        });


        $this->specify('Test existing Token', function() {
            expect('unique token string',strlen(CompanyToken::generateUniqueTokenString()))->greaterThan(31);
        });

        $this->specify('relation testing', function() {
            expect('relative data testing', CompanyToken::findOne(['company_id'=>'1'])->getCompany()->one()->company_email)->equals($this->tester->grabFixture('company', 0)->company_email);
        });
    }
}