<?php

namespace candidate\modules\v1\controllers;

use candidate\models\Request;
use candidate\models\RequestApplication;
use common\models\RequestInterview;
use Yii;
use yii\db\Expression;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * Request controller
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
        $behaviors['authenticator']['except'] = [
            'options'
        ];

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
     * Return a List of matching jobs/request
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $keyword = Yii::$app->request->get("query");
        $position_type = Yii::$app->request->get("position_type");
        $followup_interval = Yii::$app->request->get("followup_interval");

        $query = Request::find()
            //->joinWith(['requestSkills'])
            ->filterByCandidateSkills(Yii::$app->user->getId())
            ->joinWith('company')
            ->andWhere(['company.currency_code' => $currency])
            ->andWhere(['NOT IN', 'request_status', [Request::STATUS_DELIVERED, Request::STATUS_FINISHED]])
            ->addOrderBy('request_created_datetime DESC');

        if($keyword) {
            $query->filterByKeyword($keyword);
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
     * @return void
     */
    public function actionView($id) {
        return $this->findModel($id);
    }

    /**
     * @return void
     */
    public function actionApplications() {
        $query = Yii::$app->user->identity
            ->getRequestApplications()
            ->addOrderBy('created_at DESC');;

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionInterviewRequests()
    {
        $application_uuid = Yii::$app->request->get('application_uuid');
        $request_uuid = Yii::$app->request->get('request_uuid');
        $staff_id = Yii::$app->request->get('staff_id');
        $from = Yii::$app->request->get('from');
        $to = Yii::$app->request->get('to');

        //RequestInterview::STATUS_REQUESTED

        $query = RequestInterview::find()
            ->filterDateRange($from, $to)
            ->joinWith(['request'])
            ->andWhere([
                'candidate_id' => Yii::$app->user->getId(),
                'request_interview.status' => RequestInterview::STATUS_SCHEDULED
            ])
            ->andWhere(new Expression('interview_at > NOW()'))
            ->andWhere(['NOT IN', 'request.request_status', [
                Request::STATUS_DELIVERED,
                Request::STATUS_FINISHED,
                Request::STATUS_CANCELLED
            ]])
            ->orderBy("interview_at ASC");

        if($application_uuid) {
            $query->andWhere(['application_uuid' => $application_uuid]);
        }

        if($request_uuid) {
            $query->andWhere(['request_uuid' => $request_uuid]);
        }

        if($staff_id) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * apply for job / request
     * @return void
     */
    public function actionApply($id) {
        $model = new RequestApplication();
        $model->candidate_id = Yii::$app->user->getId();
        $model->request_uuid = $id;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        if(YII_ENV == 'prod') {

            $name = $model->candidate->candidate_name? $model->candidate->candidate_name: $model->candidate->candidate_name_ar;

            Yii::$app->eventManager->track(
                'Candidate Application Received',
                [
                    'company_id' => $model->request->company_id,
                    'request_uuid' => $model->request_uuid,
                    'candidate' => $name
                ]);
        }

        return [
            "operation" => "success",
            "candidateApplication" => RequestApplication::findOne($model->application_uuid),
            "message" => Yii::t('candidate',"Application sent successfully")
        ];
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = Request::find()
            ->andWhere (['request_uuid' => $id])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}