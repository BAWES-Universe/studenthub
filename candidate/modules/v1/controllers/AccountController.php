<?php

namespace candidate\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ArrayDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use candidate\models\Candidate;
use common\models\CandidateSkill;
use common\models\CandidateExperience;


/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }
    
    /**
     * return profile details
     */
    public function actionProfile() {
        
        return Yii::$app->user->identity;
    }
    
    /**
     * update candidate experiences
     * @return array
     */
    public function actionUpdateExperiences()
    {
        $experiences = Yii::$app->request->getBodyParam("experiences");

        $experiences = explode(',', $experiences);
        
        if (empty($experiences) || count($experiences) == 0) 
        {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Experiences Required")
            ];
        }
        
        CandidateExperience::deleteAll([
            'candidate_id' => Yii::$app->user->getId()
        ]);

        foreach ($experiences as $experience) {
            if (!empty($experience)) {
                $model = new CandidateExperience;
                $model->candidate_id = Yii::$app->user->getId();
                $model->experience = $experience;

                if(!$model->save()) {
                    return [
                        "operation" => "error",
                        "message" => $model->getErrors()
                    ];
                }
            }
        }
        
        $experienceList = CandidateExperience::find()
            ->where([
                'candidate_id' => Yii::$app->user->getId()
            ])    
            ->all();
        Yii::$app->user->identity->updateAlgoliaIndex(false);
        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Experiences updated successfully"),
            "experiences" => $experienceList
        ];
    }
    
    /**
     * update candidate skills
     * @return array
     */
    public function actionUpdateSkills()
    {
        $skills = Yii::$app->request->getBodyParam("skills");

        $skills_array = explode(',',$skills);
        
        if (empty($skills) || count($skills_array) == 0) 
        {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Skills Required")
            ];
        }
        
        CandidateSkill::deleteAll([
            'candidate_id' => Yii::$app->user->getId()
        ]);

        foreach ($skills_array as $skill) {
            if (!empty($skill)) {
                $model = new CandidateSkill;
                $model->candidate_id = Yii::$app->user->getId();
                $model->skill = $skill;

                if(!$model->save()) {
                    return [
                        "operation" => "error",
                        "message" => $model->getErrors()
                    ];
                }
            }
        }
        
        $skillList = CandidateSkill::find()
            ->where([
                'candidate_id' => Yii::$app->user->getId()
            ])    
            ->all(); 
        Yii::$app->user->identity->updateAlgoliaIndex(false);
        return [
            "operation" => "success",
            "message" => Yii::t('candidate',"Skills updated successfully"),
            "skills" => $skillList
        ];
    }
    
    /**
     * Remove Photo
     */
    public function actionRemovePhoto() {
        $model = Candidate::findOne(Yii::$app->user->getId());

        if ($model->candidate_personal_photo) {
            $model->deleteProfilePhotoFromCloudinary();
        }
        
        $model->candidate_personal_photo = null;
        $model->scenario = 'changeProfilePhoto';

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }

    /**
     * Update candidate email address 
     * @return type
     */
    public function actionUpdateEmail() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        $new_email = Yii::$app->request->getBodyParam("email");

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Candidate new email address required")
            ];
        }

        if ($new_email == $candidate->candidate_email || $new_email == $candidate->candidate_new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Candidate new email address is same as old email")
            ];
        }

        $candidate->scenario = "updateEmail";

        $candidate->candidate_new_email = $new_email;

        if ($candidate->save()) {

            $candidate->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('candidate', "Candidate Account Info Updated Successfully, please check email to verify new email address"),
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }
    }

    /**
     * Update candidate Bank detail
     * @return array
     */
    public function actionUpdateBankDetail() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        $benefName = Yii::$app->request->getBodyParam("benef_name");
        $iban = Yii::$app->request->getBodyParam("iban");

        if (!$benefName) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "Beneficiary Name is required")
            ];
        }

        if (!$iban) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', "IBAN Code is required")
            ];
        }

        $candidate->scenario = "updateBankDetail";

        $candidate->bank_account_name = $benefName;
        $candidate->candidate_iban = $iban;

        if ($candidate->save()) {

            Yii::$app->user->identity->updateAlgoliaIndex(false);
            return [
                "operation" => "success",
                "message" => Yii::t('candidate',"Bank details updated successfully"),
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }
    }

    /**
     * Set language preference 
     */
    public function actionLanguagePref() {
        $language_pref = Yii::$app->request->getBodyParam('language_pref');

        $model = Yii::$app->user->identity;
        $model->candidate_language_pref = $language_pref;

        $model->scenario = 'updateLanguagePref';

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }
    
    /**
     * return job status
     * @return type
     */
    public function actionGetJobSearchStatus() 
    {
        $model = Yii::$app->user->identity;
        
        return [
            'candidate_job_search_status' => (int) $model->candidate_job_search_status,
            'isProfileCompleted' => $model->isProfileCompleted(),
            'store' => $model->store,
            'company' => $model->company
        ];
    }
    
    /**
     * Set job search status
     */
    public function actionJobSearchStatus() {
        
        $job_search_status = Yii::$app->request->getBodyParam('job_search_status');

        $model = Candidate::findOne(Yii::$app->user->getId());
        $model->candidate_job_search_status = $job_search_status;

        $model->scenario = 'updateJobSearchStatus';

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }
    
    /**
     * Return a List of Salary transfers
     */
    public function actionSalary()
    {
        $currentUser = Yii::$app->user->identity;

        return new ArrayDataProvider([
            'allModels' => array_reverse($currentUser->paidTransferCandidate),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    /**
     * update change password
     * @return type
     */
    public function actionChangePassword()
    {
        $model = Yii::$app->user->identity;

        $oldPassword = Yii::$app->request->getBodyParam("old_password");
        $newPassword = Yii::$app->request->getBodyParam("new_password");

        if (empty($oldPassword)) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Empty old password")
            ];
        } else if (empty($newPassword)) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Empty new password")
            ];
        }

        if ($oldPassword === $newPassword) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"New password should not be same as old password")
            ];
        }

        if (!$model->validatePassword($oldPassword)) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Invalid Old Password")
            ];
        }

        if (strlen($newPassword) < 5) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"New password length should be great then equal to 5")
            ];
        }

        $model->scenario = 'changePassword';
        
        $model->setPassword($newPassword); 
        
        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->getErrors()
            ];
        }
        
        return [
            "operation" => "success",
            "message" => Yii::t('candidate',"Password changed successfully!")
        ];
    }
    
    /**
     * update nationality
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateNationality() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        $candidate->country_id = Yii::$app->request->getBodyParam('country_id');

        $candidate->scenario = "updateNationality";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Nationality Info Updated Successfully"),
        ];
    }
    
    /**
     * update candidate driving license
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateDrivingLicense() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_driving_license = Yii::$app->request->getBodyParam('driving_license');

        $candidate->scenario = "updateDrivingLicense";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Driving License Info Updated Successfully"),
        ];
    }
    
    /**
     * Update personal photo 
     */
    public function actionProfilePhoto() {
        $model = Yii::$app->user->identity;

        if ($model->candidate_personal_photo) {
            $model->deleteProfilePhotoFromCloudinary();
        }

        $model->candidate_personal_photo = urldecode(Yii::$app->request->getBodyParam('personal_photo'));

        if(!$model->candidate_personal_photo || $model->candidate_personal_photo == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Invalid input for {attribute}', [
                    'attribute' => 'profile photo'
                ])
            ];
        }

        if (!$model->updateProfilePhoto()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'candidate_personal_photo' => $model->candidate_personal_photo,
            'message' => Yii::t('job', 'Profile Photo Uploaded Successfully')
        ];
    }
    
    /**
     * update candidate name
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateName() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_name = Yii::$app->request->getBodyParam('name');

        $candidate->scenario = "updateName";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Name Info Updated Successfully"),
        ];
    }
    
    /**
     * update candidate name - arabic
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateNameAr() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_name_ar = Yii::$app->request->getBodyParam('name_ar');

        $candidate->scenario = "updateNameAr";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Name (Arabic) Info Updated Successfully"),
        ];
    }
    
    /**
     * update candidate civil id number
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateCivilId() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_civil_id = Yii::$app->request->getBodyParam('civil_id');

        $candidate->scenario = "updateCivilId";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Civil ID Info Updated Successfully"),
        ];
    }
    
    /**
     * update candidate objective
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateObjective() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_objective = Yii::$app->request->getBodyParam('objective');

        $candidate->scenario = "updateObjective";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Objective Info Updated Successfully"),
        ];
    }
    
    /**
     * update candidate gender
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateGender() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_gender = Yii::$app->request->getBodyParam('gender');

        $candidate->scenario = "updateGender";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Gender Info Updated Successfully"),
        ];
    }
    
    /**
     * candidate university
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateUniversity() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->university_id = Yii::$app->request->getBodyParam('university_id');

        $candidate->scenario = "updateUniversity";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate University Info Updated Successfully"),
        ];
    }
    
    /**
     * update resume
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateResume() {
        
        $model = Candidate::findOne(Yii::$app->user->getId());

        if (!$model) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
         
        if ($model->candidate_resume) {
            $this->deleteResume();
        }

        $model->scenario = "updateResume";
        
        $model->candidate_resume = urldecode(Yii::$app->request->getBodyParam('resume'));

        if(!$model->candidate_resume || $model->candidate_resume == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Invalid input for {attribute}', [
                    'attribute' => 'candidate resume'
                ])
            ];
        }
        
        if (!$model->updateResume()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'candidate_resume' => $model->candidate_resume,
            'message' => Yii::t('candidate', 'Resume Uploaded Successfully')
        ];
    }
    
    /**
     * update birth date 
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateBirthDate() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $birth_date = Yii::$app->request->getBodyParam('birth_date');
        
        $candidate->candidate_birth_date = date('Y-m-d', strtotime($birth_date));

        $candidate->scenario = "updateBirthDate";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "candidate_birth_date" => $candidate->candidate_birth_date,
            "message" => Yii::t('candidate', "Candidate Birth Date Info Updated Successfully"),
        ];
    }
}
