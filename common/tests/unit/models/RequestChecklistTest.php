<?php


namespace common\tests;


use Codeception\Specify;
use common\fixtures\RequestChecklistFixture;
use common\models\RequestChecklist;


class RequestChecklistTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'requestChecklist' => RequestChecklistFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        /*//$this->specify('Fixtures should be loaded', function() {
            expect('Check data loaded',
                RequestChecklist::find()->one()
            )->notNull();
        //});*/

        //$this->specify('model fields validation', function () {
            $model = new RequestChecklist();

            $this->assertFalse($model->validate(['status_name']));
        //});
    }
}
