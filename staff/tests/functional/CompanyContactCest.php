<?php

namespace staff\tests;

use staff\tests\FunctionalTester;
use common\models\CompanyContact;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\CompanyContactFixture;
use Codeception\Util\HttpCode;


class CompanyContactCest
{
    public $token, $contact_uuid = 1;

    public function _fixtures()
    {
        return [
        	'staffToken' => StaffTokenFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = StaffToken::find()
            ->one()
            ->token_value;
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate company contact api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/company-contacts');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $model = CompanyContact ::find()->one();
        
        $I->wantTo('Validate company contact api to view company contact detail');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/company-contacts/' . $model->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * Try to create new bank
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a company contact via API');
        $I->amBearerAuthenticated($this->token);
        $I->sendPOST(
            'v1/company-contacts',
            [
                'name' => 'davert',
                'position' => 'Java developer',
                'note' => 'Spring specialist',
                'company_id' => '1',
                'emails' => [
                    [
                        'email_address' => 'demo@demo.com'
                    ]
                ],
                'phones' => [
                    [
                        'phone_number' => '12345678'
                    ]
                ]
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
        $I->wantTo('update a company contact via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/company-contacts/' . $this->contact_uuid,
            [
                'name' => 'davert',
                'position' => 'Java developer',
                'note' => 'Spring specialist',
                'company_id' => '1',
                'emails' => [
                    [
                        'email_address' => 'demo@demo.com'
                    ]
                ],
                'phones' => [
                    [
                        'phone_number' => '12345678'
                    ]
                ]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success"
        ]);
    }

    /**
     * Try to delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete company contact via API');
        $I->amBearerAuthenticated($this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/company-contacts/' . $this->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
