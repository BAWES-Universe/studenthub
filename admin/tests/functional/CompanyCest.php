<?php
namespace admin\tests;

use common\models\Company;
use Yii;
use admin\tests\FunctionalTester;
use common\fixtures\CompanyFixture;
use common\fixtures\AdminTokenFixture;
use common\fixtures\FileFixture;
use common\models\AdminToken;
use common\models\File;
use Codeception\Util\HttpCode;


class CompanyCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'adminToken' => AdminTokenFixture::className(),
            'company' => CompanyFixture::className(),
            'files' => FileFixture::className()
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
     * list companies
     * @param FunctionalTester $I
     */
    public function tryToList(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('Validate admin > companies api response for listing');
        $I->sendGET('v1/companies');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "company_id" => $company->company_id
        ]);
    }
    
    /**
     * list companies to followups
     * @param FunctionalTester $I
     */
    public function tryToListFollowups(FunctionalTester $I)
    {
        $I->wantTo('Validate admin > companies api response for followups listing');
        $I->sendGET('v1/companies/followups');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    /**
     * view company
     * @param FunctionalTester $I
     */
    public function tryToView(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('Validate admin > companies api response for company detail');
        $I->sendGET('v1/companies/'.$company->company_id);
        $I->seeResponseCodeIs(HttpCode::OK);  
        $I->seeResponseContainsJson([
            "company_id" => $company->company_id
        ]);
    }

    /**
     * List Sub Companies for a given company
     * @param FunctionalTester $I
     */
    public function tryToListSubCompanies(FunctionalTester $I)
    {
        $company = Company::findOne('parent_company_id NOT NULL');
        $I->wantTo('Validate admin > companies api to list sub companies for a given company');
        $I->sendGET('v1/companies/sub-companies/'.$company->company_id);
        $I->seeResponseCodeIs(HttpCode::OK);  
        $I->seeResponseContainsJson([
            "company_id" => $company->company_id
        ]);
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
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPOST('v1/companies/file-create/1', [
            'file_title' => 'some-cripy-file',
            'file_description' => 'la la lalalala llala',
            'file_s3_path' => basename($response['ObjectURL'])
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company document uploaded successfully"
        ]);
    }
    
    /**
     * Update company file
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyFile(FunctionalTester $I)
    {
        $file = File::find()->one();
        
        $I->wantTo('update company file via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/file-update/' . $file->file_uuid, [
            'file_title' => 'some-cripy-file',
            'file_description' => 'la la lalalala llala'
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company document data updated successfully"
        ]);
    }
    
    /**
     * Reset company password
     * @param FunctionalTester $I
     */
    public function tryToResetCompanyPassword(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('reset company password via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/reset-password/'.$company->company_id, [
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ]);
    }
    
    /**
     * Update company status
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyStatus(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('update company status via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/change-status/'.$company->company_id, [
            'status' => 10
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account status changed successfully"
        ]);
    }
    
    /**
     * Update company followup
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyFollowup(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('update company followup via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/update-followup/'.$company->company_id, [
            'followup' => true
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account followup status changed successfully"
        ]);
    }
    
    /**
     * Update company followup
     * @param FunctionalTester $I
     */
    public function tryToUpdateCompanyFollowupInterval(FunctionalTester $I)
    {
        $company = Company::find()->one();
        $I->wantTo('update company followup interval in week via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPATCH('v1/companies/update-followup-interval/'.$company->company_id, [
            'followup_interval_weeks' => 4
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account followup interval changed successfully"
        ]);
    }
    
    /**
     * Delete company file
     * @param FunctionalTester $I
     */
    public function tryToDeleteCompanyFile(FunctionalTester $I)
    {
        $file = File::find()->one();
        
        $I->wantTo('delete company file via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDELETE('v1/companies/remove-file/' . $file->file_uuid);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company Document successfully deleted"
        ]);
    }
    
    /**
     * Delete company
     * @param FunctionalTester $I
     */
    public function tryToDeleteCompany(FunctionalTester $I)
    {
        $company = Company::findOne('company_id IN (select company_id where company_id != parent_company_id)');
        $I->wantTo('delete company via admin > companies API');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendDELETE('v1/companies/2');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson([
            "operation" => "success",
            "message" => "Company account successfully updated"
        ]);
    }
}
