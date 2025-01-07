<?php
namespace common\tests\models;

use Yii;
use common\models\Suggestion;
use common\fixtures\SuggestionFixture;
use Codeception\Specify;
use common\models\Request;
use common\models\Staff;
use common\models\Fulltimer;
use common\models\Note;


class SuggestionTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures() {
        return [
            'suggestion' => SuggestionFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Suggestion::find()->one());
        //});

        //$this->specify('model fields validation', function () {
            $model = new Suggestion();
         
            $this->assertFalse($model->validate(['request_uuid']));
            $this->assertFalse($model->validate(['candidate_id']));
            $this->assertFalse($model->validate(['note_uuid']));

            $model->suggestion_status = 9999999;

            //$model->validate();

            $this->assertFalse($model->validate(['suggestion_status']));
        //});
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        //$this->specify('Create New', function () {

            $request = Request::find()->where(['request_status' => Request::STATUS_STARTED])->one();
        
            $fulltimer = Fulltimer::find()->one();

            //create note 

            $note = new Note;
            $note->company_id = $request->company_id;
            $note->request_uuid = $request->request_uuid;
            $note->fulltimer_uuid = $fulltimer->fulltimer_uuid;
            $note->note_type = Note::TYPE_SUGGESTED;
            $note->note_text = 'Test model';
            $note->save();

            $this->assertTrue($note->save());

            $model = new Suggestion();
            $model->request_uuid = $request->request_uuid;
            $model->fulltimer_uuid = $fulltimer->fulltimer_uuid;
            $model->note_uuid = $note->note_uuid;
          
            $this->assertTrue($model->save());
            $this->assertNotNull($model->findOne(['note_uuid' => $note->note_uuid]));
        //});
    }
}
