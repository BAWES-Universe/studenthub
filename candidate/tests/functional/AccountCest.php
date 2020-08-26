<?php
namespace candidate\tests;

use yii;
use candidate\tests\FunctionalTester;
use candidate\models\CandidateToken;
use common\fixtures\CandidateTokenFixture;
use common\fixtures\TransferCandidateFixture;
use common\fixtures\InvoiceFixture;
use Codeception\Util\HttpCode;


class AccountCest
{
    public $token;

    public function _fixtures()
    {
        return [
            'candidateToken' => CandidateTokenFixture::className(),
            'transferCandidate' => TransferCandidateFixture::className(),
            'invoice' => InvoiceFixture::className()
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->token = CandidateToken::find()->one()->token_value;
        
        $I->amBearerAuthenticated($this->token);
    }

    public function _after(FunctionalTester $I){}

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function SalaryMethodTest(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Salary Method response');
        $I->sendGET('v1/account/salary');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(['transfer_id'=>5,'total'=>27]);
    }
    
    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function tryToGetProfile(FunctionalTester $I)
    {
        $I->amGoingTo('Validate account > profile api');
        $I->sendGET('v1/account/profile');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function tryToGetJobStatus(FunctionalTester $I)
    {
        $I->amGoingTo('Validate account > job-search-status api');
        $I->sendGET('v1/account/job-search-status');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }
    
    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function tryToUpdateJobStatus(FunctionalTester $I)
    {
        $I->amGoingTo('Validate account > job-search-status api');
        $I->sendPOST('v1/account/job-search-status', [
            'job_search_status' => 1
        ]);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
    }

    /**
     * @param \candidate\tests\FunctionalTester $I
     */
    public function validatePassword(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password with empty fields');
        $I->sendPOST('v1/account/change-password', array('old_password' => '', 'new_password' => ''));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty old password"]);
    }

    public function tryNewPasswordEmpty(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password with new password empty field');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123', 'new_password' => ''));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty new password"]);
    }

