<?php

namespace staff\modules\v1\controllers;

use common\models\CandidateNotification;
use common\models\CandidateToken;
use common\models\CandidateWarning;
use common\models\Contract;
use common\models\Request;
use common\models\StoreAssignmentRequest;
use common\models\TransferCost;
use kartik\mpdf\Pdf;
use staff\models\Company;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use staff\models\Note;
use common\models\CandidateTag;
use staff\models\Store;
use staff\models\CandidateWorkHistory;
use yii\web\NotFoundHttpException;
use yii\web\Response;


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
            'class' => \yii\filters\Cors::class,
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
            'class' => \yii\filters\auth\HttpBearerAuth::class,
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $candidate_civil_need_verification = Yii::$app->request->headers->get("candidate_civil_need_verification");
        $filter_minor = Yii::$app->request->get('filter_minor');

        $query = Candidate::find();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        if ($candidate_civil_need_verification) {
            $query->andWhere(['candidate_civil_need_verification' => $candidate_civil_need_verification]);
        }

        if ($filter_minor) {
            $query->andWhere(new Expression("candidate.candidate_birth_date < DATE_SUB(NOW(), INTERVAL 16 YEAR)"));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts available.
     */
    public function actionAssignedHistoryList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $working_time = Yii::$app->request->get('working_time', null);
        $name = Yii::$app->request->get("name");
        $email = Yii::$app->request->get("email");
        $filterSameRate = Yii::$app->request->get("filterSameRate");
        $filter_minor = Yii::$app->request->get('filter_minor');

        $query = CandidateWorkHistory::find()
            ->joinWith('candidate')
            ->orderBy('id DESC');

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        if($name) {
            $query->filterName($name);
        }

        if($filterSameRate) {
            $query->andWhere(new Expression("candidate.candidate_hourly_rate = candidate_work_history.candidate_hourly_rate"));
        }

        if($email) {
            $query->filterEmail($email);
        }

        if($start_date) {
            $query->startDate($start_date);
        }

        if($end_date) {
            $query->endDate($end_date, $working_time);
        }

        if ($filter_minor) {
            $query->andWhere(new Expression("candidate.candidate_birth_date < DATE_SUB(NOW(), INTERVAL 16 YEAR)"));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a Candidate account
     */
    public function actionCreate()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        // Attempt to create new account
        $password = Yii::$app->security->generateRandomString(5);

        $model = new Candidate();
        //$model->scenario = "newAccount";

        $model->candidate_preferred_time = Yii::$app->request->getBodyParam ('preferred_time');
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
        //$model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->currency_code =  Yii::$app->request->getBodyParam("currency_code", "KWD");

        if(!$model->currency_code) {
            $model->currency_code = $currency;
        }

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
            if(isset($model->errors)) {
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
        $model->updateTags(Yii::$app->request->getBodyParam("tags"));

        return [
            "operation" => "success",
            "message" => "Candidate account successfully created",
            "candidate" => $model
        ];
    }

    /**
     * Update a Candidate account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $model = $this->findModel($id);

        $model->candidate_preferred_time = Yii::$app->request->getBodyParam ('preferred_time');
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
        //$model->candidate_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->currency_code =  Yii::$app->request->getBodyParam("currency_code", "KWD");

        if(!$model->currency_code) {
            $model->currency_code = $currency;
        }

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

        $model->updateTags(Yii::$app->request->getBodyParam("tags"));

        Yii::info('['.$model->candidate_name.' Candidate Account Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account updated successfully",
            "candidate" => $model,
            "store" => $model->store,
            "company" => $model->company
        ];
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionAddTag($id)
    {
        $model = $this->findModel($id);

        $tag = new CandidateTag;
        $tag->candidate_id = $model->candidate_id;
        $tag->tag = Yii::$app->request->getBodyParam("tag");
        $tag->reason = Yii::$app->request->getBodyParam("reason");

        if(!$tag->save()) {
            return [
                'operation' => 'error',
                'message' => $tag->errors
            ];
        }

        Yii::info('['.$model->candidate_name."'s tag updated] By ".Yii::$app->user->identity->staff_name, __METHOD__);

        $model->updateAlgoliaIndex();

        return [
            "operation" => "success",
            "message" => "Candidate account updated successfully",
            //"candidateTags" => $model->getCandidateTags()->all(),
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionUpdateTags($id)
    {
        $model = $this->findModel($id);

        $model->updateTags(Yii::$app->request->getBodyParam("tags"));

        Yii::info('['.$model->candidate_name.' Candidate Account Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        $model->updateAlgoliaIndex();

        return [
            "operation" => "success",
            "message" => "Candidate account updated successfully",
            "candidateTags" => $model->getCandidateTags()->all(),
        ];
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

        Yii::info('['.$model->candidate_name.' Candidate Job Search Status Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            'operation' => 'success',
        ];
    }

    /**
     * update candidate email
     */
    public function actionUpdateCandidateEmail($id) {

        $email = Yii::$app->request->getBodyParam('email');

        $model = $this->findModel($id);

        $model->candidate_email = $email;

        $model->scenario = 'updateCandidateEmail';

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        CandidateToken::deleteAll(['candidate_id'=>$id]);
        Yii::info('['.$model->candidate_name.' Candidate Email Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            'operation' => 'success',
        ];
    }

    /**
     * update candidate email
     */
    public function actionUpdateCandidateCivilExpiry($id) {

        $date = Yii::$app->request->getBodyParam('date');

        $model = $this->findModel($id);

        if ($date && strtotime($date) < time()) {
            return [
                "operation" => "error",
                "message" => "Civil id should be future date"
            ];
        }

        $model->candidate_civil_expiry_date = $date? date('Y-m-d', strtotime($date)): null;

        $model->scenario = "updateCivilExpiryDate";

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        Yii::info('['.$model->candidate_name.' Candidate Civil ID Expiry Date Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            'operation' => 'success',
            "message" => Yii::t('candidate',"Candidate Civil Expiry date updated successfully")
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

        Yii::info('['.$model->candidate_name.' Candidate Hourly Rate Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            'operation' => 'success',
        ];
    }

    public function actionCompanyTransferCost($candidate_id, $store_id) {

        $store = Store::find()->andWhere(['store_id' => $store_id])->one();

        if (!$store) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $company = $store->getCompany()->one();

        $company_id = !empty($company->parent_company_id) ?
            $company->parent_company_id: $company->company_id;

        return TransferCost::find()
            ->andWhere(["candidate_id" => $candidate_id, "company_id" => $company_id])
            ->one();
    }

    /**
     * Assign Store to Candidate account
     * @param $id
     * @return array
     */
    public function actionAssign($id)
    {
        $sar_id = Yii::$app->request->getBodyParam("sar_id");
        $store_id = Yii::$app->request->getBodyParam("store_id");
        $start_date = Yii::$app->request->getBodyParam("start_date");
        $end_date = Yii::$app->request->getBodyParam("end_date");
        $transfer_cost = Yii::$app->request->getBodyParam("transfer_cost");
    //$company_transfer_cost = Yii::$app->request->getBodyParam("company_transfer_cost");
 
        $contract_type = Yii::$app->request->getBodyParam("contract_type");
        $contract_detail = Yii::$app->request->getBodyParam("contract_detail");
        $contract_amount_details = Yii::$app->request->getBodyParam("contract_amount_details");

        $contract_currency_code = Yii::$app->request->getBodyParam("currency_code", "KWD");
        //Yii::$app->request->headers->get("Currency", "KWD");

        //deprecated field 
        $hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $company_hourly_rate = Yii::$app->request->getBodyParam("company_hourly_rate");
        
        $model = $this->findModel($id);

        if ($model->store_id) {
            return [
                "operation" => "error",
                "code" => 1,
                "message" => "Please remove old Store before assign new store",
            ];
        }

        $isExists = Contract::find()
            ->andWhere([
                'candidate_id' => $model->candidate_id,
                'store_id' => $store_id
            ])
            ->andWhere(new \yii\db\Expression("start_date = CURDATE()"))
            ->count();

        if ($isExists) {
            return [
                "operation" => "error",
                "code" => 2,
                "message" => "Same Store not possible to assign on same day",
            ];
        }

        $store = Store::find()
            ->andWhere (['store_id' => $store_id])
            ->one();

        if(!$store) {
            return [
                "operation" => "error",
                "code" => 3,
                "message" => "Store not found",
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        $model->store_id = $store_id;

        $model->candidate_hourly_rate = $hourly_rate;

        if (!$model->save(false)) {
            
            $transaction->rollBack();

            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "code" => 4,
                    "message" => $model->errors,
                ];
            }else{
                return [
                    "operation" => "error",
                    "code" => 5,
                    "message" => "We've faced a problem updating the account, please contact us for assistance.",
                ];
            }
        }

        // save note

        $storeName = $store->store_name;

        $noteModel  = new Note();
        $noteModel->candidate_id  = $id;
        $noteModel->company_id  = $model->store->company_id;
        $noteModel->note_type  = Note::TYPE_INTERNAL_NOTE;
        $noteModel->note_text  = "Assigned to work at {$storeName}";

        if(!$noteModel->save()) {

            $transaction->rollBack();
            
            return [
                "operation" => "error",
                "code" => 6,
                "message" => $noteModel->errors,
            ];
        }

        //save company level transfer cost

        $company = $model->store->getCompany()->one();

        // there is possibility that candidate just moved from old store to new store

        $company_id = !empty($company->parent_company_id) ?
            $company->parent_company_id: $company->company_id;

            /*
        $transfer_cost_model = TransferCost::find()
            ->andWhere([
                "candidate_id" => $model->candidate_id,
                "company_id" => $company_id
            ])
            ->one();

        if (!$transfer_cost_model) {
            $transfer_cost_model = new TransferCost();
            $transfer_cost_model->candidate_id = $model->candidate_id;
            $transfer_cost_model->company_id = $company_id;
        }

        if ($contract_uuid) {

            $contract = Contract::find()
                ->andWhere([
                    "AND",
                    ['contract_uuid' => $contract_uuid],
                    [
                        "IN",
                        "company_id", [
                            $company->company_id,
                            $company->parent_company_id,
                        ]
                    ]
                ])
                ->one();

            if (!$contract) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Contract not found"
                ];
            }

            $transfer_cost_model->transfer_cost = $contract->transfer_cost;
        } else {
            $transfer_cost_model->transfer_cost = $company_transfer_cost;
        }

        if (!$transfer_cost_model->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 7,
                "message" => $transfer_cost_model->errors,
            ];
        }*/

        // saving candidate work history
        $contract = new Contract();

        $contract->scenario = Contract::SCENARIO_ASSIGN;// _TEMPLATE;

        $contract->candidate_id = $id;
        $contract->store_id = $store_id;

        $contract->type = $contract_type;
        $contract->detail = $contract_detail;
        $contract->start_date = $start_date;
        $contract->end_date = $end_date;
        $contract->transfer_cost = $transfer_cost;
        $contract->currency_code = $contract_currency_code;
        $contract->status = Contract::STATUS_INACTIVE;
        $contract->amountDetails = $contract_amount_details;
 
        if (!$model->save()) {
            $transaction->rollBack();

            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem adding the contract, please contact us for assistance"
                ];
            }
        }

        //backup code
        $workHistory = CandidateWorkHistory::saveAssignedHistory(
            $model,
            $start_date,
            $company_hourly_rate,
            $transfer_cost,
            $contract->contract_uuid
        );

        if($workHistory->errors) {     

            $transaction->rollBack();

            return [
                "operation" => "error",
                "code" => 8,
                "message" => $workHistory->errors,
            ];
        }

        $sar = null;

        if($sar_id) {
            $sar = StoreAssignmentRequest::findOne($sar_id);
        } else {
            $sar = StoreAssignmentRequest::find()
                ->andWhere(['candidate_id' => $model->candidate_id, 'store_id' => $store_id])
                ->one();
        }

        if(!empty($sar)) {

            $sar->status = StoreAssignmentRequest::STATUS_ACCEPTED;

            if (!$sar->save()) {

                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "code" => 9,
                    "message" => $sar->errors
                ];
            }
        }

        $transaction->commit(); 

        Yii::info('[Candidate contract created to work at '.$storeName.' for  '.$model->candidate_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate contract created successfully",
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
        $model = $this->findModel($id);

        $store_id = Yii::$app->request->get('store_id', null);
        $sar_id = Yii::$app->request->getBodyParam("sar_id");

        $transaction = Yii::$app->db->beginTransaction();

        $candidateHistoryModel = null;

        // in case multiple store are assigned by mistake or system issue.
        if ($store_id  && $store_id != $model->store_id) {

            // else save unassigned history
            $candidateHistoryModel = \common\models\CandidateWorkHistory::find()
                ->filterCandidate($model->candidate_id)
                ->andWhere(['store_id'=>$store_id])
                ->one();

            if ($candidateHistoryModel) {
                $storeName = $candidateHistoryModel->store->store_name;
                $company_id = $candidateHistoryModel->store->company_id;
                $commonCompanyName = $candidateHistoryModel->company->company_common_name_en;

                $candidateHistoryModel->end_date  = new \yii\db\Expression('NOW()');

                if (!$candidateHistoryModel->save()) {

                    $transaction->rollBack();

                    return [
                        "operation" => "error",
                        "message" => $model->errors
                    ];
                }

                $candidateHistoryModel->generateCertificate();

            } else {
                $transaction->rollBack();

                return [
                    'operation' =>'error',
                    'message' =>Yii::t('app','no record found')
                ];
            }
        } else {

            $storeName = $model->store->store_name;
            $company_id = $model->store->company_id;
            $commonCompanyName = $model->company->company_common_name_en;

            $model->store_id = null;

            if (!$model->save(false)) {
                $transaction->rollBack();

                if (isset($model->errors)) {
                    return [
                        "operation" => "error",
                        "message" => $model->errors
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem updating the account, please contact us for assistance."
                    ];
                }
            }

            CandidateWorkHistory::saveUnAssignedHistory($model);
        }

        // save note
        $feedback = Yii::$app->request->get('feedback');
        $noteModel  = new Note();
        $noteModel->candidate_id  = $id;
        $noteModel->company_id  = $company_id;
        $noteModel->note_type  = Note::TYPE_INTERNAL_NOTE;
        $noteModel->note_text  = "No longer assigned to work at {$storeName} for {$commonCompanyName} because {$feedback}";

        if(!$noteModel->save()) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => $noteModel->errors
            ];
        }

        if($sar_id) {
            $sar = StoreAssignmentRequest::findOne($sar_id);
        } else {
            $sar = StoreAssignmentRequest::find()
                ->andWhere(['candidate_id' => $model->candidate_id])
                ->andWhere(new Expression("store_id IS NULL"))
                ->one();
        }

        if(!empty($sar)) {

            $sar->status = StoreAssignmentRequest::STATUS_ACCEPTED;

            if (!$sar->save()) {

                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $sar->errors
                ];
            }
        }

        $candidateNotification = new CandidateNotification();
        $candidateNotification->candidate_id = $id;
        $candidateNotification->candidate_work_history_id = $candidateHistoryModel ? $candidateHistoryModel->id: null;
        $candidateNotification->company_id = $company_id;
        $candidateNotification->store_id = $store_id;
        $candidateNotification->staff_id = Yii::$app->user->getId();
        $candidateNotification->message = $feedback;
        $candidateNotification->type = CandidateNotification::TYPE_UNASSIGNED;
        if (!$candidateNotification->save()) {
            $transaction->rollBack();

            Yii::error("Error saving notification: " . print_r($candidateNotification->errors, true));

            return [
                "operation" => "error",
                "message" => $candidateNotification->errors
            ];
        }

        $transaction->commit();

        Yii::info('['.$model->candidate_name.' unassigned from store] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate unassigned from store successfully",
            "candidate_detail" => $model,
        ];
    }

    /**
     * @param $candidate_id
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionApplications($candidate_id)
    {
        $query = $this->findModel($candidate_id)
            ->getRequestApplications()
            ->orderBy("created_at DESC");

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionMarkNotDeleted($id) {
        $model = $this->findModel($id);

        if (!$model->undoDelete()) {
            if(isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem marking candidate not deleted, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
        ];
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

        Yii::info('['.$model->candidate->candidate_name.' Candidate Job Committed Status Updated] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "candidate_committed" => $model->candidate->candidate_committed,
            "message" => "Candidate committed status updated successfully"
        ];
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $model = $this->findModel((int)$id);

        $model->generateAuthKey();

        if(!$model->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->errors,
                'redirect' => Yii::$app->params['candidateAppUrl']
            ];
        }

        $url = Yii::$app->params['candidateAppUrl']. 'landing?auth_key='.$model->candidate_auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * Return a List of Candidate not assigned to store
     */
    public function actionListNotAssigned()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $candidate_name = Yii::$app->request->get("candidate_name");
        $incompleteProfile = Yii::$app->request->get("incomplete_profile");
        $withoutBank = Yii::$app->request->get("without_bank");

        $query = Candidate::find()
            ->filterNotAssigned();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
     * mark candidates as deleted
     * @param $id
     * @return array|string[]
     */
    public function actionMarkDuplicate($id)
    {
        $candidate = Candidate::find()
            ->filterNotAssigned()
            ->notDeleted()
            ->andWhere(['candidate_id' => $id])
            ->one();

        if(!$candidate) {
            return [
                "operation" => "error",
                "message" => "No candidate found!"
            ];
        }

        $candidate->is_duplicate = true;

        if(!$candidate->softDelete()) {
            return [
                "operation" => "error",
                "message" => $candidate->errors
            ];
        }

        Yii::info('['.$candidate->candidate_email.' Account marked as duplicate] Candidate account marked as duplicate and removed by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Account marked as deleted!"
        ];
    }

    /**
     * Return a List of Candidate assigned to store
     */
    public function actionListAssigned()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");
        $candidate_name = Yii::$app->request->get("candidate_name");
        $incompleteProfile = Yii::$app->request->get("incomplete_profile");

        $query = Candidate::find()
            ->filterAssigned()
            ->notDeleted();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $name = Yii::$app->request->get("name");
        $company_id = Yii::$app->request->get("company_id");
        $email = Yii::$app->request->get("email");
        $phone = Yii::$app->request->get("phone");
        $type = Yii::$app->request->get("type");
        $civil = Yii::$app->request->get("civil");
        $updatedAfter = Yii::$app->request->get("updatedAfter");
        $civilId = Yii::$app->request->get('civilId');
        $candidate_civil_need_verification = Yii::$app->request->get('candidate_civil_need_verification');
        $filter_minor = Yii::$app->request->get('filter_minor');

        $query = Candidate::find();

        if ($candidate_civil_need_verification) {
            $query->andWhere(['candidate_civil_need_verification' => $candidate_civil_need_verification]);
        }

        //letting list all company candidate from company detail page
        if($company_id) {
            $company = Company::find()->andWhere(['company_id' => $company_id])->one();
            $query->filterCompany($company);
        } else if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        if ($type == 'assigned') {
            $query->filterAssigned();
        } else if ($type == 'un-assigned'){
            $query->filterNotAssigned();
        }

        if($name && is_numeric($name)) {
            $query->filterById($name);
        }

        if($name && !is_numeric($name)) {
            $query->filterName($name);
        }

        if($email) {
            $query->filterEmail($email);
        }

        if($phone) {
            $query->filterPhone($phone);
        }

        if ($civil) {
            if ($civil == 1) {
                $query->activeCivilId();
            } else if ($civil == 2) {
                $query->civilIdExpired();
            }
        }

        if($civilId) {
            $query->filterCivil($civilId);
        }

        if($updatedAfter) {
            $query->filterUpdatedAfter($updatedAfter);
        }

        $query->notDeleted();

        if ($filter_minor) {
            $query->andWhere(new Expression("candidate.candidate_birth_date > DATE_SUB(NOW(), INTERVAL 16 YEAR)"));
        }

        $query->addOrderBy('candidate.candidate_id DESC');

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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::withoutBankInfoOrWithPayment($candidate_name);

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $country_id = Yii::$app->request->get('country_id');
        $match_request_id = Yii::$app->request->get('match_request_id');
        $candidate_civil_need_verification = Yii::$app->request->get('candidate_civil_need_verification');

        $by = Yii::$app->request->get('by');

        $query = Candidate::find()
            ->verifiedProfile();

        if ($candidate_civil_need_verification) {
            $query->andWhere(['candidate_civil_need_verification' => $candidate_civil_need_verification]);
        }

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        switch ($by) {
            case 'review' :
                $query->byApprovalStatus(Yii::$app->request->get('review'));
                $query->completedProfileWithoutApproval();
                break;
            default:
                # nothing
                break;
        }

        if($match_request_id) {
            $query->filterByRequestRequirement($match_request_id);
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
     * Warn candidate
     * @param $id
     * @return array
     */
    public function actionWarnCandidate($id)
    {
        $candidate = $this->findModel($id);

        $model = new CandidateWarning();
        $model->candidate_id = $id;
        $model->title = Yii::$app->request->getBodyParam ('title');
        $model->message = Yii::$app->request->getBodyParam ('message');
        $model->created_by = Yii::$app->user->getId();

        if (!$model->save(false))
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

        Yii::info('['.$candidate->candidate_name.' warned] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate warned successfully",
        ];
    }

    /**
     * update warning
     * @param $id
     * @return array
     */
    public function actionUpdateWarning($id)
    {
        $candidate = $this->findModel($id);

        $model = CandidateWarning::findOne($id);
        $model->title = Yii::$app->request->getBodyParam ('title');
        $model->message = Yii::$app->request->getBodyParam ('message');
        $model->updated_by = Yii::$app->user->getId();

        if (!$model->save())
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

        Yii::info('['.$candidate->candidate_name."'s warning updated] By ".Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate warning updated successfully",
        ];
    }

    /**
     * get candidate's warnings
     * @param $id
     * @return array|static[]
     */
    public function actionCandidateWarnings($id)
    {
        $model = $this->findModel($id);

        $query = $model->getCandidateWarnings()
            ->orderBy('created_at DESC');

        return new ActiveDataProvider([
            'query' => $query,
            //'pagination' => false
        ]);
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $query = \admin\models\Candidate::find()
            ->byApprovalStatus(0);

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->civilIdExpired()
            ->filterAssigned()
            ->notDeleted(); // only candidate with assigned work

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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

        $currency = Yii::$app->request->headers->get("Currency", "KWD");
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::getAssignedIdleCandidate($candidate_name);

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
    public function actionAppreciationCertificate($id, $wid) {

        $candidate = $this->findModel($id);

        if(!$candidate) {
            return [
                "operation" => "error",
                "message" => 'Transfer not found!'
            ];
        }

        $workHistory = $candidate->getWorkHistory()
            ->andWhere(['id' => $wid])
            ->one();

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
     * @return void
     */
    public function actionExportCandidateData()
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '-1');

        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $name = Yii::$app->request->get("name");
        $limit = Yii::$app->request->get("export_limit",5000);
        $email = Yii::$app->request->get("email");
        $phone = Yii::$app->request->get("phone");
        $type = Yii::$app->request->get("type");
        $task = Yii::$app->request->get("task");
        $page = Yii::$app->request->get("export_page", 1);
        $updatedAfter = Yii::$app->request->get("updatedAfter");

        $query = Candidate::find();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

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
        if($updatedAfter) {
            $query->filterUpdatedAfter($updatedAfter);
        }

        if ($task == 'expired_ids') {
            $query->idExpired()->filterAssigned(); // only candidate with assigned work
        }

        if ($task == 'generate_ids') {
            $query->idNeedGenerated()->filterAssigned();
        }

        if ($task == 'missing_bank_info') {
            $query->withoutBankInfo();
        }

        if ($task == 'expired_civil_id') {
            $query->civilIdExpired()->filterAssigned();
        }
        if ($task == 'assigned_idle') {
            $query->filterAssigned()->getTwoMonthBeforeTransfers();
        }
        if ($task == 'incomplete_profile') {
            $query->filterAssigned()->incompletedProfile();
        }

        $query->notDeleted();
        $query->limit($limit);
        $query->offset(($page-1) * $limit);
        $candidates = $query
            ->all();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                'candidate_name',
                'candidate_email',
                'candidate_phone',
                'candidate_civil_id',
                'candidate_civil_expiry_date',
                [
                    'header' => 'company name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->store) ? $model->store->company->company_name : 'Un-Assigned';
                    },
                ],
                [
                    'header' => 'Store name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->store) ? $model->store->store_name : 'Un-Assigned';
                    },
                ],
                "currency_code"
            ]
        ]);
    }

    /**
     * @return void
     */
    public function actionExportAssignedHistory()
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '-1');

        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $name = Yii::$app->request->get("name");
        $limit = Yii::$app->request->get("export_limit",5000);
        $email = Yii::$app->request->get("email");
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");
        $type = Yii::$app->request->get("type");
        $task = Yii::$app->request->get("task");
        $page = Yii::$app->request->get("export_page", 1);
        $updatedAfter = Yii::$app->request->get("updatedAfter");

        $query = CandidateWorkHistory::find();
        $query->joinWith(['candidate','company','store']);

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        if($name) {
            $query->filterName($name);
        }

        if($email) {
            $query->filterEmail($email);
        }

        if($start_date) {
            $query->startDate($start_date);
        }

        if($end_date) {
            $query->endDate($end_date);
        }

        $query->limit($limit);
        $query->offset(($page-1) * $limit);
        $candidates = $query
            ->all();

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                'candidate_id',
                'candidate.candidate_name',
                'start_date',
                'end_date',
                [
                    'header' => 'company name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->store) ? $model->store->company->company_name : 'Un-Assigned';
                    },
                ],
                [
                    'header' => 'Store name',
                    "format" => "raw",
                    "value" => function ($model) {
                        return ($model && $model->store) ? $model->store->store_name : 'Un-Assigned';
                    },
                ],
            ]
        ]);
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
