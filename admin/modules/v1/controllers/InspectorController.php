<?php

namespace admin\modules\v1\controllers;

use admin\models\Inspector;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Inspector controller - Manage Inspector accounts as Inspector
 */
class InspectorController extends Controller
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
     * Return a List of Inspector Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Inspector::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Inspector
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a inspector account
     * @return array|string[]
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Inspector();

        $model->inspector_name = Yii::$app->request->getBodyParam("name");
        $model->inspector_email = Yii::$app->request->getBodyParam("email");

        $model->inspector_password_hash = Yii::$app->request->getBodyParam("inspector_password_hash");
//        $model->setPassword (Yii::$app->security->generateRandomString());

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

        //send email to set password

        $model->sendPasswordResetEmail();

        //log to slack

        Yii::info('[Inspector Account Created] Inspector "'.$model->inspector_email.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Inspector account successfully created"
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
        $model = $this->findModel($id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Admin account not found"
                ];
        }

        $model->inspector_name = Yii::$app->request->getBodyParam("name");
        $model->inspector_email = Yii::$app->request->getBodyParam("email");

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

        Yii::info('[Inspector Account Updated] Inspector "'.$model->inspector_email.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Inspector account successfully updated"
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
        $member = $this->findModel($id);

        if($member)
        {
            Yii::info('[Inspector Account Deleted] Inspector "'.$member->inspector_email.'" deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $member->delete();

            return [
                "operation" => "success",
                "message" => "Inspector account deleted successfully"
            ];
        }else{
            return [
                "operation" => "error",
                "message" => "Inspector account not found or already deleted"
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
        $model = $this->findModel($id);

        $model->sendPasswordResetEmail();

        return [
            "operation" => "success",
            "message" => "Email has been sent to update password"
        ];
    }

    /**
     * Finds the admin model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Inspector the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Inspector::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
