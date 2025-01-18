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
            'companyContact' => CompanyContactFixture::class
        ];
    }

    protected function _before(){}

    protected function _after() {}

    public function testValidate()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(CompanyContact::find()->one());
        //});

        //$this->specify('Field validation', function() {
                
            $model = new CompanyContact;

            $model->company_id = '123123123';
            $this->assertFalse($model->validate(['company_id']));

            $model->contact_uuid = '123123123';
            $this->assertFalse($model->validate(['contact_uuid']));

            //company_id + contact_uuid should be unique combo

            //try to add same value

            $companyContact = CompanyContact::find()->one();

            $model->company_id = $companyContact->company_id;
            $model->contact_uuid = $companyContact->contact_uuid;
            
            $this->assertFalse($model->validate(['company_id']));
            //$this->assertFalse($model->validate(['contact_uuid']));

            //try to add different value

            $model->company_id = $companyContact->company_id;
            $model->contact_uuid = $companyContact->contact_uuid;

            CompanyContact::deleteAll ([
                'company_contact_uuid' => $companyContact->company_contact_uuid
            ]);

            $this->assertTrue($model->validate(['company_id']));
            $this->assertTrue($model->validate(['contact_uuid']));
        //});
    }
}
