<?php
namespace company\tests;

use company\models\Company;
use company\tests\FunctionalTester;
use Yii;
use company\models\CompanyToken;
use common\fixtures\CompanyFixture;
use common\fixtures\ContactTokenFixture;
use Codeception\Util\HttpCode;


class AccountCest
{
    public $token, $model;

    public function _before(FunctionalTester $I)
    {
        Yii::$app->params['inCodeception'] = true;
        Yii::$app->params['transfer_cost'] = 0.35;

        $this->model = Company::findOne(1);
        $this->token = $this->model->accessToken->token_value;

        $I->amBearerAuthenticated($this->token);
    }

    public function _fixtures()
    {
        return [
            'company' => CompanyFixture::className (),
            'contactToken' => ContactTokenFixture::className ()
        ];
    }
    
    //HR and Owner should able to list parent company transfer

    //HR and Owner should not able to list child company transfer

    //Other and Finance should not able list transfers
}
