<?php

namespace staff\modules\v1\controllers;

use kartik\mpdf\Pdf;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use staff\models\Note;
use staff\models\Store;
use staff\models\CandidateWorkHistory;
use yii\web\NotFoundHttpException;


/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
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
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
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
     * Return a List of Candidate Accounts available.
     */
    public function actionList()
    {
        $query = Candidate::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a Candidate account
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $password = Yii::$app->security->generateRandomString(5);

        $model = new Candidate();
        //$model->scenario = "newAccount";

        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->bank_account_name = Yii::$app->request->getBodyParam("bank_account_name");
        $model->candidate_iban = Yii::$app->request->getBodyParam("iban");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_personal_photo = Yii::$app->request->getBodyParam("personal_photo");
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_phone = Yii::$app->request->getBodyParam("phone");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date")? date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("birth_date"))): null;
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date")? date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("expiry_date"))): null;
        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->candidate_password_hash = $password;
        $model->password = $password; // temp password to send in mail

        $model->candidate_driving_license = Yii::$app->request->getBodyParam("candidate_driving_license");
        $model->candidate_gender = Yii::$app->request->getBodyParam("candidate_gender");
        $model->candidate_objective = Yii::$app->request->getBodyParam("candidate_objective");
        $model->candidate_resume = Yii::$app->request->getBodyParam("resume");
        $model->candidate_latitude = Yii::$app->request->getBodyParam("latitude");
        $model->candidate_longitude = Yii::$app->request->getBodyParam("longitude");
        $model->candidate_area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->candidate_mom_kuwaiti = Yii::$app->request->getBodyParam("mom_kuwait");

        $model->approved = 1;

        //candidate_auth_key

        if (!$model->signup(true))
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        $model->updateExperiences(Yii::$app->request->getBodyParam("experience"));
        $model->updateSkills(Yii::$app->request->getBodyParam("skill"));

        return [
            "operation" => "success",
            "message" => "Candidate account successfully created",
            "candidate" => $model
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update a Candidate account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->country_id = Yii::$app->request->getBodyParam("country_id");
        $model->bank_account_name = Yii::$app->request->getBodyParam("bank_account_name");
        $model->candidate_iban = Yii::$app->request->getBodyParam("iban");
        $model->candidate_name = Yii::$app->request->getBodyParam("name");
        $model->candidate_name_ar = Yii::$app->request->getBodyParam("name_ar");
        $model->candidate_personal_photo = Yii::$app->request->getBodyParam("personal_photo");
        $model->candidate_email = Yii::$app->request->getBodyParam("email");
        $model->candidate_phone = Yii::$app->request->getBodyParam("phone");
        $model->candidate_civil_id = Yii::$app->request->getBodyParam("civil_id");

        $model->candidate_civil_photo_front = Yii::$app->request->getBodyParam("photo_front");
        $model->candidate_civil_photo_back = Yii::$app->request->getBodyParam("photo_back");
        $model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");

        $model->candidate_driving_license = Yii::$app->request->getBodyParam("candidate_driving_license");
        $model->candidate_gender = Yii::$app->request->getBodyParam("candidate_gender");
        $model->candidate_objective = Yii::$app->request->getBodyParam("candidate_objective");
        $model->candidate_birth_date = Yii::$app->request->getBodyParam("birth_date")? date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("birth_date"))): null;
        $model->candidate_civil_expiry_date = Yii::$app->request->getBodyParam("expiry_date")? date('Y-m-d', strtotime(Yii::$app->request->getBodyParam("expiry_date"))): null;

        $model->candidate_resume = Yii::$app->request->getBodyParam("resume");
        $model->candidate_latitude = Yii::$app->request->getBodyParam("latitude");
        $model->candidate_longitude = Yii::$app->request->getBodyParam("longitude");
        $model->candidate_area_uuid = Yii::$app->request->getBodyParam("area_uuid");
        $model->candidate_mom_kuwaiti = Yii::$app->request->getBodyParam("mom_kuwait");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "code" => "2",
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance.",
                ];
            }
        }
        $model->updateExperiences(Yii::$app->request->getBodyParam("experience"));
        $model->updateSkills(Yii::$app->request->getBodyParam("skill"));

        Yii::info('['.$model->candidate_name.' Candidate Account Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account updated successfully",
            "candidate" => $model,
            "store" => $model->store,
            "company" => $model->company
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Set job search status
     */
    public function actionJobSearchStatus() {
        
        $job_search_status = Yii::$app->request->getBodyParam('job_search_status');
        $candidate_id = Yii::$app->request->getBodyParam('candidate_id');

        $model = $this->findModel($candidate_id);

        if ($model->store_id > 0 && !$job_search_status) {
            return [
                'operation' => 'error',
                "message" => Yii::t('candidate',"Candidate status can only be change if they are not assigned")
            ];
        }

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
     * Set candidate candidate_hourly_rate
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateCandidateHourRate($id) {

        $candidate_hourly_rate = Yii::$app->request->getBodyParam('hourly_rate');

        $model = $this->findModel($id);
        $model->candidate_hourly_rate = $candidate_hourly_rate;

        $model->scenario = 'updateHourRate';

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
     * Assign Store to Candidate account
     * @param $id
     * @return array
     */
    public function actionAssign($id)
    {
        $store_id = Yii::$app->request->getBodyParam("store_id");

        $model = $this->findModel($id);

        if ($model->store_id) {
            return [
                "operation" => "error",
                "message" => "Please remove old Store before assign new store",
                "code" => 1
            ];
        }

        $isExists = CandidateWorkHistory::find()
            ->andWhere([
                'candidate_id' => $model->candidate_id,
                'store_id' => $store_id
            ])
            ->andWhere(new \yii\db\Expression("start_date = CURDATE()"))
            ->count();

        if ($isExists) {
            return [
                "operation" => "error",
                "message" => "Same Store not possible to assign on same day",
                "code" => 1
            ];
        }

        $model->store_id = $store_id;
        $storeName = $model->store->store_name;
        if (!$model->save()) {

            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "code" => 2
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance.",
                    "code" => 3
                ];
            }
        }

        // save note
        $noteModel  = new Note();
        $noteModel->candidate_id  = $id;
        $noteModel->company_id  = $model->store->company_id;
        $noteModel->note_type  = Note::TYPE_INTERNAL_NOTE;
        $noteModel->note_text  = "Assigned to work at {$storeName}";
        $noteModel->save(false);

        // saving candidate work history

        CandidateWorkHistory::saveAssignedHistory($model);

        Yii::info('[Candidate '.$model->candidate_name.' assigned to work at '.$model->store->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate assigned to store successfully",
            "candidate_detail" => $model
        ];
    }

    /**
     * Remove Store from Candidate account
     * @param $id
     * @return array
     */
    public function actionUnassign($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);
        $storeName = $model->store->store_name;
        $model->store_id = null;

        if (!$model->save(false))
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        $commonCompanyName = $model->store->company->company_common_name_en;
        // save note
        $feedback = Yii::$app->request->get('feedback');
        $noteModel  = new Note();
        $noteModel->candidate_id  = $id;
        $noteModel->note_type  = Note::TYPE_INTERNAL_NOTE;
        $noteModel->note_text  = "No longer assigned to work at {$storeName} for {$commonCompanyName} because {$feedback}";
        $noteModel->save(false);

        CandidateWorkHistory::saveUnAssignedHistory($model);

        Yii::info('['.$model->candidate_name.' unassigned from store] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate unassigned from store successfully",
            "candidate_detail" => $model,
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Toggle Candidate committed and create a note
     * @return array
     */
    public function actionToggleCommitted()
    {
        $transaction = Yii::$app->db->beginTransaction();

        $model = new Note();

        $model->note_text = htmlentities(Yii::$app->request->getBodyParam("note"));
        $model->note_type = Yii::$app->request->getBodyParam("type");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");

        if (!$model->save())
        {
            $transaction->rollBack();

            if(isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        $model->candidate->candidate_committed = !$model->candidate->candidate_committed;
        $model->candidate->setScenario ('updateCommitted');

        if (!$model->candidate->save())
        {
            $transaction->rollBack();

            if(isset($model->candidate->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->candidate->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem toggling candidate committed status, please contact us for assistance."
                ];
            }
        }

        $transaction->commit();

        if (!$model->candidate->candidate_committed) {
            $model->candidate->commitmentWarningEmail();
        }

        return [
            "operation" => "success",
            "candidate_committed" => $model->candidate->candidate_committed,
            "message" => "Candidate committed status updated successfully"
        ];
    }
    
    /**
     * Expire candidate id by setting expiry as now 
     * @param $id
     * @return array
     */
    public function actionExpireCandidateCard($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $card  = $model->getCandidateIdCard()->one();
        
        if(!$card) {
            return [
                "operation" => "error",
                "message" => "No card found to mark as expired"
            ];
        }
        
        $card->expiry_date = date('Y-m-d', strtotime('-1 day'));

        if (!$card->save(false))
        {
            if(isset($card->errors)){
                return [
                    "operation" => "error",
                    "message" => $card->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('['.$model->candidate_name.' ID Card mark as expired] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate card expired successfully",
            "candidate_detail" => $model,
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Return a List of Candidate not assigned to store
     */
    public function actionListNotAssigned()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");
        $incompleteProfile = Yii::$app->request->get("incomplete_profile");
        $withoutBank = Yii::$app->request->get("without_bank");

        $query = Candidate::find()
            ->filterNotAssigned();

        if ($incompleteProfile) {
        //    $query->byApprovalStatus(0);
        }

        if($candidate_name)
        {
            $query->filterName($candidate_name);
        }

        if ($withoutBank) {
            $query->joinWith('transferCandidate');
            $query->withBankInfo();
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate assigned to store
     */
    public function actionListAssigned()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");
        $incompleteProfile = Yii::$app->request->get("incomplete_profile");

        $query = Candidate::find()
            ->filterAssigned()
            ->notDeleted();
        if ($incompleteProfile) {
            $query->incompletedProfile();
        }

        $query->notDeleted();

        if($candidate_name) {
            $query->filterName($candidate_name);
        }
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate filter to store
     */
    public function actionFilter()
    {
        $name = Yii::$app->request->get("name");
        $email = Yii::$app->request->get("email");
        $phone = Yii::$app->request->get("phone");
        $type = Yii::$app->request->get("type");

        $query = Candidate::find();

        if ($type == 'assigned') {
            $query->filterAssigned();
        } else if ($type == 'un-assigned'){
            $query->filterNotAssigned();
        }

        if($name) {
            $query->filterName($name);
        }

        if($email) {
            $query->filterEmail($email);
        }

        if($phone) {
            $query->filterPhone($phone);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate assigned to store
     * @return ActiveDataProvider
     */
    public function actionListWithoutBankInfo()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::withoutBankInfoOrWithPayment($candidate_name);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 200,
            ],
        ]);
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionSearch()
    {

        $country_id = Yii::$app->request->get('country_id');
        $by = Yii::$app->request->get('by');

        $query = Candidate::find()
            ->verifiedProfile();

        switch ($by) {
            case 'review' :
                $query->byApprovalStatus(Yii::$app->request->get('review'));
                $query->completedProfileWithoutApproval();
                break;
            default:
                # nothing
                break;
        }

        if($country_id) {
            $query->filterCountry($country_id);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Reset candidate password
     * @param $id
     * @return array
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel($id);

        $model->sendPasswordResetEmail();

        $staff = Yii::$app->user->identity;

        Yii::info("[Student Password Reset Request] by ". $staff->staff_name
             .", Candidate Email: ".$model->candidate_email, __METHOD__);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }

    /**
     * Return candidate's salary transfer with status
     * @param $id
     * @return array|mixed
     */
    public function actionTransfers($id)
    {
        $model = $this->findModel($id);

        return $model->paidTransferCandidate;
    }

    /**
     * get candidate work history
     * @param $id
     * @return array|static[]
     */
    public function actionWorkHistory($id)
    {
        $query = CandidateWorkHistory::find()
            ->filterCandidate($id);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    /**
     * get candidate detail
     * @param $id
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Return a No of Candidate to review
     * Return a No of Payable candidate also
     */
    public function actionTotalToReview()
    {
        $query = \admin\models\Candidate::find()
            ->byApprovalStatus(0);

        return [
            'total' => $query->count(),
        ];
    }

    /**
     * Approve candidate account
     * @param $id
     * @return array
     */
    public function actionApprove($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->scenario = 'statusChange';

        $model->approved = 1;

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('['.$model->candidate_email.' Account Approved] Candidate account approved by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account approved successfully",
            "saved" => $model
        ];
    }

    /**
     * Unapprove candidate account
     * @param $id
     * @return array
     */
    public function actionUnapprove($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->scenario = 'statusChange';

        $model->approved = 0;

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('['.$model->candidate_email.' Account marked as requires approval] Candidate account marked as requires approval by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account marked as requires approval successfully",
            "saved" => $model
        ];
    }

    /**
     * Merge candidate accounts
     * @return array
     */
    public function actionMerge()
    {
        $source_id = Yii::$app->request->getBodyParam ('source');
        $destination_id = Yii::$app->request->getBodyParam ('destination');

        //validate candidate ids
        $this->findModel((int) $source_id);
        $this->findModel((int) $destination_id);

        Candidate::merge($source_id, $destination_id);

        Yii::info('[Candidate account merged] #'.$source_id.' merged to #'. $destination_id.' and #'.$source_id.' removed by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account merged successfully"
        ];
    }

    /**
     * List candidates having expired Civil ID Cards
     */
    public function actionListExpiredCivilId()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->civilIdExpired()
            ->filterAssigned()
            ->notDeleted(); // only candidate with assigned work

        if($candidate_name) {
            $query->filterName($candidate_name);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Staff: "Assigned Idle Candidates".
     * This page should list candidates that are assigned to work but
     * have no TransferCandidate records in past 2 months
     * @return ActiveDataProvider
     */
    public function actionAssignedIdleCandidates() {

        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::getAssignedIdleCandidate($candidate_name);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Download Transfer as PDF
     * @param $id
     * @param $type
     * @return array|mixed
     */
    public function actionCandidateResumePdf($id)
    {
        $withNumber = Yii::$app->request->get('with_number');
        $candidate = $this->findModel($id);

        // remove phone in case candidate phone not required.
        if (!$withNumber) {
            $candidate->candidate_phone = null;
        }

        if(!$candidate) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        $this->layout = 'main';

        $content = $this->render('candidate-resume-pdf', [
            'candidate' => $candidate,
            'withNumber' => $withNumber,
        ]);

        $pdf = new Pdf([
            'options' => [
                'defaultheaderline' => 0,  //for header
                'defaulfooterline' => 0,  //for footer
                'title' => 'Candidate Resume #'.$candidate->candidate_name
            ],
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            'marginTop' => 5,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => "
            @font-face {
              font-family: 'effra';
              src: url('".Yii::getAlias("@web")."/fonts/effra_std_bd-webfont.woff2') format('woff2'),
                   url('".Yii::getAlias("@web")."/fonts/effra_std_bd-webfont.woff') format('woff'),
                   url('".Yii::getAlias("@web")."/fonts/effra_std_bd-webfont.ttf') format('truetype');
              font-weight: 700;
              font-style: normal;
            }

            @font-face {
              font-family: 'effra';
              src: url('".Yii::getAlias("@web")."/fonts/effra_std_rg-webfont.woff2') format('woff2'),
                   url('".Yii::getAlias("@web")."/fonts/effra_std_rg-webfont.woff') format('woff'),
                   url('".Yii::getAlias("@web")."/fonts/effra_std_rg-webfont.ttf') format('truetype');
              font-weight: 400;
              font-style: normal;
            }

            @font-face {
              font-family: 'effra';
              src: url('".Yii::getAlias("@web")."/fonts/l') format('woff2'),
                   url('".Yii::getAlias("@web")."/fonts/d.woff') format('woff'),
                   url('".Yii::getAlias("@web")."/fonts/a') format('opentype');
              font-weight: 500;
              font-style: normal;
            }

            html, body, h1, p, div {
                font-family: 'effra', sans-serif;
            }",
//            'methods' => [
//                'SetHeader'=>[$candidate->employeeId .'<br/>'. 'Prepared by '.Yii::$app->user->identity->staff_name],
//                'SetHeader'=>[$candidate->employeeId .'<br/>'. 'Prepared by Khalid'],
//            ]
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }

    /**
     * @param $id
     * @return mixed|string[]
     * @throws NotFoundHttpException
     * @throws \Mpdf\MpdfException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAppreciationCertificate($id,$wid) {
        $candidate = $this->findModel($id);

        if(!$candidate) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }
        $workHistory = $candidate->getWorkHistory()->andWhere(['id'=>$wid])->one();
        $this->layout = 'main';
        $content = $this->render('candidate-appreciation-certificate-pdf', [
            'candidate' => $candidate,
            'workHistory' => $workHistory
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            'marginTop' => 5,
            'marginRight' => 6,
            'marginLeft' => 6,
            // portrait orientation
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => [
                '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                'css/pdf.css'
            ],
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }
    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Candidate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
