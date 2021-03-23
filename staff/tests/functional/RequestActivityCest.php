<?php

namespace staff\tests;

use Codeception\Util\HttpCode;
use common\fixtures\CompanyFixture;
use common\fixtures\NoteFixture;
use common\fixtures\RequestFixture;
use common\fixtures\StaffTokenFixture;
use common\models\Company;
use common\models\StaffToken;


class RequestActivityCest
{
    public $token, $company;

    public function _fixtures()
    {
        return [
            'staffToken' => StaffTokenFixture::className(),
            'request' => RequestFixture::className(),
            'note' => NoteFixture::className(),
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

        $I->amBearerAuthenticated($this->token);
    }

    public function tryListRequestActivites(FunctionalTester $I)
    {
        $I->amGoingTo('try to list request notes');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/request-activity/request-activities/' . $this->request->request_uuid);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
}
