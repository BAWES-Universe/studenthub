<?php

namespace admin\modules\v1\controllers;

use common\models\CompanyContact;
use common\models\Contact;
use Illuminate\Support\Facades\Date;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\File;
use admin\models\Company;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Company controller - Manage company accounts as Admin
 */
class CompanyController extends Controller
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
     * Return a List of Company Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $status = Yii::$app->request->getQueryParam("status",0);
        $name = Yii::$app->request->getQueryParam("name",0);
        $approved_to_hire = Yii::$app->request->getQueryParam("approved_to_hire");

        $query = Company::find()
            ->filterParent();

        if ($status == 1) {
            $query->filterActive();
        }

        if ($status == 2) {
            $query->filterInActive();
        }

        if ($status == 3) {
            $query->filterByActive40DaysPassedWithoutPayment();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Company Accounts need followups.
     * @return ActiveDataProvider
     */
    public function actionFollowups()
    {
        $query = Company::find()
            ->with([
                'subCompanies',
                'stores',
            ])   
            ->followups();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Sub Company Accounts by company_id
     * @param $id
     * @return ActiveDataProvider
     */
    public function actionSubCompanies($id)
    {
        $query = Company::find()
            ->with([
                'stores.candidates', 
                'stores.candidates.store', 
                'stores.candidates.company', 
                'stores.candidates.bank',
                'stores.candidates.university'
            ])    
            ->childCompany($id);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create a company account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Company();

        $model->scenario = 'adminCreate';
        $transaction = Yii::$app->db->beginTransaction();

        if (Yii::$app->request->getBodyParam('parent')) {
            $model->scenario = "newSubAccount";
            $model->parent_company_id =Yii::$app->request->getBodyParam("parent");
        } else {
            $model->scenario = "newAccount";
            $model->company_email =Yii::$app->request->getBodyParam("email");
        }

        if ($model->scenario == "newAccount") {

            $contactModel = new Contact();

            $contactModel->contact_name = ucfirst(Yii::$app->request->getBodyParam("name"));
            $contactModel->contact_email = Yii::$app->request->getBodyParam("email");
            $contactModel->contact_password_hash = Yii::$app->security->generatePasswordHash(Yii::$app->request->getBodyParam("password"));
            $contactModel->contact_receive_email = 1;


            if (!$contactModel->sendVerificationEmail()) {

                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $contactModel->errors
                ];
            }
        }

        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");
        $model->company_common_name_en = Yii::$app->request->getBodyParam("common_name_en");
        $model->company_common_name_ar = Yii::$app->request->getBodyParam("common_name_ar");
        $model->company_description_en = Yii::$app->request->getBodyParam("description_en");
        $model->company_description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->company_website = Yii::$app->request->getBodyParam("website");
        $model->company_logo = Yii::$app->request->getBodyParam("logo");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if (!$model->save()) {
            $transaction->rollBack();
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

        if ($model->scenario == "newAccount") {
            $companyContact = new CompanyContact();
            $companyContact->company_id = $model->company_id;
            $companyContact->contact_uuid = $contactModel->contact_uuid;
            $companyContact->contact_position = 'CEO';
            $companyContact->allow_access = true;

            if (!$companyContact->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $companyContact->errors
                ];
            }
        }
        $transaction->commit();

        Yii::info('['.$model->company_name.' Company Account Created] Company created by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * View company detail
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $company = Company::find()
            ->filterCompany($id)
            ->one();

        if(!$company){
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $company;
    }

    /**
     * Create a company account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                    "operation" => "error",
                    "message" => "Company account not found"
                ];
        }
        
        $model->scenario = 'adminUpdate';

        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_email =Yii::$app->request->getBodyParam("email");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");
        $model->company_common_name_en = Yii::$app->request->getBodyParam("common_name_en");
        $model->company_common_name_ar = Yii::$app->request->getBodyParam("common_name_ar");
        $model->company_description_en = Yii::$app->request->getBodyParam("description_en");
        $model->company_description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->company_website = Yii::$app->request->getBodyParam("website");
        $model->company_logo = Yii::$app->request->getBodyParam("logo");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $company = $this->findModel((int) $id);

        if ($company) {

            if (count($company->stores)>0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple store."
                ];
            }

            if (count($company->transfers)>0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple transfers."
                ];
            }

            if (count($company->subCompanies) > 0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple Sub Company."
                ];
            }

            Yii::info('[Company Account Soft Deleted] Company "'.$company->company_name.'" soft deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $company->softDelete();

            return [
                "operation" => "success",
                "message" => "Company account successfully deleted"
            ];

        }else{
            return [
                "operation" => "error",
                "message" => "Company account not found or already deleted"
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occurred, please contact us for assistance"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDeleteFile($id)
    {
        $model = File::findOne(['file_uuid'=>$id]);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Invalid File"
            ];
        }

        if (!$model->deleteDocument()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        if (!$model->delete()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }
        return [
            "operation" => "success",
            "message" => "Company Document successfully deleted"
        ];
        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    
    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Company the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Create a company account
     * @param $id
     * @return array
     */
    public function actionCreateFile($id)
    {
        $model = new File();
        $model->scenario = 'create';
        $model->file_title = Yii::$app->request->getBodyParam("file_title");
        $model->file_description =Yii::$app->request->getBodyParam("file_description");
        $model->file_s3_path = Yii::$app->request->getBodyParam("file_s3_path");
        $model->file_name = $model->file_s3_path;
        $model->company_id = $id;

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->file_title. ' document upload for company '.$model->company->company_name.'] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company document uploaded successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * @param $id
     * @return array|string[]
     */
    public function actionUpdateFile($id)
    {
        $model = File::findOne(['file_uuid'=>$id]);

        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        
        $model->scenario = 'update';
        $model->file_title = Yii::$app->request->getBodyParam("file_title");
        $model->file_description =Yii::$app->request->getBodyParam("file_description");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->file_title. ' document upload for company '.$model->company->company_name.'] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company document data updated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionChangeStatus($id) {

        $model = $this->findModel((int) $id);

        $model->scenario = 'updateStatus';

        $model->company_status_override = Yii::$app->request->getBodyParam("status");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company status updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateFollowup($id) {

        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Company account not found"
            ];
        }

        $model->scenario = 'updateFollowup';

        $model->company_followup = Yii::$app->request->getBodyParam("followup");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup status updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    
    public function actionUpdateFollowupInterval($id) {

        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Company account not found"
            ];
        }

        $model->scenario = 'updateFollowupInterval';

        $model->company_followup_interval_weeks = Yii::$app->request->getBodyParam("followup_interval_weeks");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup interval updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup interval changed successfully"
        ];
    }

    /**
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    public function actionYearReport() {
        $year = Yii::$app->request->get("year", date('Y'));
        $company_id = Yii::$app->request->get("company_id" , null);

        $stats = [];
        foreach(range(1,12) as $key => $value) {
            $value = str_pad($value,2,"0", STR_PAD_LEFT);
            // request
            $q = 'SELECT count(*) as total, MONTH(request_created_datetime) as month, YEAR(request_created_datetime) as year, monthname(str_to_date(MONTH(request_created_datetime),"%m")) as monthName FROM `request` WHERE';
            if ($company_id) {
                $q .= ' `company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(request_created_datetime) = '.$year.' AND MONTH(request_created_datetime) = '.$value;

            $records = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['request'] = $records['total'];
            $stats[$key]['month'] = date("F", mktime(0, 0, 0, $value, 10));
            $stats[$key]['month_number'] = $value;

            // suggestions
            $q = 'SELECT count(*) as total FROM `suggestion` left join request on request.request_uuid = suggestion.request_uuid WHERE';
            if ($company_id) {
                $q .= ' `request`.`company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(suggestion_datetime) = '.$year.' AND MONTH(suggestion_datetime) = '.$value;

            $suggestion = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['suggestions'] = $suggestion['total'];

            // hired
            $q = 'SELECT count(*) as total FROM `candidate_work_history` WHERE';
            if ($company_id) {
                $q .= ' (`company_id`=' . $company_id . ' OR `parent_company_id`=' . $company_id . ') AND ';
            }
            $q .= ' YEAR(start_date) = '.$year.' AND MONTH(start_date) = '.$value;

            $joined = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['hired'] = $joined['total'];
        }
        return $stats;
    }

    /**
     * Return a List of Company Accounts available.
     * @return ActiveDataProvider
     */
    public function actionDownloadListExcel()
    {
        $status = Yii::$app->request->getQueryParam("status",0);
        $name = Yii::$app->request->getQueryParam("name",0);
        $approved_to_hire = Yii::$app->request->getQueryParam("approved_to_hire");

        $query = Company::find()
            ->filterParent();

        if ($status == 1) {
            $query->filterActive();
        }

        if ($status == 2) {
            $query->filterInActive();
        }

        if ($status == 3) {
            $query->filterByActive40DaysPassedWithoutPayment();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }

        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                'company_id',
                'company_name',
                'company_common_name_en',
                'company_common_name_ar',
                [
                    'attribute'=>'company_status',
                    'label'=>'Company Status',
                    'value'=>function($model) {
                        return ($model->company_status) ? 'Active':'InActive';
                    }
                ],
                [
                    'attribute'=>'total_suggestions',
                    'label'=>'Total Suggestions',
                    'value'=>function($model) {
                        return $model->getSuggestions()->count();
                    }
                ],
                [
                    'attribute'=>'company_bonus_commission',
                    'label'=>'company bonus commission',
                    'value'=>function($model) {
                        if($model->company_bonus_commission)
                            return (double)$model->company_bonus_commission;

                        if($model->parentCompany)
                            return (double)$model->parentCompany->company_bonus_commission;
                    }
                ],
                [
                    'attribute'=>'company_hourly_rate',
                    'label'=>'Company Hourly Rate',
                    'value'=>function($model) {
                        if($model->company_hourly_rate)
                            return (double)$model->company_hourly_rate;

                        if($model->parentCompany)
                            return (double)$model->parentCompany->company_hourly_rate;
                    }
                ],
                [
                    'attribute'=>'total_candidates',
                    'label'=>'Total Candidates',
                    'value'=>function($model) {
                        return (int)\common\models\Company::getTotalCandidateCount($model->company_id);
                    }
                ],
                [
                    'attribute'=>'total_subcompanies',
                    'label'=>'Total SubCompanies',
                    'value'=>function($model) {
                        return (int)$model->getSubCompanies()->count();
                    }
                ],
                [
                    'attribute'=>'total_subcompanies',
                    'label'=>'Total SubCompanies',
                    'value'=>function($model) {
                        return (int)$model->getSubCompanies()->count();
                    }
                ],
                [
                    'attribute'=>'total_stores',
                    'label'=>'Total Stores',
                    'value'=>function($model) {
                        return (int)$model->getStores()->count();
                    }
                ],
            ]
        ]);
    }
}
