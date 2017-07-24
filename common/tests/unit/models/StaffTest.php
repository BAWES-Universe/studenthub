<?php

use common\fixtures\Staff as StaffFixture;

class StaffTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;


    protected function _before()
    {
        $this->tester->haveFixtures([
            'staff' => [
                'class' => StaffFixture::className(),
                'dataFile' => codecept_data_dir() . 'staff.php'
            ]
        ]);
    }

    protected function _after()
    {
    }

    public function testValidateData()
    {
        $table = $this->tester->grabFixture('staff', 0);

        expect('New Record', $table->save())->true();

        $table->staff_name = null;
        expect('staff_name Validating', $table->validate(['staff_name']))->false();

        $table->staff_email = null;
        expect('staff_email Validating', $table->validate(['staff_email']))->false();

    }
}
