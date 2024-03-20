<?php
namespace staff\tests;

use yii;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\CompanyFixture;
use Codeception\Util\HttpCode;
use common\fixtures\CandidateFixture;
use common\fixtures\StoreFixture;


class CompanyCest
{
    public $token;

	public function _fixtures()
	{
		return [
                'company' => CompanyFixture::className(),
                'staffToken' => StaffTokenFixture::className(),
                'store' => StoreFixture::className(),
                'candidate' => CandidateFixture::className(),
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);

        $I->haveHttpHeader("Currency", "KWD");
    }

    public function _after(FunctionalTester $I)
    {
    }

    /**
     * List companies
     * @param FunctionalTester $I
     */
    public function tryToListing(FunctionalTester $I)
    {
        $I->wantTo('get Company listing');
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * List followups companies
     * @param FunctionalTester $I
     */
    public function tryToListFollowups(FunctionalTester $I)
    {
        $I->wantTo('get companies require followups');
        $I->sendGET('v1/companies/followups');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * View company
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $I->wantTo('get company detail');
        $I->sendGET('v1/companies/1');
        $I->seeResponseCodeIs(HttpCode::OK);
    }
    
    /**
     * Add company file
     * @param FunctionalTester $I
     */
    public function tryToAddFile(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );

        $I->wantTo('add company file');
        $I->sendPOST('v1/companies/file-create/1', [
            'file_title' => 'Test',
            'file_description' => 'Lorem isum...',
            'file_s3_path' => basename($response['ObjectURL'])
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Add company followup note
     * @param FunctionalTester $I
     */
    public function tryToAddNote(FunctionalTester $I)
    {
        $I->wantTo('add followup note');
        $I->sendPOST('v1/companies/add-followup-note/1', [
            'note' => 'Test'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Update company followup
     * @param \admin\tests\FunctionalTester $I
     */
    public function tryToUpdateCompanyFollowup(FunctionalTester $I)
    {
        $I->wantTo('update company followup via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/update-followup/2', [
            'followup' => true
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Update company followup
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyFollowupInterval(FunctionalTester $I)
    {
        $I->wantTo('update company followup interval in week via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/update-followup-interval/2', [
            'followup_interval_weeks' => 4
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * Add company
     * @param FunctionalTester $I
     */
    public function tryToAddCompany(FunctionalTester $I)
    {
        $I->wantTo('add company');
        $I->sendPOST('v1/companies', [
            'email' => 'Test@parent.com',
            'name' => 'test',
            'hourly_rate' => 2,
            'bonus_commission' => 0,
            'common_name_en' => 'test',
            'common_name_ar' => 'test',
            'description_en' => 'test',
            'description_ar' => 'test',
            'website' => 'me@me.com',
            'logo' => null,
            'followup' => false,
            'followup_interval_weeks' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * try to update company
     * @param \staff\tests\FunctionalTester $I
     */
    public function tryToUpdateCompany(FunctionalTester $I)
    {
        $I->wantTo('add company');
        $I->sendPATCH('v1/companies/1', [
            'email' => 'Test@parent2.com',
            'name' => 'test',
            'hourly_rate' => 2,
            'bonus_commission' => 0,
            'common_name_en' => 'test',
            'common_name_ar' => 'test',
            'description_en' => 'test',
            'description_ar' => 'test',
            'website' => 'me@me.com',
            'logo' => null,
            'followup' => false,
            'followup_interval_weeks' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
    }
}

