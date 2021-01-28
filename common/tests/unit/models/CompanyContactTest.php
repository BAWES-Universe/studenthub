<?php
namespace common\tests;

use common\fixtures\CompanyContactFixture;
use common\models\CompanyContact;
use Codeception\Specify;



class CompanyContactTest extends \Codeception\Test\Unit
{
     use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'companyContact' => CompanyContactFixture::className()
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Company Contact is in the table',
                CompanyContact::find()->one()
            )->notNull();
        });

        $this->specify('Field validation', function() {
                
            $model = new CompanyContact;

            $model->company_id = '123123123';
            expect('Invalid Company id', $model->validate(['company_id']))->false();

            $model->contact_uuid = '123123123';
            expect('Invalid Contact id', $model->validate(['contact_uuid']))->false();

            //company_id + contact_uuid should be unique combo

            //try to add same value

            $companyContact = CompanyContact::find()->one();

            $model->company_id = $companyContact->company_id;
            $model->contact_uuid = $companyContact->contact_uuid;
            
            expect('Invalid Company id', $model->validate(['company_id']))->false();
            //expect('Invalid Contact id', $model->validate(['contact_uuid']))->false();

            //try to add different value

            $model->company_id = $companyContact->company_id;
            $model->contact_uuid = $companyContact->contact_uuid;

            CompanyContact::deleteAll ([
                'company_contact_uuid' => $companyContact->company_contact_uuid
            ]);

            expect('Valid Company id', $model->validate(['company_id']))->true();
            expect('Valid Contact id', $model->validate(['contact_uuid']))->true();
        });
    }
}
