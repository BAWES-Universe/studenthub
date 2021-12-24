<?php

namespace staff\tests;

use common\models\Invitation;
use common\models\Request;
use common\models\Candidate;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\InvitationFixture;
use common\fixtures\CandidateFixture;
use Codeception\Util\HttpCode;


class InvitationCest
{
    public $token;
    public $invitation;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className (),
            'invitation' => InvitationFixture::className (),
            'candidate' => CandidateFixture::className (),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find ()
            ->one ()
            ->token_value;

        $this->invitation = Invitation::find ()->one ();

        $I->amBearerAuthenticated ($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo ('Validate invitation api response for listing');
        $I->sendGET ('v1/invitations');
        $I->seeResponseCodeIs (HttpCode::OK); // 200
        $I->seeResponseIsJson ();
    }

    /**
     * Try to create new invitation
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $request = Request::find ()
            ->andWhere (['request_status' => Request::STATUS_STARTED])
            ->one ();

        $candidate = Candidate::find ()->one ();

        $I->wantTo ('create a invitation via API');
        $I->sendPOST (
            'v1/invitations',
            [
                'request_uuid' => $request->request_uuid,
                'candidate_id' => $candidate->candidate_id,
                'reason' => 'test'
            ]
        );
        $I->seeResponseCodeIs (HttpCode::OK); // 200
        /*$I->seeResponseContainsJson ([
            "operation" => "success"
        ]);*/
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo ('delete invitation via API');
        $I->sendDelete ('v1/invitations/' . $this->invitation->invitation_uuid);
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }
}

