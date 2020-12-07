<?php
namespace common\tests;

use common\fixtures\CompanyFixture;
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
            'companyContact' => CompanyFixture::className()
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

            $model->contact_name = null;
            $model->contact_position = null;

            expect('companyContact contact_name should be required field', $model->validate(['contact_name']))->false();
            expect('companyContact contact_position should be required field', $model->validate(['contact_position']))->false();

            $model->company_id = '123123123';
            expect('Invalid Company id', $model->validate(['company_id']))->false();

        });
    }
}
