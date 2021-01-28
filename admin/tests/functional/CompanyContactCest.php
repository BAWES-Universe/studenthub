<?php

namespace admin\tests;

use admin\tests\FunctionalTester;
use common\models\CompanyContact;
use common\models\Contact;
use common\models\AdminToken;
use common\fixtures\AdminFixture;
use common\fixtures\CompanyContactFixture;
use common\fixtures\ContactFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\CompanyFixture;
use Codeception\Util\HttpCode;


class CompanyContactCest
{
    public $token, $contact_uuid = 1;

    public function _fixtures()
    {
        return [
            'admin' => AdminFixture::className(),
            'adminToken' => AdminTokenFixture::className(),
            'company' => CompanyFixture::className(),
            'companyContact' => CompanyContactFixture::className(),
            'contact' => ContactFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;

        $this->contact_uuid = CompanyContact::find()->one()->contact_uuid;

        $I->amBearerAuthenticated($this->token);
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $model = CompanyContact ::find()->one();
        $I->wantTo('Validate company contact api response for listing');
        $I->amBearerAuthenticated($this->token);
        $I->sendGET('v1/company-contacts');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'contact_uuid' => $model->contact_uuid
        ]);
    }

    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $model = Contact ::find()->one();

        $I->wantTo('Validate company contact api to view company contact detail');
        $I->sendGET('v1/company-contacts/' . $model->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'contact_uuid' => $model->contact_uuid
        ]);
    }

    /**
     * View company contact detail
     * @param FunctionalTester $I
     */
    public function tryToViewCompanyContact(FunctionalTester $I)
    {
        $model = CompanyContact ::find()->one();
        
        $I->wantTo('Validate company contact api to view company contact detail');
        $I->sendGET('v1/company-contacts/view-company-contact' . $model->company_contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'company_contact_uuid' => $model->company_contact_uuid
        ]);
    }

    /**
     * Try to create new bank
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a company contact via API');
        $I->sendPOST(
            'v1/company-contacts',
            [
                'name' => 'davert',
                'email' => 'ravan@lanka.com',
                'company_id' => '1',
                'role' => 'Owner',
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
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/company-contacts/' . $this->contact_uuid,
            [
                'name' => 'davert',
                'company_id' => '1',
                'email' => 'ravan@lanka.com',
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
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToCheckEmailExists(FunctionalTester $I)
    {
        $contact = Contact::find()->one();

        $I->wantTo('check email availability via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendGET('v1/company-contacts/is-email-exists?email=' . $contact->contact_email);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "contact_uuid" => $contact->contact_uuid
        ]);
    }

    /**
     * Try to add contact to team
     * @param FunctionalTester $I
     */
    public function tryToAddToTeam(FunctionalTester $I)
    {
        $subQuery = CompanyContact::find()
            ->select('contact_uuid')
            ->filterWhere(['company_id' => 1])
            ->all();

        $contact = Contact::find()
            ->filterWhere(['NOT IN', 'contact_uuid', $subQuery])
            ->one();

        $I->wantTo('add contact to team via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/company-contacts/add-to-team',
            [
                'contact_position' => 'Owner',
                'allow_access' => 1,
                'contact_uuid' => $contact->contact_uuid,
                'company_id' => '1'
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
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/company-contacts/' . $this->contact_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company Contact deleted successfully"
        ]);
    }
}
