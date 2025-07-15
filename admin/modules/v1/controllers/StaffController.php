<?php

namespace admin\modules\v1\controllers;

use agent\models\PaymentMethod;
use common\models\StaffNotification;
use common\models\StaffSalary;
use common\models\StaffToken;
use company\models\TranferExcel;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Staff;
use common\models\Currency;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * Staff controller - Manage staff accounts as Admin
 */
class StaffController extends Controller
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
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $model = $this->findModel($id);

        $model->generateAuthKey();

        if(!$model->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->errors,
                'redirect' => Yii::$app->params['staffAppUrl']
            ];
        }

        $url = Yii::$app->params['staffAppUrl']. '?auth_key='.$model->staff_auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * Return a List of Staff Accounts available.
     */
    public function actionList()
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '-1');
        $role = Yii::$app->request->get('role', null);
        $status = Yii::$app->request->get('status', null);
        $name = Yii::$app->request->get('name', null);
        $deleted = Yii::$app->request->get('deleted', null);

        $query = Staff::find();

        if($role) {
            $query->andWhere(['staff_role' => $role]);
        }
        if ($deleted) {
            if ($deleted == 1) {
                $query->andWhere(['staff.deleted' => $deleted]);
            } else {
                $query->andWhere(['staff.deleted' => 0]);
            }
        }
        if($name) {
            $query->filterName($name);
        }

        if($status || $status == '0') {
            $query->andWhere(['staff_status' => $status]);
        }

        $query->orderBy('staff_status desc');
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Staff Salaries available.
     */
    public function actionListSalaries($id)
    {
        $staff = $this->findModel($id);

        $query = $staff->getStaffSalaries();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Staff Salaries available.
     */
    public function actionListCompanies($id)
    {
        $staff = $this->findModel($id);

        return new ActiveDataProvider([
            'query' => $staff->getCompanies()
        ]);
    }

    /**
     * import bank excel to extract candidate data
     * @return type
     */
    public function actionImportSalary() {

        $model = new TranferExcel();
        $model->excel = Yii::$app->request->getBodyParam('excel');

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }

        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($model->excel);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir() . '/' . $model->excel;

        if(!file_put_contents($tmpFile, file_get_contents($fileUrl))) {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];
        }

        $excelData  = \common\components\PhpExcel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '1');

        //second row will be key

        //$keys = \yii\helpers\ArrayHelper::remove($excelData, '2');

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values)
        {
            $data[] = array_combine($keys, $values);
        }

        //no need file anymore

        @unlink($tmpFile);

        //remove empty rows

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($data as $key => $value)
        {
            if(empty($value['staff_id'])) {

                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => 'Invalid excel',
                    'errorCode' => 1
                ];
            }

            $model = new StaffSalary();
            $model->staff_id = $value['staff_id'];
            $model->salary = $value['salary'];
            $model->salary_currency = $value['salary_currency'];
            $model->comment = $value['comment'];
            $model->salary_date = empty($value['salary_date'])?
                date('Y-m-d'): date('Y-m-d', strtotime($value['salary_date']));

            if (!$model->save())
            {
                $transaction->rollBack();

                if(isset($model->errors)){
                    return [
                        "operation" => "error",
                        "message" => $model->errors
                    ];
                }else{
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem adding salary, please contact us for assistance."
                    ];
                }
            }

            /*if(YII_ENV == 'prod') {
                Yii::$app->eventManager->setUser('admin_' . Yii::$app->user->getId(), [
                    '$first_name' => Yii::$app->user->identity->admin_name,
                    '$email' => Yii::$app->user->identity->admin_email
                ]);
            }*/

            //todo: send to segment

            Yii::info('[Staff Salary Added] For "'.$model->staff->staff_email.'" by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        }

        $transaction->commit();

        return [
            'operation' => "success",
            'message' => "Salaries imported successfully"
        ];
    }

    /**
     * load staff details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a staff account
     */
    public function actionCreate()
    {
        $model = new Staff();
        $model->scenario = "newAccount";

        $model->staff_name = Yii::$app->request->getBodyParam("name");
        $model->staff_email =Yii::$app->request->getBodyParam("email");
        $model->staff_password_hash = Yii::$app->request->getBodyParam("password");
        $model->staff_gmail_username = Yii::$app->request->getBodyParam("gmail_username");
        $model->staff_role = Yii::$app->request->getBodyParam("role");
        $model->staff_gmail_password = Staff::encryptPass(Yii::$app->request->getBodyParam("gmail_password"));
        $model->staff_job_title = Yii::$app->request->getBodyParam("job_title");
        $model->staff_salary = Yii::$app->request->getBodyParam("salary");
        $model->staff_salary_currency = Yii::$app->request->getBodyParam("salary_currency");
        $model->week_start_day = Yii::$app->request->getBodyParam("week_start_day");
        $model->work_days = Yii::$app->request->getBodyParam("work_days");
        $model->hours_per_day = Yii::$app->request->getBodyParam("hours_per_day");

        $model->staff_notification = Yii::$app->request->getBodyParam("staff_notification");

        $permissions =  Yii::$app->request->getBodyParam("permissions");

        $staff_photo = Yii::$app->request->getBodyParam('staff_photo');
        
        if(!Currency::findOne(['code' => $model->staff_salary_currency])){
            return [
                "operation" => "error",
                "message" => "Invalid currency code"
            ];
        }

        if($staff_photo)
            $model->setLogo($staff_photo);

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

        if(!$permissions) {
            $permissions = [];
        }

        foreach ($permissions as $permission) {
            $pmodel = new StaffNotification();
            $pmodel->staff_id = $model->staff_id;
            $pmodel->permission = $permission;
            $pmodel->save();
        }

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->setUser('staff' . $model->staff_id, [
                '$first_name' => $model->staff_name,
                '$email' => $model->staff_email
            ]);

            $param = [
                'email' => Yii::$app->request->getBodyParam('email'),
                'password' => Yii::$app->request->getBodyParam('password'),
                'name' => $model->staff_name,
                'nickname' => $model->staff_name
            ];

            Yii::$app->auth0->createUser($param);
        }

        Yii::info('[Staff Account Created] Staff "'.$model->staff_email.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff account successfully created",
            "staffNotifications" => $model->staffNotifications
        ];
    }

    /**
     * Create a staff account
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Staff account not found"
                ];
        }

        $model->staff_name = Yii::$app->request->getBodyParam("name");
        $model->staff_email =Yii::$app->request->getBodyParam("email");
        $model->staff_gmail_username = Yii::$app->request->getBodyParam("gmail_username");
        $model->staff_role = Yii::$app->request->getBodyParam("role");
        $model->staff_gmail_password = Staff::encryptPass(Yii::$app->request->getBodyParam("gmail_password"));
        $model->staff_job_title = Yii::$app->request->getBodyParam("job_title");
        $model->staff_salary = Yii::$app->request->getBodyParam("salary");
        $model->staff_salary_currency = Yii::$app->request->getBodyParam("salary_currency");
        $model->week_start_day = Yii::$app->request->getBodyParam("week_start_day");
        $model->work_days = Yii::$app->request->getBodyParam("work_days");
        $model->hours_per_day = Yii::$app->request->getBodyParam("hours_per_day");
        $model->staff_notification = Yii::$app->request->getBodyParam("staff_notification");

        $permissions =  Yii::$app->request->getBodyParam("permissions");

        $staff_photo = Yii::$app->request->getBodyParam('staff_photo');

        if($staff_photo)
            $model->setLogo($staff_photo);

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

        if(!$permissions) {
            $permissions = [];
        }

        foreach ($permissions as $permission) {

           // if(isset($permission['sn_uuid'])) {
           //     $pmodel = new StaffNotification();
            $pmodel = StaffNotification::findOne([
                "staff_id" => $model->staff_id,
                "permission" => $permission
            ]);

            if($pmodel) {
               continue;
            }

            $pmodel = new StaffNotification();
            $pmodel->staff_id = $model->staff_id;
            $pmodel->permission = $permission;
            $pmodel->save();
        }

        StaffNotification::deleteAll([
            "AND",
            ['staff_id' => $model->staff_id],
            ['NOT IN', 'permission', $permissions]
        ]);

        if(YII_ENV == 'prod') {
            Yii::$app->eventManager->setUser('staff' . $model->staff_id, [
                '$first_name' => $model->staff_name,
                '$email' => $model->staff_email
            ]);
        }

        Yii::info('[Staff Account Updated] Staff "'.$model->staff_email.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Staff account successfully updated",
            "staffNotifications" => $model->staffNotifications
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
        $staffMember = $this->findModel((int)$id);

        if($staffMember) 
        {
            Yii::info('[Staff Account Soft Deleted] Staff "'.$staffMember->staff_email.'" soft deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $staffMember->softDelete();

            return [
                "operation" => "success",
                "message" => "Staff account deleted successfully"
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Staff account not found or already deleted"
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occured, please contact us for assistance."
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionStatus($id)
    {
        $status = Yii::$app->request->post('status', 0);
        $model = $this->findModel((int)$id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Invalid Account"
            ];
        }
        $model->staff_status = $status;
        if (!$model->save(false)) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        // reset token
        StaffToken::deleteAll(['staff_id'=>$id]);
        return [
            "operation" => "success",
            "message" => "Staff status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    public function actionRecoverAccount($id)
    {
        $model = $this->findModel((int)$id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Invalid Account"
            ];
        }
        $model->staff_status = Staff::STATUS_ACTIVE;
        $model->deleted = 0;
        if (!$model->save(false)) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        // reset token
        StaffToken::deleteAll(['staff_id'=>$id]);
        return [
            "operation" => "success",
            "message" => "Staff recovered changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    
    /**
     * Reset staff password
     * @param $id
     * @return array
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);
        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Staff not found",
                "code" => 1
            ];
        }
        $model->generatePasswordResetToken();
        if (!$model->save(false)) {
            return [
                "operation" => "error",
                "message" => $model->getErrors(),
            ];
        }
        $model->sendVerificationEmail();
        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }

    /**
     * Reset staff password
     * @param $id
     * @return array
     */
    public function actionResetPasswordDirect($id)
    {
        $model = $this->findModel((int) $id);
        $password = Yii::$app->request->getBodyParam("password", null);
        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Staff not found",
                "code" => 1
            ];
        }

        if (!$password) {
            $password = Yii::$app->security->generateRandomString(5);
        }

        $model->password = $password;
        $model->save(false);

        //Send Email to user
        Staff::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }
    
    /**
     * Finds the Staff model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Staff the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Staff::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
