<?php

namespace staff\modules\v1\controllers;

use common\models\CompanyContact;
use common\models\Contact;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use staff\models\Company;
use staff\models\Note;
use staff\models\File;
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
            'collectionOptions' => ['GET', 'OPTIONS'],
            'resourceOptions' => ['GET', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Company Accounts available.
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

        if ($status == 4) {
            $query->filterActive()
                ->andWhere(new \yii\db\Expression("company_created_at < DATE_SUB(NOW(),INTERVAL 40 DAY)"))//last 40 day
                ->filterByActive40DaysPassedWithoutRequest();
        }

        if ($status == 5) {
            $query->filterActiveWithOnlyStaff();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }

        $query->notDeleted();

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
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
            ->followups()
            ->filterParent();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Company Detail
     * @param $id
     * @return Company
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $model = $this->findModel((int)$id);
    }

    /**
     * add followup note
     * @return array
     */
    public function actionAddFollowupNote($id)
    {
        // Attempt to create new brand
        $model = new Note();

        $model->note_text = Yii::$app->request->getBodyParam("note");
        $model->note_type = Yii::$app->request->getBodyParam("type");

        $model->company_id = $id;

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
                    "message" => "We've faced a problem creating the Note, please contact us for assistance."
                ];
            }
        }

        $model->company->company_last_followup_datetime = new Expression('NOW()');
        $model->company->save(false);

        //reload to get latest followup time

        $company = Company::findOne($id);

        return [
            "operation" => "success",
            "message" => "Note created successfully",
            "company_last_followup_datetime" => $company->company_last_followup_datetime
        ];
    }

    /**
     * Create a company account
     * @param $id
     * @return array
     */
    public function actionCreateFile($id)
    {
        $model = new File();
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

        Yii::info('['.$model->file_title. ' document upload for company '.$model->company->company_name.'] Company updated by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company document uploaded successfully"
        ];
    }

    /**
     * Send payroll email
     * @param $id
     * @return array
     */
    public function actionPayrollEmail($id)
    {

          $model = $this->findModel((int) $id);

          if (!$model) {
              return [
                  "operation" => "error",
                  "message" => "Company account not found"
              ];
          }

          $mail = Company::sendPayrollEmail($model);


          return [
              "operation" => "success",
              "message" => "Payroll email has been sent",
              "mail_status" => $mail
          ];

    }

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

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup status updated by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
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
        $model->company_followup = Yii::$app->request->getBodyParam("followup");
        $model->company_followup_interval_weeks = Yii::$app->request->getBodyParam("followup_interval_weeks");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if ($model->company_followup) {
            $model->company_last_followup_datetime = new Expression('NOW()');
        }

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
        $mail = Company::companyCreateUpdateMail($model);

        Yii::info('['.$model->company_name.' Company Account Created] Company created by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully created",
            "mail_status" => $mail
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
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

        $model->scenario = 'update';

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
        $model->company_followup = Yii::$app->request->getBodyParam("followup");
        $model->company_followup_interval_weeks = Yii::$app->request->getBodyParam("followup_interval_weeks");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if ($model->oldAttributes['company_followup'] != $model->company_followup) {
            $model->company_last_followup_datetime = new Expression('NOW()');
        }

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        $mail = Company::companyCreateUpdateMail($model,'updated');

        Yii::info('['.$model->company_name.' Company Account Updated] Company updated by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully updated",
            "mail_status" => $mail
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
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

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup interval updated by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup interval changed successfully"
        ];
    }

    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return \admin\models\Company the loaded model
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
}
