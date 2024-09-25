<?php

namespace staff\modules\v1\controllers;

use common\models\RequestInterview;
use common\models\RequestSkill;
use common\models\Story;
use Yii;
use staff\models\Staff;
use staff\models\Note;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Request;
use staff\models\Suggestion;
use common\models\RequestChecklist;
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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $company_id = Yii::$app->request->get("company_id");
        $company_name = Yii::$app->request->get("company_name");
        $request_status = Yii::$app->request->get("request_status");
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");
        $position_type = Yii::$app->request->get("position_type");
        $followup_interval = Yii::$app->request->get("followup_interval");
        $contact_uuid = Yii::$app->request->get("contact_uuid");
        $q = Yii::$app->request->get("query");

        //todo: filter by contact_uuid

        $statusOrder = [ "'".Request::STATUS_RE_WORK."'" , "'".Request::STATUS_PENDING."'","'".Request::STATUS_STARTED."'","'".Request::STATUS_FINISHED."'","'".Request::STATUS_DELIVERED."'","'".Request::STATUS_CANCELLED."'"];

        $query = Request::find()
                    ->joinWith(['company', 'requestSkills'])
                    ->orderBy(new yii\db\Expression(sprintf("FIELD(request_status, %s)", implode(",", $statusOrder))));

        if($currency) {
            $query->andWhere(['company.currency_code' => $currency]);
        }

        $query->addOrderBy('request_created_datetime ASC');

        if($company_id) {
            $query->andWhere(['request.company_id' => $company_id]);
        }

        if($contact_uuid) {
            $query->andWhere(['contact_uuid' => $contact_uuid]);
        }

        /*if(!$contact_uuid || !$company_id) {
            $query->activeRequest();
        }*/

        if ($q) {

            $query->andFilterWhere([
                'or',
                ['like', 'request.request_position_title', $q],
                ['like', 'request.request_job_description', $q],
                ['like', 'request.request_additional_info', $q],
                ['like', 'company_common_name_en', $q],
                ['like', 'company_common_name_ar', $q],
                ['like', 'company_name', $q],
                ['like', 'request_skill.skill', $q],
            ]);
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
            if($request_status == 'need-update')
                $query->needUpdate();
            else if ($request_status != 'latest')
                $query->andWhere(['request_status' => $request_status]);
        }

        if($position_type) {
            $query->filterByType($position_type);
        }

        if($start_date) {
            $query->startDate(date('Y-m-d', strtotime ($start_date)));
        }

        if($end_date) {
            $query->endDate(date('Y-m-d', strtotime ($end_date)));
        }

        if ($request_status != 'latest') {
            $statusOrder = [ "'".Request::STATUS_RE_WORK."'" , "'".Request::STATUS_PENDING."'","'".Request::STATUS_STARTED."'","'".Request::STATUS_FINISHED."'","'".Request::STATUS_DELIVERED."'","'".Request::STATUS_CANCELLED."'"];
            $query->orderBy(new yii\db\Expression(sprintf("FIELD(request_status, %s)", implode(",", $statusOrder))));
        }

        if ($followup_interval && $request_status != 'latest') {
            $query->orderByFollowupInterval();
        } else {
            $query->addOrderBy('request_created_datetime DESC');
        }

        /*if(Yii::$app->user->identity->staff_role == Staff::ROlE_CONSULTANT) {
            $query->joinWith (['stories'])
                ->andWhere ([
                    //'request.staff_id' => Yii::$app->user->getId (),
                    'story.staff_id' => Yii::$app->user->getId ()
                ]);
            //$query->andWhere(['staff_id' => Yii::$app->user->getId ()]);
        }*/

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
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $keyword = Yii::$app->request->get("query");
        $company_id = Yii::$app->request->get("company_id");
        $position_type = Yii::$app->request->get("position_type");
        $followup_interval = Yii::$app->request->get("followup_interval");
        $contact_uuid = Yii::$app->request->get("contact_uuid");
        $request_status = Yii::$app->request->get("request_status");

        $query = Request::find();
            //->joinWith(['requestSkills']);

        if($currency) {
            $query->joinWith('company')
                ->andWhere(['company.currency_code' => $currency]);
        }

        if($keyword) {
            $query->filterByKeyword($keyword);
        }

        if($company_id) {
            $query->andWhere(['request.company_id' => $company_id]);
        }

        if($contact_uuid) {
            $query->andWhere(['contact_uuid' => $contact_uuid]);
        } else {
            $query->needUpdate();//activeRequest
        }

        if($position_type) {
            $query->filterByType($position_type);
        }

        if($request_status) {
            if($request_status == 'need-update')
                $query->needUpdate();
            else if ($request_status != 'latest') {
                $query->andWhere(['request_status' => $request_status]);
            }
        }

        if ($request_status != 'latest') {
            $statusOrder = [ "'".Request::STATUS_RE_WORK."'" , "'".Request::STATUS_PENDING."'","'".Request::STATUS_STARTED."'","'".Request::STATUS_FINISHED."'","'".Request::STATUS_DELIVERED."'","'".Request::STATUS_CANCELLED."'"];
            $query->orderBy(new yii\db\Expression(sprintf("FIELD(request_status, %s)", implode(",", $statusOrder))));
        }

        if ($followup_interval && $request_status != 'latest') {
            $query->orderByFollowupInterval();
        } else {
            $query->addOrderBy('request_created_datetime DESC');
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $request_uuid
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionApplications($request_uuid)
    {
        $query = $this->findModel($request_uuid)
            ->getRequestApplication()
            ->orderBy("created_at DESC");

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionInterviewRequests()
    {
        $status = Yii::$app->request->get('status');
        $application_uuid = Yii::$app->request->get('application_uuid');
        $request_uuid = Yii::$app->request->get('request_uuid');
        $fulltimer_uuid = Yii::$app->request->get('fulltimer_uuid');
        $candidate_id = Yii::$app->request->get('candidate_id');
        $staff_id = Yii::$app->request->get('staff_id');
        $from = Yii::$app->request->get('from');
        $to = Yii::$app->request->get('to');

            //RequestInterview::STATUS_REQUESTED

        $query = RequestInterview::find()
            ->filterDateRange($from, $to)
            ->joinWith(['request'])
            ->andWhere(['NOT IN', 'request.request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_FINISHED,
                Request::STATUS_CANCELLED
            ]]);

        if ($status == RequestInterview::STATUS_SCHEDULED) {
            $query->orderBy("interview_at ASC");
        } else if ($status == RequestInterview::STATUS_REQUESTED) {
            $query->orderBy("interview_at ASC");
        } else {
            $query->orderBy("created_at ASC");
        }

        if($application_uuid) {
            $query->andWhere(['application_uuid' => $application_uuid]);
        }

        if($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if($fulltimer_uuid) {
            $query->andWhere(['fulltimer_uuid' => $fulltimer_uuid]);
        }

        if($candidate_id) {
            $query->andWhere(['candidate_id' => $candidate_id]);
        }

        if($staff_id) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        if ($status) {
            $query->andWhere(['request_interview.status' => $status]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionAcceptInterviewRequest($id)
    {
        $model = RequestInterview::find()
            ->andWhere(['request_interview_uuid' => $id])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->status = RequestInterview::STATUS_SCHEDULED;
        $model->internal_note = Yii::$app->request->getBodyParam('internal_note');
        $model->staff_id = Yii::$app->request->getBodyParam('staff_id');
        $model->interview_note = Yii::$app->request->getBodyParam('interview_note');

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Request accepted successfully"
        ];
    }

    public function actionRejectInterviewRequest($id)
    {
        $model = RequestInterview::find()
                ->andWhere(['request_interview_uuid' => $id])
                ->one();

        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->status = RequestInterview::STATUS_REJECTED;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Request rejected successfully"
        ];
    }

    /**
     * Return a List of requests available.
     * @return ActiveDataProvider
     */
    public function actionPendingRequest()
    {
        $company_name = Yii::$app->request->get("company_name");
        $followup_interval = Yii::$app->request->get("followup_interval");
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $query = Request::find()
            ->joinWith('suggestions')
            ->where([
                'suggestion.suggestion_status' => Suggestion::TYPE_SUGGESTED,
            ]);

        if($currency) {
            $query->joinWith('company')
                ->andWhere(['company.currency_code' => $currency]);
        }

        if($company_name) {
            $query->joinWith('company')
                ->andWhere([
                    'OR',
                    ['like', 'company.company_common_name_en', $company_name],
                    ['like', 'company.company_common_name_ar', $company_name],
                    ['like', 'company.company_name', $company_name]
                ]);
        }

        $query->activeRequest();

        if ($followup_interval) {
            $query->orderByFollowupInterval();
        } else {
            $query->orderBy('request_created_datetime DESC');
        }
        
        if(Yii::$app->user->identity->staff_role == Staff::ROlE_SALE)
        {
            $query->andWhere(['staff_id' => Yii::$app->user->getId ()]);
        }

        /*if(Yii::$app->user->identity->staff_role == Staff::ROlE_CONSULTANT) {
            $query->joinWith (['stories'])
                ->andWhere ([
                    //'request.staff_id' => Yii::$app->user->getId (),
                    'story.staff_id' => Yii::$app->user->getId ()
                ]);
        }*/

        return new ActiveDataProvider([
            'query' => $query,
//            'pagination' => false
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
        $model->staff_id = Yii::$app->user->getId();
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        $model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_location = Yii::$app->request->getBodyParam("location");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");
        $model->request_status = Request::STATUS_PENDING;
        $model->no_of_employees_per_story = Yii::$app->request->getBodyParam("no_of_employees_per_story");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");

        $model->our_fees_unit = Yii::$app->request->getBodyParam("our_fees_unit");
        $model->our_fees = Yii::$app->request->getBodyParam("our_fees");

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
        $model->requestNotification();

        Yii::info('[Request added for company '.$model->company->company_name.'] '.
            $model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        /*Yii::$app->eventManager->track(
            'Request added',
            [
                'company' => $model->company->company_name,
                'company_id' => $model->company_id,
                'request_uuid' => $model->request_uuid,
                'staff' => Yii::$app->user->identity->staff_name,
                'title' => $model->request_position_title
            ]);*/

        return [
            "operation" => "success",
            "message" => "Request created successfully",
            "request" => $model
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

        $model->setScenario('staffUpdate');

        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->contact_uuid = Yii::$app->request->getBodyParam("contact_uuid");
        $model->request_position_type = (int)Yii::$app->request->getBodyParam("position_type");
        $model->request_position_title = Yii::$app->request->getBodyParam("position_title");
        //$model->request_number_of_employees = Yii::$app->request->getBodyParam("number_of_employees");
        $model->request_location = Yii::$app->request->getBodyParam("location");
        $model->request_additional_info = Yii::$app->request->getBodyParam("additional_info");
        $model->request_job_description = Yii::$app->request->getBodyParam("job_description");
        $model->request_compensation = Yii::$app->request->getBodyParam("compensation");
        $model->no_of_employees_per_story = Yii::$app->request->getBodyParam("no_of_employees_per_story");
        $model->gender = Yii::$app->request->getBodyParam("gender");
        $model->nationality_id = Yii::$app->request->getBodyParam("nationality_id");

        $model->our_fees_unit = Yii::$app->request->getBodyParam("our_fees_unit");
        $model->our_fees = Yii::$app->request->getBodyParam("our_fees");

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

        Yii::info('[Request updated for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        /*Yii::$app->eventManager->track(
            'Request updated',
            [
                'company' => $model->company->company_name,
                'company_id' => $model->company_id,
                'request_uuid' => $model->request_uuid,
                'staff' => Yii::$app->user->identity->staff_name,
                'title' => $model->request_position_title
            ]);*/

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

//        $count = $model->getStories()
//            ->andWhere(['!=','story_status',Story::STATUS_DELIVERED])
//            ->count();
//
//        if ($count) {
//            return [
//                "operation" => "error",
//                "message" => "Please deliver all stories before deliver request"
//            ];
//        }

        //feel new not null fields for old requests

        if(!$model->request_job_description) {
            $model->request_job_description = '.';
        }

        if(!$model->request_compensation) {
            $model->request_compensation = '.';
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

        /*Yii::$app->eventManager->track(
            'Request marked as delivered',
            [
                'company' => $model->company->company_name,
                'company_id' => $model->company_id,
                'request_uuid' => $model->request_uuid,
                'staff' => Yii::$app->user->identity->staff_name,
                'title' => $model->request_position_title
            ]);*/

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

        //feel new not null fields for old requests

        if(!$model->request_job_description) {
            $model->request_job_description = '.';
        }

        if(!$model->request_compensation) {
            $model->request_compensation = '.';
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
            "staff" => $model->staff,
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
        ];
    }

    /**
     * Update Request Status
     * @param $id
     * @return array
     */
    public function actionUpdateStatus($id)
    {
        $model = $this->findModel($id);

        $model->request_status = Yii::$app->request->getBodyParam("status");

        if (!$model->save()) {
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

        $status = ($model->request_status == Request::STATUS_RE_WORK) ? 'Re-work' : 'Finished by recruitment';
        $model->createRequestActivity('I have set request status as `'.$status.'`');

        Yii::info('[Request marked as '.$status.' for company '.$model->company->company_name.'] '.$model->request_position_title. ' By '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated",
            "staff" => $model->staff,
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
        ];
    }

    /**
     * Assign staff to request
     * @param $id
     * @return array
     */
    public function actionAssign($id)
    {
        $staff_id = Yii::$app->request->getBodyParam("staff_id");

        $model = $this->findModel($id);

        $model->staff_id = Yii::$app->request->getBodyParam("staff_id");

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

        $staff = Staff::find()->andWhere(['staff_id' => $staff_id])->one();

        $model->createRequestActivity('I have assign this request to '. $staff->staff_name);

        Yii::info('[Request assigned to '.$staff->staff_name.'] '.$model->request_position_title. ' @' .$model->company->company_name .' By '. Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Request successfully updated",
            "request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
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
     * Allows staff to update request interval
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateInterval($id) {

        $request_uuid = $id;
        $hours = Yii::$app->request->getBodyParam('hours');
        $feedback = Yii::$app->request->getBodyParam('reason');

        $model = $this->findModel($request_uuid);
        $model->num_hours_followup_interval = $hours;
        $model->save(false);

        $days = ($hours < 24) ? $hours.' hours' : round($hours/24).' days';
        $reason = Yii::$app->user->identity->staff_name." has updated the followup interval for this ";
        $reason .= "request to  ".$days." with feedback: ".$feedback;

        $modelActivity = new Note();
        $modelActivity->request_uuid = $request_uuid;
        $modelActivity->note_type = \common\models\Note::TYPE_INTERNAL_NOTE;
        $modelActivity->company_id = $model->company_id;
        $modelActivity->note_text = $reason;

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
     * return request checklist
     */
    public function actionListChecklist()
    {
        return RequestChecklist::find()
            ->all();
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
        $model = Request::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
