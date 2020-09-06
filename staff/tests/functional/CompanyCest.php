<?php
namespace staff\tests;

use yii;
use staff\tests\FunctionalTester;
use common\models\StaffToken;
use common\fixtures\StaffTokenFixture;
use common\fixtures\CompanyFixture;
use Codeception\Util\HttpCode;

class CompanyCest
{
    public $token;

	public function _fixtures()
	{
		return [
                    'company' => CompanyFixture::className(),
			'staffToken' => StaffTokenFixture::className()
		];
	}

	public function _before(FunctionalTester $I)
	{
        $this->token = StaffToken::find()
            ->one()
            ->token_value;

        $I->amBearerAuthenticated($this->token);
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
}
