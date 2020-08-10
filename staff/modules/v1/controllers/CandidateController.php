<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use common\models\CandidateWorkHistory;
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
        $query = Candidate::find()
            ->notDeleted();

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

        //candidate_auth_key

        if (!$model->signup())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }
        $model->updateExperiences(Yii::$app->request->getBodyParam("experience"));
        $model->updateSkills(Yii::$app->request->getBodyParam("skill"));

        Yii::info('['.$model->candidate_name.' Candidate Account Created] By '.Yii::$app->user->identity->staff_name, __METHOD__);

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
     * Assign Store to Candidate account
     * @param $id
     * @return array
     */
    public function actionAssign($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        $model->store_id = Yii::$app->request->getBodyParam("store_id");

        if (!$model->store) {
            return [
                "operation" => "error",
                "message" => "Store not found",
                "code" => 1
            ];
        }

        $store = $model->store;

        if(!$store) {
            return [
                "operation" => "error",
                "message" => "Store not found",
                "code" => 1
            ];
        }

        if (!$model->save())
        {

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

        // saving candidate work history
        CandidateWorkHistory::saveAssignedHistory($model);

        Yii::info('[Candidate '.$model->candidate_name.' assigned to work at '.$store->store_name.'] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate assigned to store successfully",
            "candidate_detail" => $model
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
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
     * Return a List of Candidate not assigned to store
     */
    public function actionListNotAssigned()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->filterNotAssigned()
            ->notDeleted();

        if($candidate_name)
        {
            $query->filterName($candidate_name);
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

        $query = Candidate::find()
            ->filterAssigned()
            ->notDeleted();

        if($candidate_name)
        {
            $query->filterName($candidate_name);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionSearch()
    {
        $country_id = Yii::$app->request->get('country_id');

        $query = Candidate::find()
            ->notDeleted();

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

        $password = Yii::$app->security->generateRandomString(5);

        $model->password = $password;
        $model->save(false);

        //Send Email to user
        Candidate::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }

    /**
     * Delete candidate
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        if ($model->store_id) {
            return [
                "operation" => "error",
                "message" => "Can not delete as assigned to store."
            ];
        }

        //check if in invoice
        $transfers = $model->transferCandidate;

        if($transfers)
        {
            return [
                "operation" => "error",
                "message" => "Can not delete as Candidate mansioned in Invoice"
            ];
        }

        Yii::info('[Candidate '.$model->candidate_name.' Deleted] By '.Yii::$app->user->identity->staff_name, __METHOD__);

        $model->softDelete();

        return [
            "operation" => "success",
            "message" => "Candidate removed successfully"
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
        return CandidateWorkHistory::find()
            ->filterCandidate($id)
            ->with('store')
            ->asArray()
            ->all();
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
