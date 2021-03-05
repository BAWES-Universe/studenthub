<?php

namespace company\tests;

use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\SuggestionFixture;
use common\models\Suggestion;
use company\models\Contact;
use company\models\Request;


class SuggestionCest
{
    public $company;

    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
            'suggestion' => SuggestionFixture::className (),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $this->request = Request::find ()
            ->filterWhere (['company_id' => $this->company->company_id])
            ->one ();

        $this->suggestion = Suggestion::find ()
            ->filterWhere (['request_uuid' => $this->request->request_uuid])
            ->one ();

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Try to list
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo ('List suggestion via API');
        $I->sendGET ('v1/suggestions');
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }

    /**
     * Try to view suggestion
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo ('View suggestion via API');
        $I->sendGET ('v1/suggestions/' . $this->suggestion->suggestion_uuid);
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }
}