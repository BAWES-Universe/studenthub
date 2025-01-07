<?php
namespace common\tests\models;

use common\models\Fulltimer;
use common\models\FulltimerTags;
use common\fixtures\FulltimerTagsFixture;
use Codeception\Specify;


class FulltimerTagsTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures(){
        return ['fulltimerTags' => FulltimerTagsFixture::class];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(FulltimerTags::find()->one());
        //});

        //$this->specify('model fields validation', function () {
            $model = new FulltimerTags();
            $this->assertFalse($model->validate(['tag']));
        //});
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        //$this->specify('Create New Fulltimer Tag', function () {
            
            $fulltimer = Fulltimer::find()->one();

            $model = new FulltimerTags();
            $model->tag = 'BigBazar';
            $model->fulltimer_uuid  = $fulltimer->fulltimer_uuid;
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['fulltimer_uuid' => $fulltimer->fulltimer_uuid, 'tag' => 'BigBazar']));
        //});
    }
}