    public function tryOldPasswordEmpty(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password with old password empty field');
        $I->sendPOST('v1/account/change-password', array('old_password' => '', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Empty old password"]);
    }

    public function trySamePassword(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password for same old and new password');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "New password should not be same as old password"]);
    }

    public function tryInvalidOldPassword(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password for 123456');
        $I->sendPOST('v1/account/change-password', array('old_password' => '123123123', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Invalid Old Password"]);
    }

    public function tryInvalidPasswordLength(FunctionalTester $I)
    {
        $I->amGoingTo('Validate Change Password for new password length');
        $I->sendPOST('v1/account/change-password', array('old_password' => '12345', 'new_password' => '123'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "New password length should be great then equal to 5"]);
    }

    public function tryValidPassword(FunctionalTester $I)
    {
        $I->amGoingTo('Successful test for change password');
        $I->sendPOST('v1/account/change-password', array('old_password' => '12345', 'new_password' => '123456'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(["message" => "Password changed successfully!"]);
    }
    
    public function tryUpdateEmail(FunctionalTester $I)
    {
        $I->amGoingTo('try to update email');
        $I->sendPOST('v1/account/update-email', array('email' => 'demo@demo.com'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateLanguagePref(FunctionalTester $I)
    {
        $I->amGoingTo('try to update language preference');
        $I->sendPOST('v1/account/language-pref', array('language_pref' => 'en'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateName(FunctionalTester $I)
    {
        $I->amGoingTo('try to update name');
        $I->sendPOST('v1/account/update-name', array('name' => 'Bilal'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateNameAR(FunctionalTester $I)
    {
        $I->amGoingTo('try to update name - arabic');
        $I->sendPOST('v1/account/update-name-ar', array('name_ar' => 'Bilal'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateCivilId(FunctionalTester $I)
    {
        $I->amGoingTo('try to update civil id');
        $I->sendPOST('v1/account/update-civil-id', array('civil_id' => '123456789012'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateNationality(FunctionalTester $I)
    {
        $I->amGoingTo('try to update nationality');
        $I->sendPOST('v1/account/update-nationality', array('country_id' => '1'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateUniversity(FunctionalTester $I)
    {
        $I->amGoingTo('try to update university');
        $I->sendPOST('v1/account/update-university', array('university_id' => '1'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateDrivingLicense(FunctionalTester $I)
    {
        $I->amGoingTo('try to update driving license');
        $I->sendPOST('v1/account/update-driving-license', array('driving_license' => '214UWEA1'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateGender(FunctionalTester $I)
    {
        $I->amGoingTo('try to update gender');
        $I->sendPOST('v1/account/update-gender', array('gender' => '1'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateObjective(FunctionalTester $I)
    {
        $I->amGoingTo('try to update objective');
        $I->sendPOST('v1/account/update-objective', array('objective' => 'Who knows!'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateResume(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.pdf',
            [],
            codecept_data_dir() . 'files/sample.pdf',
            'application/pdf'
        );
        
        $I->amGoingTo('try to update resume');
        $I->sendPOST('v1/account/update-resume', array('resume' => $response['Key']));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateBirthDate(FunctionalTester $I)
    {
        $I->amGoingTo('try to update birth date');
        $I->sendPOST('v1/account/update-birth-date', array('birth_date' => '1992-01-01'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateProfilePhoto(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->amGoingTo('try to update profile photo');
        $I->sendPOST('v1/account/profile-photo', array('personal_photo' => $response['Key']));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateSkills(FunctionalTester $I)
    {
        $I->amGoingTo('try to update skills');
        $I->sendPOST('v1/account/update-skills', array('skills' => 'office,telly'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateExperiences(FunctionalTester $I)
    {
        $I->amGoingTo('try to update experiences');
        $I->sendPOST('v1/account/update-experiences', array('experiences' => 'office,telly'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateBankDetail(FunctionalTester $I)
    {
        $I->amGoingTo('try to update bank detail');
        $I->sendPOST(
            'v1/account/update-bank-detail', 
            array(
                'benef_name' => 'Karasandas',
                'iban' => '123456789012345678901234567890'
            )
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdatePhone(FunctionalTester $I)
    {
        $I->amGoingTo('try to update phone');
        $I->sendPOST(
            'v1/account/update-phone', 
            array(
                'phone' => '12341234'
            )
        );
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryRemoveCivilPhotoFront(FunctionalTester $I)
    {
        $I->amGoingTo('try to remove civil photo front');
        $I->sendDELETE('v1/account/remove-civil-photo-front');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryRemoveCivilPhotoBack(FunctionalTester $I)
    {
        $I->amGoingTo('try to remove civil photo back');
        $I->sendDELETE('v1/account/remove-civil-photo-back');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryRemovePhoto(FunctionalTester $I)
    {
        $I->amGoingTo('try to remove photo');
        $I->sendDELETE('v1/account/remove-photo');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateCivilExpiryDate(FunctionalTester $I)
    {
        $I->amGoingTo('try to update civil expiry date');
        $I->sendPOST('v1/account/update-civil-expiry-date', array('civil_expiry_date' => '1992-01-01'));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateCivilPhotoBack(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->amGoingTo('try to update civil photo back');
        $I->sendPOST('v1/account/update-civil-photo-back', array('civil_photo_back' => $response['Key']));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
    
    public function tryUpdateCivilPhotoFront(FunctionalTester $I)
    {
        $response = Yii::$app->temporaryBucketResourceManager->save(
            null,
            'sample.jpg',
            [],
            codecept_data_dir() . 'files/sample.jpg',
            'image/jpg'
        );
        
        $I->amGoingTo('try to update civil photo front');
        $I->sendPOST('v1/account/update-civil-photo-front', array('civil_photo_front' => $response['Key']));
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
