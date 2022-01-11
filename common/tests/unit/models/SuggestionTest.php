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
            'suggestion' => SuggestionFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after() { }

    /**
     * Tests validator
     */
    public function testValidators()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Check suggestion loaded',
                Suggestion::find()->one()
            )->notNull();
        });

        $this->specify('model fields validation', function () {
            $model = new Suggestion();
         
            expect('should not accept empty request_uuid', $model->validate(['request_uuid']))->false();
            expect('should not accept empty candidate_id', $model->validate(['candidate_id']))->false();
            expect('should not accept empty note_uuid', $model->validate(['note_uuid']))->false();

            $model->suggestion_status = 9999999;

            //$model->validate();

            expect('should not accept random string for suggestion_status', $model->validate(['suggestion_status']))->false();
        });
    }

    /**
     * Tests Create, Update
     */
    public function testCrud()
    {
        $this->specify('Create New', function () {

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

            expect('Note created successfully', $note->save())->true();

            $model = new Suggestion();
            $model->request_uuid = $request->request_uuid;
            $model->fulltimer_uuid = $fulltimer->fulltimer_uuid;
            $model->note_uuid = $note->note_uuid;
          
            expect('Created successfully', $model->save())->true();
            expect('Record is in database', $model->findOne(['note_uuid' => $note->note_uuid]))->notNull();
        });
    }
}
