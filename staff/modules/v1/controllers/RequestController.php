<?php

namespace staff\modules\v1\controllers;

use Yii;
use staff\models\Note;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Request;
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
        $company_id = Yii::$app->request->get("company_id");
        $company_name = Yii::$app->request->get("company_name");
        $request_status = Yii::$app->request->get("request_status");
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");
        $position_type = Yii::$app->request->get("position_type");

        $query = Request::find()
            ->orderBy('request_created_datetime DESC');

        if($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if($company_name) {
            $query->joinWith('company')
                ->andWhere([
                    'OR',
                    ['like', 'company_common_name_en', $company_name],
                    ['like', 'company_common_name_ar', $company_name],
                    ['like', 'company_name', $company_name]
                ]);
        } 

        if($request_status) {
            $query->andWhere(['request_status' => $request_status]);
        }

        if($position_type) {
            $query->filterByType($position_type);
        }

        if($start_date) {
            $query->startDate($start_date);
        } 

        if($end_date) {
            $query->endDate($end_date);
        } 

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of requests available.
     * @return ActiveDataProvider
     */
    public function actionListActive()
    {
        $company_id = Yii::$app->request->get("company_id");
        $position_type = Yii::$app->request->get("position_type");

        $query = Request::find()
            ->andWhere(['request_status' => Request::STATUS_STARTED])
            ->orderBy('request_created_datetime DESC');

        if($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if($position_type) {
            $query->filterByType($position_type);
        }

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
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");
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
                    "message" => "We've faced a problem creating the Request, please contact us for assistance."
                ];
            }
        }

        //save activity
        $model->createRequestActivity('I have created this request');

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
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");

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
        //save activity
        $model->createRequestActivity('I have updated this request');

        Yii::info('[Request updated for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated",
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
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

        if ($model->getActiveSuggestions()->count() > 0) {
            return [
                "operation" => "error",
                "message" => "Please clear all suggestions by accepting or rejecting before being able to proceed with mark delivered / cancellation"
            ];
        }

        $model->request_status = Request::STATUS_DELIVERED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

        if (!$model->request_feedback) {
            return [
                "operation" => "error",
                "message" => "Please provide Feedback"
            ];
        }

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

        $model->createRequestActivity('I have completed this request and '. $model->request_feedback);

        Yii::info('[Request marked as delivered for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated",
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
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

        if ($model->getActiveSuggestions()->count() > 0) {
            return [
                "operation" => "error",
                "message" => "Please clear all suggestions by accepting or rejecting before being able to proceed with mark delivered / cancellation"
            ];
        }

        $model->request_status = Request::STATUS_CANCELLED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

        if (!$model->request_feedback) {
            return [
                "operation" => "error",
                "message" => "Please provide Feedback"
            ];
        }

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

        $model->createRequestActivity('I have cancelled this request because '. $model->request_feedback);

        Yii::info('[Request marked as cancelled for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated",
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
        ];
    }

    /**
     * Allows staff to add request activity
     */
    public function actionAddActivity() {

        $request_uuid = Yii::$app->request->getBodyParam('request_uuid');

        $model = $this->findModel($request_uuid);

        $modelActivity = new Note();
        $modelActivity->contact_uuid = Yii::$app->request->getBodyParam('contact_uuid');
        $modelActivity->note_type = Yii::$app->request->getBodyParam('note_type');
        $modelActivity->company_id = $model->company_id;
        $modelActivity->request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $modelActivity->note_text = Yii::$app->request->getBodyParam("detail");

        if (!$modelActivity->save())
        {
            if(isset($modelActivity->errors)){
                return [
                    "operation" => "error",
                    "message" => $modelActivity->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem adding the request activity, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Request activity successfully added",
            "request_updated_at" => Request::findOne($modelActivity->request_uuid)->request_updated_datetime
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
