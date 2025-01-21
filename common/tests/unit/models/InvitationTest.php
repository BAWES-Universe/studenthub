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
            'invitation' => InvitationFixture::class,
            'story' => StoryFixture::class,
            'candidate' => CandidateFixture::class,
        ];
    }

    protected function _before(){}

    protected function _after(){}

    /**
     * Test Validation
     */
    public function testValidate()
    {
        //$this->specify('Fixtures should be loaded', function() {
            $this->assertNotNull(Candidate::find()->one());
            $this->assertNotNull(Invitation::find()->one());
        //});

        $data = new Invitation();
        $data->request_uuid = null;
        $this->assertFalse($data->validate(['request_uuid']));

        $data = new Invitation();
        $data->request_uuid = 'test';
        $this->assertFalse($data->validate(['request_uuid']));

        $data = new Invitation();
        $data->request_uuid = Request::find()->one()->request_uuid;
        $data->candidate_id = Candidate::find()->one()->candidate_id;
        $this->assertTrue($data->validate(['candidate_id']));
        $this->assertTrue($data->validate(['request_uuid']));

        $data->story_uuid = 12313;
        $data->invitation_status = 'test';

        $this->assertFalse($data->validate(['story_uuid']));
        $this->assertFalse($data->validate(['invitation_status']));
    }
}
