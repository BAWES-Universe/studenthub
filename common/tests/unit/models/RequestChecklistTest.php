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
            'requestChecklist' => RequestChecklistFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        /*$this->specify('Fixtures should be loaded', function() {
            expect('Check data loaded',
                RequestChecklist::find()->one()
            )->notNull();
        });*/

        $this->specify('model fields validation', function () {
            $model = new RequestChecklist();

            expect('should not accept empty status_name', $model->validate(['status_name']))->false();
        });
    }
}
