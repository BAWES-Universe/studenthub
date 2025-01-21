<?php

namespace staff\modules\v1\controllers;

use staff\models\Staff;
use Yii;
use common\models\StoryActivity;
use staff\models\Story;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Request;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * Story controller - Manage brand as Admin
 */
class StoryController extends Controller
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
     * Assign staff to story
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

        $model->request->createRequestActivity('I have assign story to '. $staff->staff_name);

        Yii::info('[Story assigned to '.$staff->staff_name.'] '. $model->request->request_position_title. ' @' .$model->company->company_name .' By '. Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Story successfully updated",
            //"request_updated_at" => Request::findOne($model->request_uuid)->request_updated_datetime
        ];
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
     * @param $id
     * @return Request
     * @throws NotFoundHttpException
     */
    public function actionActiveStory()
    {
        $id = Yii::$app->request->get('id', Yii::$app->user->getId());

        $model = Story::find()
            ->andWhere(['staff_id' => $id])
            ->andWhere(['OR',
                    ['story_status' => Story::STATUS_STARTED],
                    ['story_status' => Story::STATUS_REWORK],
                ])
            ->all();

        if ($model !== null) {
            return [
                "operation" => "success",
                "body" => $model
            ];

        } else {
            return [
                "operation" => "error",
                "message" => Yii::t ('app', "There is no active story")
            ];
        }
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionAllOldStories()
    {
        $id = Yii::$app->request->get('id', Yii::$app->user->getId());

        $query = Story::find();
        $query->andWhere(['staff_id' => $id])
            ->andWhere(['!=', 'story_status', Story::STATUS_STARTED])
            ->orderBy('story_last_updated_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of stories.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $status = Yii::$app->request->get('story_status');
        $position_type = Yii::$app->request->get('position_type');
        $keyword = Yii::$app->request->get("query");

        $query = Story::find()
            ->joinWith('request');

        if($currency) {
            $query->joinWith(['company'])
                ->andWhere(['company.currency_code' => $currency]);
        }

        if ($position_type) {
            $query->andWhere(['request.request_position_type' => $position_type]);
        }

        if ($keyword) {
            $query->andWhere([
                'OR',
                ['like', 'request.request_position_title', $keyword]
            ]);
        }

        if ($status == 10) {
            $query->orderBy('request.request_created_datetime DESC');
            $query->needUpdate();//activeRequest
        } else {
            if ($status) {
                $status = ($status == '9' ? 0 : $status);
                $query->andWhere(['story_status' => $status]);
            } else {
                $query->needUpdate();//activeRequest
            }

            $statusOrder = [ "'".Request::STATUS_RE_WORK."'" , "'".Request::STATUS_PENDING."'","'".Request::STATUS_STARTED."'","'".Request::STATUS_FINISHED."'","'".Request::STATUS_DELIVERED."'","'".Request::STATUS_CANCELLED."'"];
            $query->orderBy(new yii\db\Expression(sprintf("FIELD(request.request_status, %s)", implode(",", $statusOrder))));

//            $query->orderBy(
//                [
//                    'request.request_priority' => SORT_ASC,
//                    new \yii\db\Expression('FIELD (story_status, 4,0)'),
//                    'request_created_datetime' => SORT_ASC
//                ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * create story in request
     */
    public function actionCreateStory() {

        $request_uuid = Yii::$app->request->post('request_uuid');
        $employee = Yii::$app->request->post('employee');
        $request = Request::findOne($request_uuid);

        if (!$request) {
            return [
                "operation" => "error",
                "message" => Yii::t ('app', "Invalid Request")
            ];
        }
        if ($request->request_status == \common\models\Request::STATUS_CANCELLED) {
            return [
                "operation" => "error",
                "message" => Yii::t ('app', "This request has been cancelled already")
            ];
        }

        $totalEmployee = $request->getStories()
            ->sum('number_of_employees');

        if (((int)$employee + (int)$totalEmployee) > (int)$request->request_number_of_employees) {
            $totalPending = (int)$request->request_number_of_employees - (int)$totalEmployee;
            $msg = "Employee limit cannot be greater then number of employee asked by client. maximum you can assign: $totalPending";
            return [
                "operation" => "error",
                "message" => Yii::t ('app', $msg)
            ];
        }

        // TODO URGENT : this is temp solution due to all of sudden deployment. need to change role base
        if (!in_array(Yii::$app->user->getId(),[98,106,102,119])) {
            return [
                "operation" => "error",
                "message" => Yii::t ('app', 'You are not allowed to perform this action')
            ];
        }

        $story = new Story();
        //$story->staff_id = Yii::$app->user->getId();
        $story->request_uuid = $request_uuid;
        $story->story_status = Story::STATUS_UNSTARTED;
        $story->number_of_employees = $employee;

        if (!$story->save())
        {
            if(isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the Story, please contact us for assistance."
                ];
            }
        }

        return [
            "operation" => "success",
            "body" => 'Story created successfully'
        ];
    }

    /**
     * change story status
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionChangeStoryStatus()
    {
        $status = (int) Yii::$app->request->getBodyParam("status");
        $status_lbl = 'started';
        $storyUuid = Yii::$app->request->getBodyParam("story_uuid");

        $arrStatus = [
            StoryActivity::STATUS_UNSTARTED,
            StoryActivity::STATUS_STARTED,
            StoryActivity::STATUS_FINISHED,
            StoryActivity::STATUS_DELIVERED,
            StoryActivity::STATUS_REJECTED,
            StoryActivity::STATUS_ACCEPTED,
            StoryActivity::STATUS_REWORK,
            StoryActivity::STATUS_STOPPED
        ];

        if (!in_array ($status, $arrStatus))
        {
            return [
                "operation" => "error",
                "message" => Yii::t ('app', "Invalid status!")
            ];
        }

        if ($status == Story::STATUS_STARTED || $status == Story::STATUS_REWORK ) {

            $exist = Story::find()
                ->andWhere([
                    'staff_id' => Yii::$app->user->getId(),
                    'story_status' => StoryActivity::STATUS_STARTED
                ])->one();

            if ($exist) {
                return [
                    "operation" => "error",
                    "message" => "Please complete your existing story. You can only work one story at a time",
                    "data" => $exist
                ];
            }
        }

        $story =  $this->findModel($storyUuid);

//        if ($story->story_status == Story::STATUS_DELIVERED && ($story->request->request_status = Request::STATUS_DELIVERED || $story->request->request_status = Request::STATUS_FINISHED)) {
//            \common\models\Request::updateAll(['request_status'=>Request::STATUS_RE_WORK],['request_uuid'=>$story->request->request_uuid]);
//            $status_lbl = 'Re-started';
//        }

        // Attempt to create new request

        $model = new StoryActivity();
        $model->staff_id = Yii::$app->user->getId();
        $model->story_uuid = $storyUuid;
        $model->activity_status = $status;

        if (!$model->save())
        {
            if(isset($model->errors)) {
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

        $totalDelivered = $story->request->getStories()
            ->andWhere(['story_status' => Story::STATUS_DELIVERED])
            ->count();

        $total = $story->request->getStories()->count();

        $nextStory = $story->request->getStories()
            ->andWhere(['story_status' => Story::STATUS_UNSTARTED])
            ->one();

        //if no story in current request, get story from other

        if(!$nextStory)
        {
            $nextStory = Story::find()
                ->andWhere(['story_status' => Story::STATUS_UNSTARTED])
                ->one();
        }

        $newStoryActivity = StoryActivity::find()
            ->where(['story_uuid' => $storyUuid])
            ->orderBy('activity_created_at desc')
            ->one();

        return [
            "operation" => "success",
            "message" => Yii::$app->user->identity->staff_name . " $status_lbl " . $story->request->request_position_title  . ' for ' . $model->company->company_name,
            //"last_story_acitivty_model" => $last_story_acitivty_model,
            "newStoryActivity" => $newStoryActivity,
            "totalDelivered" => $totalDelivered,
            "total" => $total,
            "nextStory" => $nextStory
        ];
    }

    /**
     * check if request updated
     */
    public function actionIsStoryUpdated($id) {

        $request = $this->findModel ($id);

        return [
            "story_last_updated_at" => $request->story_last_updated_at
        ];
    }

    /**
     * Finds the Request model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Story the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = Story::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
