<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Webhook;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Webhook controller - Manage Webhook as Admin
 */
class WebhookController extends Controller
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
        $behaviors['authenticator']['except'] = ['options','list'];

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
     * Return a List of Country Accounts available.
     */
    public function actionList()
    { 
        $query = Webhook::find();
 
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load webhook details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a webhook account
     */
    public function actionCreate()
    {
        // Attempt to create new webhook
        $model = new Webhook();

        $model->event = Yii::$app->request->getBodyParam("event");
        $model->endpoint = Yii::$app->request->getBodyParam("endpoint");
        $model->event = Yii::$app->request->getBodyParam("event");
        $model->method = Yii::$app->request->getBodyParam("method");
        $model->created_by = Yii::$app->user->getId();

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
                    "message" => "We've faced a problem creating the webhook, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Webhook Account Created] Webhook "'.$model->webhook_name_en.'" created by Staff: "'.Yii::$app->user->identity->staff_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Webhook created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a webhook account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        $model->event = Yii::$app->request->getBodyParam("event");
        $model->endpoint = Yii::$app->request->getBodyParam("endpoint");
        $model->event = Yii::$app->request->getBodyParam("event");
        $model->method = Yii::$app->request->getBodyParam("method");
        $model->updated_by = Yii::$app->user->getId();

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
                    "message" => "We've faced a problem updating the webhook, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Webhook Account Updated] Webhook "'.$model->event.'" updated by Staff: "'.Yii::$app->user->identity->staff_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "Webhook successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $webhook = $this->findModel((int)$id);

        if ($webhook->delete()) {

            return [
                "operation" => "success",
                "message" => "Webhook deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "Webhook deleted failed. Please try again."
            ];
        }
    }

    /**
     * Finds the Webhook model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Webhook::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
