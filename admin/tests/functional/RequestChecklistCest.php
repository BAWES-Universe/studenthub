<?php

namespace admin\tests;

use admin\models\AdminToken;
use common\models\RequestChecklist;
use common\fixtures\RequestChecklistFixture;
use common\fixtures\AdminTokenFixture;
use admin\tests\FunctionalTester;
use Codeception\Util\HttpCode;

class RequestChecklistCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::class,
            'requestChecklist' => RequestChecklistFixture::class
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List countries
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $model = RequestChecklist::find()->one();

        $I->wantTo('Validate admin > request checklist api response for listing');
        $I->sendGET('v1/request-checklists');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'request_checklist_uuid' => $model->request_checklist_uuid
        ]);
    }

    /**
     * view country list
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $model = RequestChecklist::find()->one();

        $I->wantTo('Validate admin > request-checklist api response for view');
        $I->sendGET('v1/request-checklists/' . $model->request_checklist_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            'request_checklist_uuid' => $model->request_checklist_uuid
        ]);
    }

    /**
     * Create
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('add a request-checklist via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/request-checklists',
            [
                'status_name' => 'davert',
                'status_name_ar' => 'davert',
                'is_require' => 0,
                'sort_order' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
        ]);
    }

    /**
     * Update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $model = RequestChecklist::find()->one();

        $I->wantTo('update a request-checklist via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/request-checklists/' . $model->request_checklist_uuid,
            [
                'status_name' => 'davert',
                'status_name_ar' => 'davert',
                'is_require' => 0,
                'sort_order' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
        ]);
    }

    /**
     * @param \admin\tests\FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $model = RequestChecklist::find()->one();

        $I->wantTo('Validate request-checklist > delete api response');
        $I->sendDELETE('v1/request-checklists/' . $model->request_checklist_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        /*$I->seeResponseContainsJson([
            "operation" => "success",
        ]);*/
    }
}