<?php


namespace common\tests;


use Codeception\Specify;
use common\fixtures\StoryFixture;
use common\models\Story;

class StoryTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'story' => StoryFixture::className(),
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
            expect('Check story loaded',
                Story::find()->one()
            )->notNull();
        });*/

        $this->specify('model fields validation', function () {
            $model = new Story();

            expect('should not accept empty request_uuid', $model->validate(['request_uuid']))->false();
            //expect('should not accept empty suggestion_uuid', $model->validate(['suggestion_uuid']))->false();

            $model->suggestion_uuid = 'test';
            expect('should not accept random string for suggestion_uuid', $model->validate(['suggestion_uuid']))->false();

            $model->suggestion_uuid = 999;
            expect('should not accept invalid value for suggestion_uuid', $model->validate(['suggestion_uuid']))->false();

            $model->request_uuid = 'test';
            expect('should not accept random string for request_uuid', $model->validate(['request_uuid']))->false();

            $model->request_uuid = 999;
            expect('should not accept invalid value for request_uuid', $model->validate(['request_uuid']))->false();

            $model->staff_id = 'test';
            expect('should not accept random string for staff_id', $model->validate(['staff_id']))->false();

            $model->staff_id = 999;
            expect('should not accept invalid value for staff_id', $model->validate(['staff_id']))->false();

            $model->story_status = 'story_status';
            expect('should not accept random string for story_status', $model->validate(['story_status']))->false();
        });
    }
}
