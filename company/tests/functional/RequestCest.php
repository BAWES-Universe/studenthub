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


class RequestCest
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
    }

    /**
     * Try to list
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo ('List request via API');
        $I->sendGET ('v1/requests');
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }

    public function tryToListActiveRequests(FunctionalTester $I)
    {
        $I->wantTo ('List request via API');
        $I->sendGET ('v1/requests/active');
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }

    /**
     * Try to view request
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo ('View request via API');
        $I->sendGET ('v1/requests/' . $this->request->request_uuid);
        $I->seeResponseCodeIs (HttpCode::OK); // 200
    }

    /**
     * Try to create request
     * @param FunctionalTester $I
     */
//    public function tryToCreate(FunctionalTester $I)
//    {
//        $I->wantTo ('Create request via API');
//        $I->sendPOST ('v1/requests', [
//            "position_type" => '1',
//            "position_title" => 'Android developer',
//            "number_of_employees" => 10,
//            "location" => 'OP Road',
//            "additional_info" => 'Add minue...',
//            "job_description" => 'detail about job',
//            "compensation" => '100 USD per day'
//        ]);
//        $I->seeResponseCodeIs (HttpCode::OK); // 200
//    }
//
//    /**
//     * Try to update request
//     * @param FunctionalTester $I
//     */
//    public function tryToUpdateRequest(FunctionalTester $I)
//    {
//        $I->wantTo ('Update request via API');
//        $I->sendPATCH ('v1/requests/' . $this->request->request_uuid, [
//            "position_type" => '1',
//            "position_title" => 'Android developer',
//            "number_of_employees" => 10,
//            "location" => 'OP Road',
//            "additional_info" => 'Add minue...',
//            "job_description" => 'detail about job',
//            "compensation" => '100 USD per day'
//        ]);
//        $I->seeResponseCodeIs (HttpCode::OK); // 200
//    }
//
//    /**
//     * Try to get request count
//     * @param FunctionalTester $I
//     */
//    public function tryToGetRequestCount(FunctionalTester $I)
//    {
//        $I->wantTo ('Get request count via API');
//        $I->sendGET('v1/requests/count');
//        $I->seeResponseCodeIs (HttpCode::OK); // 200
//    }
//
//    /**
//     * Try to check if request updated
//     * @param FunctionalTester $I
//     */
//    public function tryToCheckIfRequestUpdated(FunctionalTester $I)
//    {
//        $I->wantTo ('Check is request updated via API');
//        $I->sendGET('v1/requests/is-request-updated/' . $this->request->request_uuid);
//        $I->seeResponseCodeIs (HttpCode::OK); // 200
//    }
}
