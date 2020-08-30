<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\Request;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Request controller - Manage brand as Admin
 */
class RequestController extends Controller
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
     * Return a List of requests available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = Request::find();
        
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return Request
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a Request 
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new request
        $model = new Request();
 
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->request_status = Request::STATUS_PENDING;
        
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
                    "message" => "We've faced a problem creating the Request, please contact us for assistance."
                ];
            }
        }
        
        Yii::info('[Request added for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request created successfully"
        ];
    }

    /**
     * Update Request 
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Request not found."
                ];
        }

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");

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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Request updated for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated"
        ];
    }
    
    /**
     * Update Request Status to `started`
     * @param $id
     * @return array
     */
    public function actionStart($id)
    {
        $model = $this->findModel($id);
        
        $model->request_status = Request::STATUS_STARTED;
        
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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Request marked as started for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated"
        ];
    }
    
    /**
     * Update Request Status to `delivered`
     * @param $id
     * @return array
     */
    public function actionDeliver($id)
    {
        $model = $this->findModel($id);
        
        $model->request_status = Request::STATUS_DELIVERED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Request marked as delivered for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated"
        ];
    }
    
    /**
     * Update Request Status to `cancelled`
     * @param $id
     * @return array
     */
    public function actionCancel($id)
    {
        $model = $this->findModel($id);
        
        $model->request_status = Request::STATUS_CANCELLED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

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
                    "message" => "We've faced a problem updating the Request, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Request marked as cancelled for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated"
        ];
    }
    
    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Request the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
