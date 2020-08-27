<?php
namespace admin\tests;

use Yii;
use admin\tests\FunctionalTester;
use common\fixtures\CompanyFixture;
use common\fixtures\AdminTokenFixture;
use common\models\AdminToken;
use Codeception\Util\HttpCode;


class CompanyCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'company' => CompanyFixture::className()
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
        $I->seeResponseCodeIs(HttpCode::OK);  
        $I->seeResponseIsJson();
    }
    
    /**
     * list companies to followups
     * @param FunctionalTester $I
     */
    public function tryToListFollowups(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > companies api response for followups listing');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGET('v1/companies/followups');
        $I->seeResponseCodeIs(HttpCode::OK);  
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
        $I->seeResponseCodeIs(HttpCode::OK);  
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
        $I->seeResponseCodeIs(HttpCode::OK);  
        $I->seeResponseIsJson();
    }

    /**
     * create company account
     * @param FunctionalTester $I
     */
    public function tryToCreateCompany(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->wantTo('create a company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPOST(
            'v1/companies',
            [
                'name' => 'davert',
                'common_name_en' => 'test',
                'common_name_ar' => 'test',
                'logo' => basename($response['ObjectURL']),
                'description_en' => 'test',
                'description_ar' => 'TEST',
                'website' => 'test.com',
                'email' => 'davert@bawes.com',
                'password' => '12345',
                'bonus_commission' => 20,
                'hourly_rate' => 1.5
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);  
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
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->wantTo('create a sub company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST(
            'v1/companies',
            [
                'name' => 'davert',
                'common_name_en' => 'test',
                'common_name_ar' => 'test',
                'description_en' => 'test',
                'logo' => basename($response['ObjectURL']),
                'description_ar' => 'TEST',
                'website' => 'test.com',
                'parent' => 1,
                'bonus_commission' => 20,
                'hourly_rate' => 1.5
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);  
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
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->wantTo('update company via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH(
            'v1/companies/1',
            [
                'name' => 'davert',
                'common_name_en' => 'test',
                'common_name_ar' => 'test',
                'description_en' => 'test',
                'description_ar' => 'TEST',
                'logo' => basename($response['ObjectURL']),
                'website' => 'test.com',
                'email' => 'davert@bawes.com',
                'bonus_commission' => 20,
                'hourly_rate' => 1.5
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);  
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully updated"
        ]);
    } 
        
    /**
     * Add company file
     * @param FunctionalTester $I
     */
    public function tryToAddCompanyFile(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->wantTo('add company file via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST('v1/companies/file-create/1', [
            'file_title' => 'some-cripy-file',
            'file_description' => 'la la lalalala llala',
            'file_s3_path' => basename($response['ObjectURL'])
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); 
    }
    
    /**
     * Update company file
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyFile(FunctionalTester $I)
    {
        $I->wantTo('update company file via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/file-update/1', [
            'file_title' => 'some-cripy-file',
            'file_description' => 'la la lalalala llala'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); 
    }
    
    /**
     * Reset company password
     * @param FunctionalTester $I
     */
    public function tryToResetCompanyPassword(FunctionalTester $I)
    {
        $I->wantTo('reset company password via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/reset-password/2', [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); 
    }
    
    /**
     * Update company status
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyStatus(FunctionalTester $I)
    {
        $I->wantTo('update company status via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/change-status/2', [
            'status' => 10
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); 
    }
    
    /**
     * Update company followup
     * @param FunctionalTester $I
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
     * Delete company file
     * @param FunctionalTester $I
     */
    public function tryToDeleteCompanyFile(FunctionalTester $I)
    {
        $I->wantTo('delete company file via admin > companies API');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDELETE('v1/companies/remove-file/1');
        $I->seeResponseCodeIs(HttpCode::OK); 
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
        $I->seeResponseCodeIs(HttpCode::OK); 
    }
}
