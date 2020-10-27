<?php

namespace staff\tests;

use staff\tests\FunctionalTester;
use common\models\Company;
use common\models\StaffToken;
use common\models\CompanyContact;
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
            'request' => RequestFixture::className(),
            'contact' => CompanyContactFixture::className(),
            'company' => CompanyFixture::className(),
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
                'additional_info' => 'la la lala  la'
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
                'additional_info' => 'la la lala  la'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to start 
     * @param FunctionalTester $I
     */
    public function tryToStart(FunctionalTester $I)
    {
        $I->wantTo('start request via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/requests/start/' . $this->request->request_uuid, []);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
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
}

