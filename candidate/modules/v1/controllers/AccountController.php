<?php

namespace candidate\modules\v1\controllers;

use candidate\models\CandidateToken;
use common\models\CandidateWorkingHour;
use common\models\Country;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use candidate\models\Candidate;
use candidate\models\CandidateSkill;
use candidate\models\CandidateExperience;
use candidate\models\TransferCandidate;
use candidate\models\Transfer;
use candidate\models\Area;
use yii\web\NotFoundHttpException;

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
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'video-by-webhook'];

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
        return Candidate::findOne(Yii::$app->user->getId());
    }
    
    /**
     * update candidate experiences
     * @return array
     */
    public function actionUpdateExperiences()
    {
        $experiences = Yii::$app->request->getBodyParam("experiences");

        $experiences = $experiences? explode(',', $experiences): [];
        
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
            ->andWhere([
                'candidate_id' => Yii::$app->user->getId()
            ])    
            ->all();

        Yii::$app->user->identity->updateAlgoliaIndex(false);

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Experiences updated successfully"),
            "experiences" => $experienceList,
            "lang" => Yii::$app->language
        ];
    }
    

    public function actionToggleTwoStepAuth() {

        $candidate = Yii::$app->user->identity;
        $candidate->enable_two_step_auth = !$candidate->enable_two_step_auth;
        
        if (!$candidate->save()) {
            return [
                'operation' => 'error',
                'message' => $candidate->errors
            ];
        }

        return [
            'operation' => 'success',
            "enable_two_step_auth" => $candidate->enable_two_step_auth,
            'message' =>  Yii::t('candidate', $candidate->enable_two_step_auth ? 
                'Two-step authentication enabled' : 
                'Two-step authentication disabled')
        ];        
    }

    /**
     * update candidate skills
     * @return array
     */
    public function actionUpdateSkills()
    {
        $skills_array = Yii::$app->request->getBodyParam("skills");

        if (!is_array($skills_array)) {
            $skills_array = $skills_array? explode(',', $skills_array): [];
        }

        if (count($skills_array) == 0)
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
            ->andWhere([
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
     * send updated candidate video status from db
     */
    public function actionVideoStatus() 
    {
        $model = Candidate::findOne(Yii::$app->user->getId());

        return [
            'candidate_video' => $model->candidate_video,
            'candidate_video_processed' => $model->candidate_video_processed
        ];
    }

    /**
     * mark video as processed 
     */
    public function actionVideoByWebhook() {

        $data = json_decode(file_get_contents("php://input"));

        if(isset($data->SubscribeURL)) {
            //log to sentry

            Yii::warning("[Confirm Subscription] " . $data->SubscribeURL, 'webhook');

            return [
                'operation' => 'success',
            ];
        }
        else if(!$data)
        {
            $data = (object) Yii::$app->request->post ();
            $detail = (object) $data->detail;
        }
        else if (isset($data->detail))
        {
            $detail = $data->detail;
        }
        else
        {
           // Yii::warning("[AWS Webhook with no details] " . print_r($data, true), 'webhook');

            return [
                'operation' => 'error',
                "message" => "Empty message"
            ];
        }

        if (empty($detail->jobId)) {
            return [
                'operation' => 'error',
                "message" => "No video job id found"
            ];
        }

        $jobId = $detail->jobId;

        $model = Candidate::find()
            ->andWhere([
                'candidate_video_job_id' => $jobId
            ])
            ->one();

        if(!$model) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', "Invalid Job ID")
            ];
        }

        if($detail->status == 'ERROR') {

            //log to sentry

            Yii::error($detail->errorMessage, 'candidate');

            //remove video

            $model->candidate_video = null;
            $model->candidate_video_processed = true;

            $model->save(false);

            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', "MediaConvert Job Failed")
            ];
        }

        $fileName = basename($detail->outputGroupDetails[0]->outputDetails[0]->outputFilePaths[0]);

        $model->candidate_video =  explode('.', $fileName)[0];

        $model->candidate_video_processed = true;
        
        if(!$model->save(false)) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        //log to slack

        $name = $model->candidate_name? $model->candidate_name: $model->candidate_name_ar;

        $url = Yii::$app->resourceManager->getUrl('candidate-video/' . $model->candidate_video . '.mp4');

        Yii::info("[Video recording uploaded by ".$name."] Watch it on " . $url, __METHOD__);

        return [
            'operation' => 'success',
        ]; 
    }

    /**
     * Remove Video
     */
    public function actionRemoveVideo() {
        $model = Candidate::findOne(Yii::$app->user->getId());

        if ($model->candidate_video) {
            $model->deleteVideo();
        }
        
        $model->candidate_video = null;
        $model->scenario = 'changeVideo';

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
            "candidate_personal_photo" => $model->candidate_personal_photo
        ];
    }

    /**
     * Remove civil photo back
     */
    public function actionRemoveCivilPhotoBack() {

        $model = Candidate::findOne(Yii::$app->user->getId());

        if (!$model) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $oldFileName = $model->candidate_civil_photo_back;

        $model->candidate_civil_photo_back = null;
        $model->candidate_civil_need_verification = true;
        $model->scenario = 'updateCivilPhotoBack';

        try {
            if (!$model->save(false)) {
                return [
                    'operation' => 'error',
                    'message' => $model->getErrors(),
                ];
            }
        } catch (\Throwable $e) {
            Yii::error([
                'action'       => 'actionRemoveCivilPhotoBack',
                'candidate_id' => $model->candidate_id,
                'side'         => 'back',
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');

            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Could not remove civil photo back.'),
            ];
        }

        // DB committed first — only then best-effort remove the orphan object.
        if ($oldFileName !== null && $oldFileName !== '') {
            $s3Key = Candidate::normalizeCivilIdPermanentS3Key($oldFileName);
            if ($s3Key !== '') {
                try {
                    Yii::$app->resourceManager->delete($s3Key);
                } catch (\Throwable $e) {
                    Yii::warning([
                        'action'       => 'actionRemoveCivilPhotoBack',
                        'candidate_id' => $model->candidate_id,
                        'side'         => 'back',
                        'filename'     => $oldFileName,
                        's3_key'       => $s3Key,
                        'reason'       => Candidate::classifyS3DeleteThrowable($e),
                        'exception'    => get_class($e),
                        'message'      => $e->getMessage(),
                    ], 'candidate.civil-id');
                }
            }
        }

        return [
            'operation' => 'success',
        ];
    }
    
    /**
     * Remove civil photo front
     */
    public function actionRemoveCivilPhotoFront() {

        $model = Candidate::findOne(Yii::$app->user->getId());

        if (!$model) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $oldFileName = $model->candidate_civil_photo_front;

        $model->candidate_civil_photo_front = null;
        $model->candidate_civil_need_verification = true;
        $model->scenario = 'updateCivilPhotoFront';

        try {
            if (!$model->save(false)) {
                return [
                    'operation' => 'error',
                    'message' => $model->getErrors(),
                ];
            }
        } catch (\Throwable $e) {
            Yii::error([
                'action'       => 'actionRemoveCivilPhotoFront',
                'candidate_id' => $model->candidate_id,
                'side'         => 'front',
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');

            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Could not remove civil photo front.'),
            ];
        }

        // DB committed first — only then best-effort remove the orphan object.
        if ($oldFileName !== null && $oldFileName !== '') {
            $s3Key = Candidate::normalizeCivilIdPermanentS3Key($oldFileName);
            if ($s3Key !== '') {
                try {
                    Yii::$app->resourceManager->delete($s3Key);
                } catch (\Throwable $e) {
                    Yii::warning([
                        'action'       => 'actionRemoveCivilPhotoFront',
                        'candidate_id' => $model->candidate_id,
                        'side'         => 'front',
                        'filename'     => $oldFileName,
                        's3_key'       => $s3Key,
                        'reason'       => Candidate::classifyS3DeleteThrowable($e),
                        'exception'    => get_class($e),
                        'message'      => $e->getMessage(),
                    ], 'candidate.civil-id');
                }
            }
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

        if (!$candidate->save()) {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        //https://www.pivotaltracker.com/story/show/176767983
        // in case if user change his bank credentials and he has any pending transfer
        // which is not in completed or not even in distribution mode then change the bank detail
        $candidateUpdated = Candidate::findOne(Yii::$app->user->getId());

        $transferCandidate = TransferCandidate::find()
            ->joinWith('transfer')
            ->andWhere(
                [
                    'candidate_id'=>Yii::$app->user->getId(),
                    'paid'=>TransferCandidate::UNPAID,
                    'transfer_candidate.deleted'=>0
                ]
            )
            ->andWhere(
                [
                    'transfer.transfer_status'=>[
                        Transfer::STATUS_INITIATED,
                        Transfer::STATUS_LOCK,
                        Transfer::STATUS_PAYMENT_SENT
                    ]
                ]
            )
            ->all();

        if (count($transferCandidate) > 0) {
            foreach ($transferCandidate as $tc) {
                // update bank detail in transfer candidate table
                $tc->transfer_benef_name = $candidateUpdated->bank_account_name;
                $tc->transfer_benef_iban = $candidateUpdated->candidate_iban;
                $tc->bank_id = $candidateUpdated->bank_id;
                $tc->save(false);
            }
        }


        Yii::$app->user->identity->updateAlgoliaIndex(false);

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Bank details updated successfully"),
            "bank" => $candidate->bank
        ];
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
            'company' => $model->company,
            'parent_company' => (isset($model->company->parentCompany)) ? $model->company->parentCompany : null
        ];
    }
    
    /**
     * Set job search status
     */
    public function actionJobSearchStatus() {
        
        $job_search_status = Yii::$app->request->getBodyParam('job_search_status');

        $model = Candidate::findOne(Yii::$app->user->getId());
        if ($model->store_id > 0 && !$job_search_status) {
            return [
                'operation' => 'error',
                "message" => Yii::t('candidate',"You can only change status if you are not assigned")
            ];
        }
        $model->candidate_job_search_status = $job_search_status;
        $model->candidate_job_search_updated_at = new Expression("NOW()");

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
        $status = [
            Transfer::STATUS_TRANSFER_COMPLETE,
            Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
        ];

        $query = TransferCandidate::find()
            ->leftJoin('transfer','transfer.transfer_id=transfer_candidate.transfer_id')
            ->andWhere('{{%transfer}}.transfer_status IN('.implode(',', $status).')')
            ->filterCandidate(Yii::$app->user->identity->candidate_id)
            ->orderBy('{{%transfer_candidate}}.tc_id DESC')
            ->andWhere(['paid' => TransferCandidate::PAID]);

        return new ActiveDataProvider([
//            'allModels' => array_reverse($currentUser->paidTransferCandidate),
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
    }

    public function actionSalaryDetail($id)
    {
        $status = [
            Transfer::STATUS_TRANSFER_COMPLETE,
            Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS
        ];

        $model = TransferCandidate::find()
            ->leftJoin('transfer','transfer.transfer_id=transfer_candidate.transfer_id')
            ->andWhere('{{%transfer}}.transfer_status IN('.implode(',', $status).')')
            ->filterCandidate(Yii::$app->user->identity->candidate_id)
            ->andWhere(['tc_id' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException("Item not found!");
        }

        return $model;
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
            "country" => Country::findOne($candidate->country_id),
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
     * Update introductory video
     */
    public function actionVideo() {
        
        $model = Yii::$app->user->identity;

        // deleting old video

        if ($model->candidate_video && !$model->deleteVideo()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        $model->candidate_video = urldecode(Yii::$app->request->getBodyParam('video'));

        if(!$model->candidate_video || $model->candidate_video == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Invalid input for {attribute}', [
                    'attribute' => 'video'
                ])
            ];
        }

        if (!$model->updateVideo()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'candidate_video' => $model->candidate_video,
            'candidate_video_processed' => $model->candidate_video_processed,
            'message' => Yii::t('candidate', 'Video Uploaded Successfully')
        ];
    }
    
    /**
     * Update personal photo 
     */
    public function actionProfilePhoto() {
        $model = Yii::$app->user->identity;

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
            'candidate_personal_photo_url' => $model->getPersonalPhotoUrl(),
            'message' => Yii::t('candidate', 'Profile Photo Uploaded Successfully')
        ];
    }

    public function actionUpdateNames() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->candidate_name = Yii::$app->request->getBodyParam('name_en');
        $candidate->candidate_name_ar = Yii::$app->request->getBodyParam('name_ar');

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
     * update Profile Url
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionProfileUrl() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->profile_url = Yii::$app->request->getBodyParam('url');

        $candidate->scenario = "updateProfileUrl";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Profile Url Updated Successfully"),
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
     * Update candidate location info
     */
    public function actionUpdateLocation() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $data = [
            'candidate_latitude' => Yii::$app->request->getBodyParam('latitude'),
            'candidate_longitude' => Yii::$app->request->getBodyParam('longitude'),
            'candidate_area_uuid' => Yii::$app->request->getBodyParam('area_uuid'),
        ];

        $candidate->scenario = "updateLocation";

        $candidate->setAttributes($data);

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "area" => $candidate->getArea()->one(),
            "country"=> $candidate->getCountry()->one(),
            "message" => Yii::t('candidate', "Candidate Location Info Updated Successfully"),
        ];
    }

    /**
     * Return area by geolocation 
     * @return type
     */
    public function actionAreaByLocation() 
    {
        $latitude = Yii::$app->request->get("latitude");
        $longitude = Yii::$app->request->get("longitude");
        $area_name = Yii::$app->request->get("area");

        // call google api to get country name, lat, long 
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $latitude .','. $longitude;
        
        return Area::addByGoogleAPIResponse($url, $area_name);
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
        $candidate->candidate_civil_need_verification = true;

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
     * update candidate intro
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateIntro() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate->candidate_intro = Yii::$app->request->getBodyParam('intro');

        $candidate->scenario = "updateIntro";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Introduction Updated Successfully"),
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
            $model->deleteResume();
        }

        $model->scenario = "updateResume";

        $resume = Yii::$app->request->getBodyParam('resume');

        if(strpos($resume, '%20') !== false) {
            $model->candidate_resume = urldecode($resume);
        } else {
            $model->candidate_resume = $resume;
        }

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
     * Remove Resume
     */
    public function actionRemoveResume() {
        $model = Candidate::findOne(Yii::$app->user->getId());

        if ($model->candidate_resume) {
            $model->deleteResume();
        }

        $model->candidate_resume = null;
        $model->scenario = 'updateResume';

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
     * update civil photo back
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateCivilPhotoBack() {
        
        $model = Candidate::findOne(Yii::$app->user->getId());

        if (!$model) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $model->scenario = "updateCivilPhotoBack";
        
        $model->candidate_civil_photo_back = urldecode(Yii::$app->request->getBodyParam('civil_photo_back'));

        if(!$model->candidate_civil_photo_back || $model->candidate_civil_photo_back == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Invalid input for {attribute}', [
                    'attribute' => 'candidate civil photo back'
                ])
            ];
        }
        
        $model->updateCivilId('back');

        //reset to remove old id's data
        $model->candidate_civil_expiry_date = null;
        $model->candidate_civil_id = null;

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            "candidate_civil_photo_back" => $model->candidate_civil_photo_back,
            "candidate_civil_photo_front" => $model->candidate_civil_photo_front,
            "candidate_civil_expiry_date" => $model->candidate_civil_expiry_date,
            "candidate_civil_id" => $model->candidate_civil_id,
            'civilExpired' => $model->candidate_civil_expiry_date && (strtotime($model->candidate_civil_expiry_date) <
                    strtotime(date('Y-m-d'))),

            'message' => Yii::t('candidate', 'Civil Photo Back Uploaded Successfully')
        ];
    }
   
    /**
     * update civil photo front
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateCivilPhotoFront() {
        
        $model = Candidate::findOne(Yii::$app->user->getId());

        if (!$model) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $model->scenario = "updateCivilPhotoFront";
        
        $model->candidate_civil_photo_front = urldecode(Yii::$app->request->getBodyParam('civil_photo_front'));

        if(!$model->candidate_civil_photo_front || $model->candidate_civil_photo_front == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Invalid input for {attribute}', [
                    'attribute' => 'candidate civil photo front'
                ])
            ];
        }
        
        $model->updateCivilId('front');

        //reset to remove old id's data
        $model->candidate_civil_expiry_date = null;
        $model->candidate_civil_id = null;

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',

            "candidate_civil_photo_back" => $model->candidate_civil_photo_back,
            "candidate_civil_photo_front" => $model->candidate_civil_photo_front,
            "candidate_civil_expiry_date" => $model->candidate_civil_expiry_date,
            "candidate_civil_id" => $model->candidate_civil_id,
            'civilExpired' => $model->candidate_civil_expiry_date && (strtotime($model->candidate_civil_expiry_date) <
                    strtotime(date('Y-m-d'))),

            'message' => Yii::t('candidate', 'Civil Photo Front Uploaded Successfully')
        ];
    }
   
    /**
     * update civil id expiry date
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateCivilExpiryDate() {
        
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }
        
        $candidate_civil_expiry_date = Yii::$app->request->getBodyParam('civil_expiry_date');

        if (!is_string($candidate_civil_expiry_date) || trim($candidate_civil_expiry_date) === '') {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Civil ID expiry date is required.'),
            ];
        }

        $expiryDt = $this->parseStrictCivilExpiryDateUtc(trim($candidate_civil_expiry_date));
        if ($expiryDt === null) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Civil ID expiry date is invalid.'),
            ];
        }

        $candidate->candidate_civil_expiry_date = $expiryDt->format('Y-m-d');

        $candidate->candidate_civil_need_verification = true;

        $candidate->scenario = "updateCivilExpiryDate";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "candidate_civil_expiry_date" => $candidate->candidate_civil_expiry_date,
            "message" => Yii::t('candidate', "Civil ID Expiry Date Updated Successfully"),
        ];
    }

    /**
     * update civil id expiry date and expiry date
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdateCivilIdExpiryDate() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate_civil_id = Yii::$app->request->getBodyParam('civil_id');
        $candidate_civil_expiry_date = Yii::$app->request->getBodyParam('civil_expiry_date');

        // Input validation: never raw 500 for invalid client payloads.
        if (!is_string($candidate_civil_id) || trim($candidate_civil_id) === '') {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Civil ID is required.'),
            ];
        }

        if (!is_string($candidate_civil_expiry_date) || trim($candidate_civil_expiry_date) === '') {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Civil ID expiry date is required.'),
            ];
        }

        $expiryDt = $this->parseStrictCivilExpiryDateUtc(trim($candidate_civil_expiry_date));
        if ($expiryDt === null) {
            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Civil ID expiry date is invalid.'),
            ];
        }

        $candidate->candidate_civil_id = trim($candidate_civil_id);
        $candidate->candidate_civil_expiry_date = $expiryDt->format('Y-m-d');
        $candidate->candidate_civil_need_verification = true;
        $candidate->scenario = 'updateCivilExpiryDateAndCivilID';

        try {

            if (!$candidate->save()) {
                return [
                    'operation' => 'error',
                    'message' => $candidate->errors,
                ];
            }

        } catch (\Throwable $e) {

            Yii::error([
                'action'       => 'actionUpdateCivilIdExpiryDate',
                'candidate_id' => $candidate->candidate_id,
                'exception'    => get_class($e),
                'message'      => $e->getMessage(),
            ], 'candidate.civil-id');

            return [
                'operation' => 'error',
                'message' => Yii::t('candidate', 'Could not update civil id and expiry date.'),
            ];
        }

        return [
            'operation' => 'success',
            'candidate_civil_expiry_date' => $candidate->candidate_civil_expiry_date,
            'message' => Yii::t('candidate', 'Civil ID And Expiry Date Updated Successfully'),
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
        
        $candidate->candidate_birth_date = empty($birth_date)? date('Y-m-d'): date('Y-m-d', strtotime($birth_date));

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

    /**
     * update preferred time
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdatePreferredTime()
    {
        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->candidate_preferred_time = Yii::$app->request->getBodyParam('preferred_time');

        $candidate->scenario = "candidatePreferredTime";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate Preferred Time Updated Successfully"),
        ];
    }

    /**
     * update phone
     * @return type
     * @throws \yii\web\HttpException
     */
    public function actionUpdatePhone() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->candidate_phone = Yii::$app->request->getBodyParam('phone');

        $candidate->scenario = "candidatePhone";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate phone number Updated Successfully"),
        ];
    }

    /**
     * @return array
     * @throws \yii\db\Exception
     * @throws \yii\web\HttpException
     */
    public function actionUpdateNationalityWithKuwaitiStatus() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->candidate_mom_kuwaiti = Yii::$app->request->getBodyParam('candidate_mom_kuwaiti');
        $candidate->country_id = Yii::$app->request->getBodyParam('country_id');

        $candidate->scenario = "updateKuwaitiNationality";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "country" => Country::findOne($candidate->country_id),
            "message" => Yii::t('candidate', "Candidate kuwaiti National Info Updated Successfully"),
        ];
    }

    /**
     * update candidate Kuwaiti National
     * @return array
     * @throws \yii\web\HttpException
     */
    public function actionUpdateKuwaitiNational() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        if (!$candidate) {
            throw new \yii\web\HttpException(404, Yii::t('candidate', 'The requested Item could not be found.'));
        }

        $candidate->candidate_mom_kuwaiti = Yii::$app->request->getBodyParam('candidate_mom_kuwaiti');

        $candidate->scenario = "updateKuwaitiNational";

        if (!$candidate->save()) {

            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Candidate kuwaiti National Info Updated Successfully"),
        ];
    }

    /**
     * start working
     * @return array
     */
    public function actionStartWorkingTime() {

        $lat = Yii::$app->request->post('lat');
        $long = Yii::$app->request->post('long');

        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(['store_id' => Yii::$app->user->identity->store_id])
            ->andWhere('end_time is null')
            ->one();

        if ($model) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate', 'You are already working')
            ];
        }

        $model = new CandidateWorkingHour();
        $model->start_time = date('Y-m-d H:i:s');
        $model->candidate_id = Yii::$app->user->getId();
        $model->store_id = Yii::$app->user->identity->store_id;
        $model->date  = date('Y-m-d');
        $model->start_location_lat = $lat;
        $model->start_location_long = $long;
        $model->via = "Timer";

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Started working successfully"),
            "data" => Yii::$app->user->identity->getIsWorking(),
        ];
    }

    /**
     * @return array
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDiscardSession() {

        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(['store_id' => Yii::$app->user->identity->store_id])
            ->andWhere('end_time is null')
            ->one();

        if (!$model) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate','You have not started working on any store')
            ];
        }

        if (!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Session removed successfully"),
        ];
    }

    /**
     * stopped working
     * @return array|string[]
     */
    public function actionStopWorkingTime() {

        $lat = Yii::$app->request->post('lat');
        $long = Yii::$app->request->post('long');

        $model = CandidateWorkingHour::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->andWhere(['store_id' => Yii::$app->user->identity->store_id])
            ->andWhere('end_time is null')
            ->one();

        if (!$model) {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate','You have not started working on any store')
            ];
        }
        $model->end_time = date('Y-m-d H:i:s');
        $model->end_location_lat = $lat;
        $model->end_location_long = $long;
        $model->start_location_lat = $lat;
        $model->start_location_long = $long;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "Stopped worked on store successfully"),
        ];
    }

    /**
     * @return mixed
     */
    public function actionWorkingStatus() {
        return Yii::$app->user->identity->getIsWorking();
    }

    /**
     * delete profile
     * @return array
     */
    public function actionDeleteProfile() {

        $candidate = Candidate::findOne(Yii::$app->user->getId());

        $candidate->scenario = 'deleteCandidate';
        $candidate->candidate_phone = null;
        $candidate->candidate_email = 'deleted_'.date('Y-m-d').'_'.$candidate->candidate_email;
        $candidate->candidate_civil_id = null;
        $candidate->deleted = 1;

        if (!$candidate->save()) {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }
        Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $candidate->candidate_id);

        CandidateToken::deleteAll(['candidate_id'=>Yii::$app->user->getId()]);

        Yii::info('['.$candidate->candidate_email.' Account Deleted] Candidate account Deleted by candidate itself', __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('candidate', "profile deleted successfully"),
        ];
    }

    /**
     * validate user password
     * @return mixed
     */
    public function actionValidateUserPassword() {
        $password = Yii::$app->request->getBodyParam("password");
        if ($password) {
            return Yii::$app->user->identity->validatePassword($password);
        }
    }

    /**
     * Parse civil expiry from strict formats only (UTC Zulu or calendar date).
     * Rejects relative phrases and silently-normalized invalid calendar dates.
     *
     * Accepted patterns:
     * - Y-m-d\\TH:i:s.u\\Z (fractional seconds padded to 6 µs digits)
     * - Y-m-d\\TH:i:s\\Z
     * - Y-m-d
     *
     * @param string $raw
     * @return \DateTimeImmutable|null
     */
    private function parseStrictCivilExpiryDateUtc(string $raw): ?\DateTimeImmutable
    {
        $utc = new \DateTimeZone('UTC');

        // Pad fractional seconds to 6 digits so PHP's `u` token parses ISO milliseconds.
        $normalized = preg_replace_callback(
            '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})\.(\d+)Z$/',
            static function ($m) {
                $digits = substr($m[2], 0, 6);
                $digits = str_pad($digits, 6, '0', STR_PAD_RIGHT);

                return $m[1] . '.' . $digits . 'Z';
            },
            $raw
        );

        $variants = [$normalized];
        if ($normalized !== $raw) {
            $variants[] = $raw;
        }

        // Leading ! rejects trailing junk and bogus partial parses.
        $formats = ['!Y-m-d\TH:i:s.u\Z', '!Y-m-d\TH:i:s\Z', '!Y-m-d'];

        foreach ($variants as $candidateStr) {
            foreach ($formats as $format) {
                $dt = \DateTimeImmutable::createFromFormat($format, $candidateStr, $utc);
                $errors = \DateTimeImmutable::getLastErrors();

                if ($dt !== false && (
                    $errors === false
                    || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
                )) {
                    return $dt;
                }
            }
        }

        return null;
    }
}
