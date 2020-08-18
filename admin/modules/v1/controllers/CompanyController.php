<?php

namespace admin\modules\v1\controllers;

use common\models\File;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
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
        $query = Company::find()
            ->with([
                'subCompanies',
                'stores',
            ])    
            ->filterParent()                
            ->notDeleted();

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
            ->childCompany($id)
            ->notDeleted();

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

        if (Yii::$app->request->getBodyParam('parent')) {
            $model->scenario = "newSubAccount";
            $model->parent_company_id =Yii::$app->request->getBodyParam("parent");
            $model->company_password_hash = rand(11111,99999);
        } else {
            $model->scenario = "newAccount";
            $model->company_email =Yii::$app->request->getBodyParam("email");
            $model->company_password_hash = Yii::$app->request->getBodyParam("password");
        }
        
        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");	
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");

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
            ->with([
                'subCompanies',
                'subCompanies.stores',
                'subCompanies.stores.candidates', 
                'subCompanies.stores.candidates.store', 
                'subCompanies.stores.candidates.company', 
                'subCompanies.stores.candidates.bank',
                'subCompanies.stores.candidates.university',
                'stores',
                'stores.candidates', 
                'stores.candidates.store', 
                'stores.candidates.company', 
                'stores.candidates.bank',
                'stores.candidates.university'
            ])    
            ->filterCompany($id)
            ->notDeleted()
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

        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_email =Yii::$app->request->getBodyParam("email");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");	
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");
        
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
     * Reset Company password
     * @param $id
     * @return array
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company not found",
                "code" => 1
            ];
        }

        $password = Yii::$app->security->generateRandomString(5);

        $model->setPassword($password);
        $model->save(false);

        //Send Email to user
        Company::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }
    
    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
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

    public function actionChangeStatus($id) {
        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Company account not found"
            ];
        }

        $model->company_status = Yii::$app->request->getBodyParam("status");

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
}
