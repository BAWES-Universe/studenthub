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
            'story' => StoryFixture::class,
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
            expect('Check story loaded',
                Story::find()->one()
            )->notNull();
        //});*/

        //$this->specify('model fields validation', function () {
            $model = new StoryActivity();

            $this->assertFalse($model->validate(['story_uuid']));

            $model->staff_id = 'test';
            $this->assertFalse($model->validate(['staff_id']));

            $model->staff_id = 999;
            $this->assertFalse($model->validate(['staff_id']));
        //});
    }
}