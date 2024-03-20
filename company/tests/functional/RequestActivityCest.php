<?php


namespace company\tests;


use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use common\fixtures\RequestFixture;
use company\models\Contact;
use company\models\Request;


class RequestActivityCest
{
    public $company;

    public function _fixtures() {
        return [
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contactToken' => ContactTokenFixture::className(),
            'candidate'    => CandidateFixture::className(),
            'request' => RequestFixture::className (),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->contact = Contact::find()->one();

        $this->token = $this->contact->getAccessToken()
            ->token_value;

        $this->company = $this->contact->getManagedCompanies()->one();

        $this->request = Request::find ()
            ->andWhere (['company_id' => $this->company->company_id])
            ->one ();

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    /**
     * Try to list activities
     * @param FunctionalTester $I
     */
    public function tryToListActivities(FunctionalTester $I)
    {
        $I->wantTo ('List request activities via API');
        $I->sendGET ('v1/request-activity/request-activities/' . $this->request->request_uuid);
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }
}