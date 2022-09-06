<?php

namespace company\tests;


use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\InvitationFixture;
use common\models\Candidate;
use common\models\Invitation;
use common\models\Request;
use company\models\Contact;


class InvitationCest
{
    public $company;

    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
            'invitation' => InvitationFixture::className (),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->invitation = Invitation::find ()->one ();

        $this->company = $this->contact->getManagedCompanies()->one();

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Try to create new invitation
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $request = Request::find ()->andWhere ([
            'request_status' => Request::STATUS_STARTED,
            'company_id' => $this->company->company_id
        ])->one ();

        $candidate = Candidate::find()->one ();

        $I->wantTo ('create a invitation via API');
        $I->sendPOST (
            'v1/request-candidate-invitation',
            [
                'request_uuid' => $request->request_uuid,
                'candidate_id' => $candidate->candidate_id
            ]
        );
        $I->seeResponseCodeIs (HttpCode::OK); // 200
        $I->seeResponseContainsJson ([
            "operation" => "success"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo ('delete invitation via API');
        $I->sendDelete ('v1/request-candidate-invitation/' . $this->invitation->invitation_uuid);
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }
}