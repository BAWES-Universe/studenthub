<?php

namespace admin\modules\v1\controllers;

use common\models\Transfer;
use Yii;
use yii\rest\Controller;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use admin\models\Company;
use admin\models\Store;

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
     * Return a List of Company Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Company::find()->filterParent()->notDeleted();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Sub Company Accounts by company_id
     * @param $id
     * @return ActiveDataProvider
     */
    public function actionSubcompanies($id)
    {
        $query = Company::find()->childCompany($id)->notDeleted();

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
            $model->company_name = Yii::$app->request->getBodyParam("name");
            $model->parent_company_id =Yii::$app->request->getBodyParam("parent");
            $model->company_password_hash = rand(11111,99999);
        } else {
            $model->scenario = "newAccount";
            $model->company_name = Yii::$app->request->getBodyParam("name");
            $model->company_email =Yii::$app->request->getBodyParam("email");
            $model->company_password_hash = Yii::$app->request->getBodyParam("password");
        }

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

        Yii::info('[Company Account Created] Company "'.$model->company_name.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

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
            ->where(['company_id' => $id])
            ->asArray()
            ->notDeleted()
            ->one();

        if(!$company){
            return [
                    "operation" => "error",
                    "message" => "Company account not found"
                ];
        }

        //sub companies

        $company['subcompanies'] = Company::find()->where([
                'parent_company_id' => $company['company_id']
            ])->all();

        //stores

        $company['stores'] = Store::find()->where([
                'company_id' => $company['company_id']
            ])->notDeleted()->all();

        return $company;
    }

    /**
     * Create a company account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Company::findOne((int) $id);

        if (!$model) {
            return [
                    "operation" => "error",
                    "message" => "Company account not found"
                ];
        }

        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_email =Yii::$app->request->getBodyParam("email");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent");

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

        Yii::info('[Company Account Updated] Company "'.$model->company_name.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

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
        $company = Company::findOne((int)$id);

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
}
