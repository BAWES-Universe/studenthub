<?php

namespace admin\modules\v1\controllers;

use Yii;
use common\models\Campaign;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CampaignController extends Controller
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
     * Return a List of Campaign Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Campaign::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Campaign details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a Campaign account
     * @return array
     */
    public function actionCreate()
    {
        $model = new Campaign();

        $model->utm_source = Yii::$app->request->getBodyParam("utm_source");
        $model->utm_medium = Yii::$app->request->getBodyParam("utm_medium");
        $model->utm_campaign = Yii::$app->request->getBodyParam("utm_campaign");
        $model->utm_content= Yii::$app->request->getBodyParam("utm_content");
        $model->utm_term= Yii::$app->request->getBodyParam("utm_term");
        $model->investment= Yii::$app->request->getBodyParam("investment");

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
                    "message" => "We've faced a problem creating the Campaign, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Campaign created successfully"
        ];
    }

    /**
     * Create a Campaign account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->utm_source = Yii::$app->request->getBodyParam("utm_source");
        $model->utm_medium = Yii::$app->request->getBodyParam("utm_medium");
        $model->utm_campaign = Yii::$app->request->getBodyParam("utm_campaign");
        $model->utm_content= Yii::$app->request->getBodyParam("utm_content");
        $model->utm_term= Yii::$app->request->getBodyParam("utm_term");
        $model->investment= Yii::$app->request->getBodyParam("investment");

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
                    "message" => "We've faced a problem updating the Campaign, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Campaign successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Delete Campaign
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Campaign deleted successfully"
        ];
    }

    /**
     * Finds the Campaign model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Campaign the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Campaign::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}