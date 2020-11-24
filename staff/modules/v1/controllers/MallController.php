<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Mall;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Mall controller - Manage Mall as Admin
 */
class MallController extends Controller
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
     * Return a List of Mall Accounts available.
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionListAll()
    {
        return Mall::find()->all();
    }

    /**
     * Return a List of Mall Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Mall::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Mall details
     * @param $id
     * @return Mall
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a Mall account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new Mall
        $model = new Mall();

        $model->mall_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->mall_name_ar = Yii::$app->request->getBodyParam("name_ar");

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
                    "message" => "We've faced a problem creating the Mall, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Mall created successfully"
        ];
    }

    /**
     * Create a Mall account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Mall not found."
                ];
        }

        $model->mall_name_en = Yii::$app->request->getBodyParam("name_en");
        $model->mall_name_ar = Yii::$app->request->getBodyParam("name_ar");

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
                    "message" => "We've faced a problem updating the Mall, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Mall successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $mall = $this->findModel($id);

        if(!$mall) {
            return [
                "operation" => "error",
                "message" => "Mall not found or already deleted"
            ];
        }

        // Delete mall
        $mall->delete();

        return [
            "operation" => "success",
            "message" => "Mall deleted successfully"
        ];
    }
    
    /**
     * Finds the Mall model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Mall the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Mall::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
