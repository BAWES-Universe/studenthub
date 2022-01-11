<?php


namespace common\tests;

use Codeception\Specify;
use common\fixtures\StoryFixture;
use common\models\Story;
use common\models\StoryActivity;


class StoryActivityTest extends \Codeception\Test\Unit
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
            $model = new StoryActivity();

            expect('should not accept empty story_uuid', $model->validate(['story_uuid']))->false();

            $model->staff_id = 'test';
            expect('should not accept random string for staff_id', $model->validate(['staff_id']))->false();

            $model->staff_id = 999;
            expect('should not accept invalid value for staff_id', $model->validate(['staff_id']))->false();
        });
    }
}