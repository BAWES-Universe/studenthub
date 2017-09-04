<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use admin\fixtures\CompanyFixture;
use common\fixtures\AdminFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use Codeception\Util\HttpCode;

class CompanyCest
{
    public $token;

	public function _fixtures()
	{
        return [
            'admin' => [
                'class' => AdminFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/admin.php'
            ],
            'adminToken' => [
                'class' => AdminTokenFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/adminToken.php'
            ],
            'company' => [
                'class' => CompanyFixture::className(),
                'dataFile' => Yii::getAlias('@common').'/tests/_data/company.php'
            ]
        ];
	}

	public function _before(FunctionalTester $I)
	{
		$this->token = AdminToken::find()
            ->one()
            ->token_value;
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * list companies
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > companies api response for listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * view company
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > companies api response for company detail');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/companies/1');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * List Sub Companies for a given company
     * @param FunctionalTester $I
     */
    public function tryToListSubCompanies(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > companies api to list sub companies for a given company');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/companies/sub-companies/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    /**
     * create company account
     * @param FunctionalTester $I
     */
    public function tryToCreateCompany(FunctionalTester $I)
    {
        $I->wantTo('create a company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/companies',
            [
                'name' => 'davert',
                'email' => 'davert@bawes.com',
                'password' => '12345',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully created"
        ]);
    }

    /**
     * create sub company
     * @param FunctionalTester $I
     */
    public function tryToCreateSubCompany(FunctionalTester $I)
    {
        $I->wantTo('create a sub company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/companies',
            [
                'name' => 'davert',
                'parent' => 1
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully created"
        ]);
    }

    /**
     * update company
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompany(FunctionalTester $I)
    {
        $I->wantTo('update company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/companies/1',
            [
                'name' => 'davert',
                'email' => 'davert@bawes.com'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully updated"
        ]);
    }

    /**
     * Delete company
     * @param FunctionalTester $I
     */
    public function tryToDeleteCompany(FunctionalTester $I)
    {
        $I->wantTo('delete company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDELETE('v1/companies/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully deleted"
        ]);
    }
}
