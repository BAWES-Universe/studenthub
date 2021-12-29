<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\RequestChecklist;
use yii\web\NotFoundHttpException;


/**
 * RequestChecklist controller - Manage request checklist as Admin
 */
class RequestChecklistController extends Controller
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
     * Return a List of RequestChecklist Accounts available.
     */
    public function actionList()
    {
        $query = RequestChecklist::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load request checklist details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a request checklist account
     */
    public function actionCreate()
    {
        // Attempt to create new request checklist
        $model = new RequestChecklist();

        $model->status_name = Yii::$app->request->getBodyParam("status_name");
        $model->status_name_ar = Yii::$app->request->getBodyParam("status_name_ar");
        $model->is_require = Yii::$app->request->getBodyParam("is_require");
        $model->sort_order = Yii::$app->request->getBodyParam("sort_order");

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
                    "message" => "We've faced a problem creating the request checklist, please contact us for assistance."
                ];
            }
        }

        Yii::info('[RequestChecklist Account Created] RequestChecklist "'.$model->status_name_en.'" created by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "RequestChecklist created successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Create a request checklist account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel((int) $id);

        $model->status_name = Yii::$app->request->getBodyParam("status_name");
        $model->status_name_ar = Yii::$app->request->getBodyParam("status_name_ar");
        $model->is_require = Yii::$app->request->getBodyParam("is_require");
        $model->sort_order = Yii::$app->request->getBodyParam("sort_order");

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
                    "message" => "We've faced a problem updating the request checklist, please contact us for assistance."
                ];
            }
        }

        Yii::info('[RequestChecklist Account Updated] RequestChecklist "'.$model->status_name.'" updated by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        return [
            "operation" => "success",
            "message" => "RequestChecklist successfully updated"
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
        $model = $this->findModel((int)$id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "RequestChecklist not found or already deleted"
            ];

        }

        Yii::info('[RequestChecklist Deleted] RequestChecklist "'.$model->status_name.'" account deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

        // Delete
        if ($model->delete()) {

            return [
                "operation" => "success",
                "message" => "RequestChecklist deleted successfully"
            ];
        } else {
            return [
                "operation" => "error",
                "message" => "RequestChecklist deleted failed. Please try again."
            ];
        }

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    
    /**
     * Finds the RequestChecklist model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RequestChecklist::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
