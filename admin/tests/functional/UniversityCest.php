<?php
namespace admin\tests;

use common\models\University;
use Yii;
use admin\tests\FunctionalTester;
use common\models\AdminToken;
use common\fixtures\AdminTokenFixture;
use common\fixtures\UniversityFixture;
use Codeception\Util\HttpCode;


class UniversityCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'university' => UniversityFixture::className(),
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = AdminToken::find()
            ->one()
            ->token_value;
        $I->amBearerAuthenticated($this->token);

    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $university = University::find()->one();
        $I->wantTo('Validate university api response for listing');
        $I->sendGET('v1/universities');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "university_id" => $university->university_id,
        ]);
    }
    
    /**
     * Listing
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $university = University::find()->one();
        $I->wantTo('Validate university api response for detail');
        $I->sendGET('v1/universities/'.$university->university_id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "university_id" => $university->university_id,
        ]);
    }

    /**
     * Create
     * @param FunctionalTester $I
     */
    public function tryToCreate(FunctionalTester $I)
    {
        $I->wantTo('create a university via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/universities',
            [
                'name_en' => 'davert',
                'name_ar' => 'davert'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University created successfully"
        ]);
    }

    /**
     * Update
     * @param FunctionalTester $I
     */
    public function tryToUpdate(FunctionalTester $I)
    {
        $I->wantTo('update a university via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/universities/1',
            [
                'name_en' => 'davert',
                'name_ar' => 'davert'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University successfully updated"
        ]);
    }

    /**
     * Delete
     * @param FunctionalTester $I
     */
    public function tryToDelete(FunctionalTester $I)
    {
        $I->wantTo('delete university via API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDelete('v1/universities/2');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "University deleted successfully"
        ]);
    }
}
