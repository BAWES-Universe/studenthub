<?php

namespace company\modules\v1\controllers;

use common\models\RequestSkill;
use Yii;
use company\models\Note;
use yii\data\ActiveDataProvider;
use company\models\Request;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;


/**
 * Request controller - Manage brand as Admin
 */
class RequestController extends BaseController
{
    /**
     * Return a List of requests available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $company_id = Yii::$app->request->get("company_id");
        $company_name = Yii::$app->request->get("company_name");
        $request_status = Yii::$app->request->get("request_status");
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");

        $query = Request::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
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

        if($start_date) {
            $query->startDate($start_date);
        } 

        if($end_date) {
            $query->endDate($end_date);
        }

        if($request_status) {
            $query->andWhere(['request_status' => $request_status]);
        }

        Yii::$app->response->headers->set("X-Pending-Count",
            Request::totalRequestCountByStatus(
                Request::STATUS_PENDING,
                $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date
            ));

        Yii::$app->response->headers->set("X-Cancelled-Count",
            Request::totalRequestCountByStatus(
                Request::STATUS_CANCELLED,
                $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date));

        Yii::$app->response->headers->set("X-Completed-Count",
            Request::totalRequestCountByStatus(Request::STATUS_DELIVERED, $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date));

        Yii::$app->response->headers->set("X-Finished-Count",
            Request::totalRequestCountByStatus(Request::STATUS_FINISHED, $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date));

        Yii::$app->response->headers->set("X-Rework-Count",
            Request::totalRequestCountByStatus(Request::STATUS_RE_WORK, $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date));

        Yii::$app->response->headers->set("X-Open-Count",
            Request::totalRequestCountByStatus(Request::STATUS_STARTED, $companyIds,
                $company_id,
                $company_name,
                $start_date,
                $end_date));

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
        $companyIds = Yii::$app->companyManager->getCompanyIds();
        
        $company_id = Yii::$app->request->get("company_id");
        $position_type = Yii::$app->request->get("position_type");

        $query = Request::find()
            ->andWhere(['IN', 'company_id', $companyIds])//current company and childs
            ->activeRequest()
            ->orderBy('request_created_datetime DESC');

        if($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if($position_type) {
            $query->andWhere(['request_position_type' => $position_type]);
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
        $company = Yii::$app->companyManager->getCompany();

        // Attempt to create new request
        $model = new Request();

        $model->company_id = $company->company_id;
        $model->contact_uuid = Yii::$app->user->identity->getId();
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->no_of_employees_per_story = Yii::$app->request->getBodyParam("no_of_employees_per_story", $model->request_number_of_employees);
        $model->request_location = Yii::$app->request->getBodyParam("location");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->request_status = Request::STATUS_PENDING;
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");

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
                    "message" => Yii::t('company',"We've faced a problem creating the Request, please contact us for assistance.")
                ];
            }
        }

        $requestSkills = Yii::$app->request->getBodyParam('requestSkills');

        if(!$requestSkills) {
            $requestSkills = [];
        }

        foreach ($requestSkills as $requestSkill) {

            if(empty($requestSkill['skill'])) {
                continue;
            }

            $modelRS = new RequestSkill();
            $modelRS->request_uuid = $model->request_uuid;
            $modelRS->skill = $requestSkill['skill'];
            if(!$modelRS->save()) {
                return [
                    "operation" => "error",
                    "message" => $modelRS->errors
                ];
            }
        }

        //save activity
        $model->createRequestActivity('I have created this request');

        Yii::info('[Request added for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->contact_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Request created successfully")
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
                    "message" => Yii::t('company',"Request not found.")
                ];
        }

        $model->contact_uuid = Yii::$app->user->identity->getId();
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_location = Yii::$app->request->getBodyParam("location");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");

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
                    "message" => Yii::t('company',"We've faced a problem updating the Request, please contact us for assistance.")
                ];
            }
        }

        $alreadyAdded = ArrayHelper::getColumn($model->getRequestSkills()->all(), "skill");

        $requestSkills = Yii::$app->request->getBodyParam('requestSkills');

        if(!$requestSkills) {
            $requestSkills = [];
        }

        foreach ($requestSkills as $requestSkill) {

            if(empty($requestSkill['skill'])) {
                continue;
            }

            if(in_array($requestSkill['skill'], $alreadyAdded)) {
                continue;
            }

            $modelRS = new RequestSkill();
            $modelRS->request_uuid = $model->request_uuid;
            $modelRS->skill = $requestSkill['skill'];
            if(!$modelRS->save()) {
                return [
                    "operation" => "error",
                    "message" => $modelRS->errors
                ];
            }
        }

        //remove deleted / not provided in request

        RequestSkill::deleteAll([
            'AND',
            ['request_uuid' => $model->request_uuid],
            ["NOT IN", 'skill', ArrayHelper::getColumn($requestSkills, "skill")]
        ]);

        //save activity
        $model->createRequestActivity('I have updated this request');

        Yii::info('[Request updated for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->contact_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Request successfully updated"),
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
                "message" => Yii::t('company',"Please clear all suggestions by accepting or rejecting before being able to proceed with mark delivered / cancellation")
            ];
        }

        $model->request_status = Request::STATUS_DELIVERED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

        if (!$model->request_feedback) {
            return [
                "operation" => "error",
                "message" => Yii::t('company',"Please provide Feedback")
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
                    "message" => Yii::t('company',"We've faced a problem updating the Request, please contact us for assistance.")
                ];
            }
        }

        $model->createRequestActivity('I have completed this request and '. $model->request_feedback);

        Yii::info('[Request marked as delivered for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->contact_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Request successfully updated"),
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
                "message" => Yii::t('company',"Please clear all suggestions by accepting or rejecting before being able to proceed with mark delivered / cancellation")
            ];
        }

        $model->request_status = Request::STATUS_CANCELLED;
        $model->request_feedback = Yii::$app->request->getBodyParam("feedback");

        if (!$model->request_feedback) {
            return [
                "operation" => "error",
                "message" => Yii::t('company',"Please provide Feedback")
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
                    "message" => Yii::t('company',"We've faced a problem updating the Request, please contact us for assistance.")
                ];
            }
        }

        $model->createRequestActivity('I have cancelled this request because '. $model->request_feedback);

        Yii::info('[Request marked as cancelled for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->contact_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Request successfully updated"),
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
                    "message" => Yii::t('company',"We've faced a problem adding the request activity, please contact us for assistance.")
                ];
            }
        }

        if(YII_ENV == 'prod') {

            Yii::$app->eventManager->track('Request Activity Added',
                    [
                        'contact_uuid' => $modelActivity->contact_uuid,
                        'request_uuid' => $modelActivity->request_uuid,
                        'note_text' => $modelActivity->note_text
                    ]);
        }

        return [
            "operation" => "success",
            "message" => Yii::t('company',"Request activity successfully added"),
            "request_updated_at" => Request::findOne($modelActivity->request_uuid)->request_updated_datetime
        ];
    }

    /**
     * @return array
     */
    public function actionRequestCount() {
        $company = Yii::$app->companyManager->getCompany();

        $count = $company->getRequests()
            ->activeRequest()
            ->handleByStaff()
            ->count();

        return [
            "active_request_count" => $count
        ];
    }

    /**
     * check if request updated
     */
    public function actionIsRequestUpdated($id) {

        $request = $this->findModel ($id);

        return [
            "request_updated_datetime" => $request->request_updated_datetime
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
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $model = Request::find()
            ->andWhere(['request_uuid' => $id])
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->one();
            
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
