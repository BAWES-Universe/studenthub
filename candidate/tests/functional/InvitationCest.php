<?php

namespace candidate\tests;

use common\fixtures\CandidateTokenFixture;
use common\fixtures\InvitationFixture;
use common\fixtures\CandidateFixture;
use common\fixtures\RequestFixture;
use Codeception\Util\HttpCode;
use common\models\Candidate;

class InvitationCest
{
    public $token;
    public $invitation;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'invitation' => InvitationFixture::className (),
            'candidate' => CandidateFixture::className (),
            'request' => RequestFixture::className (),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $candidate = Candidate::find()->one();

        $this->token = $candidate->getAccessToken()
            ->token_value;

        $this->invitation = $candidate->getInvitations()
            ->one();

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
     * Try to log view timestamp by email
     * @param FunctionalTester $I
     *
    public function tryToLogEmail(FunctionalTester $I)
    {
        $I->wantTo('log email view timestamp via API');
        $I->sendGET('v1/invitations/log/' . $this->invitation->invitation_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }*/

    public function tryToLogAppView(FunctionalTester $I)
    {
        $I->wantTo('log app view timestamp via API');
        $I->sendGET('v1/invitations/log-viewed');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to accept invitation
     * @param FunctionalTester $I
     */
    public function tryToAccept(FunctionalTester $I)
    {
        $I->wantTo('accept invitation via API');
        $I->sendPATCH('v1/invitations/accept/' . $this->invitation->invitation_uuid, [
            'reason' => 'Okay can go with this'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to reject invitation
     * @param FunctionalTester $I
     */
    public function tryToReject(FunctionalTester $I)
    {
        $I->wantTo('reject invitation via API');
        $I->sendPATCH('v1/invitations/reject/' . $this->invitation->invitation_uuid, [
            'reason' => 'Nah can not go with this'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}

