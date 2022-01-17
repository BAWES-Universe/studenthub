<?php

namespace staff\tests;

use common\fixtures\ContactFixture;
use common\fixtures\StaffFixture;
use common\models\Request;
use staff\models\Staff;
use staff\tests\FunctionalTester;
use common\models\Company;
use common\models\StaffToken;
use common\models\Note;
use common\fixtures\StaffTokenFixture;
use common\fixtures\RequestFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\CompanyFixture;
use Codeception\Util\HttpCode;


class RequestCest
{
    public $token, $company;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className(),
            'staff' => StaffFixture::className(),
            'request' => RequestFixture::className(),
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contact' => ContactFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $this->company = Company::find()->one();

        $this->request = $this->company->getRequests()->one();

        $this->contact = $this->company->getCompanyContacts()->one();
        
        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate request api response for listing');
        $I->sendGET('v1/requests');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * api to check if request updated
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToCheckRequestUpdated(FunctionalTester $I)
    {
        $I->wantTo('Check if request updated');
        $I->sendGET('v1/requests/is-request-updated/' . $this->request->request_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate request api to view request detail');
        $I->sendGET('v1/requests/' . $this->request->request_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new request
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a request via API');

        $I->sendPOST(
            'v1/requests',
            [
               	'company_id' => $this->company->company_id,
                'contact_uuid' => $this->contact->contact_uuid,
                'position_type' => 1,//full time
                'position_title' => 'Android developer',
                'number_of_employees' => 1,
                'job_description' => 'Autem.',
                'compensation' => 'Dolor.',
                'additional_info' => 'la la lala la'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a request via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/requests/' . $this->request->request_uuid,
            [
            	'company_id' => $this->company->company_id,
                'contact_uuid' => $this->contact->contact_uuid,
                'position_type' => 1,//full time
                'position_title' => 'Android developer',
                'number_of_employees' => 1,
                'job_description' => 'Autem.',
                'compensation' => 'Dolor.',
                'additional_info' => 'la la lala  la'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to cancel 
     * @param FunctionalTester $I
     */
    public function tryToCancel(FunctionalTester $I)
    {
        $I->wantTo('cancel request via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/requests/cancel/' . $this->request->request_uuid, [
        	'feedback' => 'Lorem isuem...'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * Try to deliver 
     * @param FunctionalTester $I
     */
    public function tryToDeliver(FunctionalTester $I)
    {
        $I->wantTo('deliver request via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/requests/deliver/' . $this->request->request_uuid, [
        	'feedback' => 'Lorem isuem...'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    public function tryToListActive(FunctionalTester $I)
    {
        $I->wantTo('Validate request api response for listing pending');
        $I->sendGET('v1/requests/active');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * try to add activity to request
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToAddActivity(FunctionalTester $I)
    {
        $this->request->request_status = Request::STATUS_STARTED;
        $this->request->save();

        $I->wantTo('add activity to list');

        $I->sendPOST(
            'v1/requests/add-activity',
            [
                'request_uuid' => $this->request->request_uuid,
                'contact_uuid' => $this->contact->contact_uuid,
                'note_type' => Note::TYPE_INTERNAL_NOTE,
                'detail' => 'Test note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * api to list request checklist
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToListRequestChecklist(FunctionalTester $I)
    {
        $I->wantTo('Check if can list checklist');
        $I->sendGET('v1/requests/list-checklist');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * api to list pending request
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToListPendingRequest(FunctionalTester $I)
    {
        $I->wantTo('Check if can list pending request');
        $I->sendGET('v1/requests/pending-request');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * api to update followup interval
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToUpdateInterval(FunctionalTester $I)
    {
        $I->wantTo('Check if can update followup interval');
        $I->sendPATCH('v1/requests/update-interval/' . $this->request->request_uuid, [
            'hours' => 1,
            'reason' => 'test'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * api to assign staff
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToAssign(FunctionalTester $I)
    {
        $staff = Staff::find()->one();

        $I->wantTo('Check if can assign staff');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/requests/assign/' . $this->request->request_uuid, [
            'staff_id' => $staff->staff_id
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}

