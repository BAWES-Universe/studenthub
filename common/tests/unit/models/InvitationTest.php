<?php
namespace common\tests;

use Codeception\Specify;
use common\fixtures\InvitationFixture;
use common\fixtures\StoryFixture;
use common\fixtures\CandidateFixture;
use common\models\Candidate;
use common\models\Invitation;
use common\models\Request;

class InvitationTest extends \Codeception\Test\Unit
{
    use Specify;

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'invitation' => InvitationFixture::className(),
            'story' => StoryFixture::className(),
            'candidate' => CandidateFixture::className(),
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        $this->specify('Fixtures should be loaded', function() {
            expect('Candidate is in the table', Candidate::find()->one())->notNull();
            expect('Invitatio is in the table', Invitation::find()->one())->notNull();
        });

        $data = new Invitation();
        $data->request_uuid = null;
        expect('invitation request_uuid should be required field', $data->validate(['request_uuid']))->false();

        $data = new Invitation();
        $data->request_uuid = 'test';
        expect('invitation request_uuid should be Valid', $data->validate(['request_uuid']))->false();

        $data = new Invitation();
        $data->request_uuid = Request::find()->one()->request_uuid;
        $data->candidate_id = Candidate::find()->one()->candidate_id;
        expect('invitation candidate_id is Valid', $data->validate(['candidate_id']))->true();
        expect('invitation candidate_id is Valid', $data->validate(['request_uuid']))->true();

        $data->story_uuid = 12313;
        $data->invitation_status = 'test';

        expect('invitation story_uuid should be Valid', $data->validate(['story_uuid']))->false();
        expect('invitation invitation_status should be Valid', $data->validate(['invitation_status']))->false();
    }
}
