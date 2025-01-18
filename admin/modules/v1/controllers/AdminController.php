<?php

namespace admin\modules\v1\controllers;

use admin\models\AdminToken;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Admin;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * Admin controller - Manage Admin accounts as Admin
 */
class AdminController extends Controller
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
     * Return a List of admin Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Admin::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Admin
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a admin account
     * @return array|string[]
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Admin();
        $model->scenario = "newAccount";

        $model->admin_name = Yii::$app->request->getBodyParam("name");
        $model->admin_email =Yii::$app->request->getBodyParam("email");
        $model->admin_password_hash = Yii::$app->request->getBodyParam("password");
        $model->admin_limited_access = Yii::$app->request->getBodyParam("limited_access");

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

        Yii::info('[Admin Account Created] Admin "'.$model->admin_email.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Admin account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a admin account
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Admin account not found"
                ];
        }

        $model->admin_name = Yii::$app->request->getBodyParam("name");
        $model->admin_email =Yii::$app->request->getBodyParam("email");
        $model->admin_limited_access = Yii::$app->request->getBodyParam("limited_access");

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

        Yii::info('[Admin Account Updated] Admin "'.$model->admin_email.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Admin account successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete an account
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
//        $count = Admin::find()->andWhere(['admin_limited_access'=>1])->count();
//        if ($count == 1) {
//            return [
//                "operation" => "error",
//                "message" => "One admin should be exist"
//            ];
//        }
        $member = $this->findModel((int)$id);

        if($member)
        {
            Yii::info('[Admin Account Deleted] Admin "'.$member->admin_email.'" deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $member->delete();

            return [
                "operation" => "success",
                "message" => "Admin account deleted successfully"
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Admin account not found or already deleted"
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
     * Reset admin password
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "admin not found",
                "code" => 1
            ];
        }

        $password = Yii::$app->security->generateRandomString(5);

        $model->setPassword($password);
        $model->save(false);

        //Send Email to user
        Admin::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
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
        $model->admin_status = $status;
        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        // reset token
        AdminToken::deleteAll(['admin_id'=>$id]);
        return [
            "operation" => "success",
            "message" => "Admin status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    /**
     * Finds the admin model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Admin the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Admin::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
