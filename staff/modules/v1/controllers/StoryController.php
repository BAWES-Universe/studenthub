<?php

namespace staff\modules\v1\controllers;

use Yii;
use common\models\StoryActivity;
use common\models\Story;
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
        $model = Story::find()->where(['staff_id' => Yii::$app->user->getId(),'story_status' => Story::STATUS_STARTED])->all();

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
     * @param $id
     * @return Request
     * @throws NotFoundHttpException
     */
    public function actionAllOldStories()
    {
        $model = Story::find()->andWhere(['staff_id' => Yii::$app->user->getId()])
            ->andWhere(['<>','story_status',Story::STATUS_STARTED])
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
     * Return a List of stories.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $status = Yii::$app->request->get('story_status',null);

        $query = Story::find()
            ->joinWith('request')
            ->andWhere([
                'NOT IN',
                'request_status',
                [Request::STATUS_CANCELLED, Request::STATUS_DELIVERED]
            ]);

        if ($status == 'rejected') {
            $query->andWhere(['story_status' => Story::STATUS_REJECTED]);
        } else if ($status == 'unstarted') {
            $query->andWhere(['story_status' => Story::STATUS_UNSTARTED]);
        } else {
            $query->andWhere(['or',
                ['story_status' => Story::STATUS_UNSTARTED],
                ['story_status' => Story::STATUS_REJECTED]
            ]);
        }

        $query->orderBy(
            [
                'request.request_priority' => SORT_ASC,
                new \yii\db\Expression('FIELD (story_status, 4,0)'),
                'request_created_datetime' => SORT_ASC
            ]);


        return new ActiveDataProvider([
            'query' => $query
        ]);
    }


    /**
     * Stop working on story
     * @return array
     */
    public function actionChangeStoryStatus()
    {
        $status = (int) Yii::$app->request->getBodyParam("status");

        if (!in_array ($status, [StoryActivity::STATUS_UNSTARTED, StoryActivity::STATUS_STARTED,StoryActivity::STATUS_FINISHED,StoryActivity::STATUS_DELIVERED,StoryActivity::STATUS_REJECTED, StoryActivity::STATUS_ACCEPTED])){
            return [
                "operation" => "error",
                "message" => Yii::t ('app', "Invalid status!")
            ];
        }

        if ($status == Story::STATUS_STARTED ) {
            $exist = Story::find()->andWhere(['staff_id'=>Yii::$app->user->getId(),'story_status'=>StoryActivity::STATUS_STARTED])->exists();
            if ($exist) {
                return [
                    "operation" => "error",
                    "message" => "Please complete your existing story. You can only work one story at a time"
                ];
            }
        }
        $storyUuid = Yii::$app->request->getBodyParam("story_uuid");
        $story =  $this->findModel($storyUuid);

        // Attempt to create new request
        $model = new StoryActivity();

        if ($status) {

            if ($status != StoryActivity::STATUS_UNSTARTED)
                $model->staff_id = Yii::$app->user->getId();

            $model->story_uuid = $storyUuid;
            $model->activity_status = $status;

            $last_story_acitivty_model = StoryActivity::find()
                ->where(['story_uuid' => $storyUuid])
                ->orderBy('activity_created_at desc')
                ->one();

            if ($last_story_acitivty_model) {
                $activity_created_at = new \DateTime(date('Y-m-d H:i:s', strtotime($last_story_acitivty_model->activity_created_at)));
                $activity_last_updated_at = new \DateTime(date('Y-m-d H:i:s'));
                $diff = $activity_created_at->diff($activity_last_updated_at);
                $daysInSecs = $diff->format('%r%a') * 24 * 60 * 60;
                $hoursInSecs = $diff->h * 60 * 60;
                $minsInSecs = $diff->i * 60;

                $seconds = $daysInSecs + $hoursInSecs + $minsInSecs + $diff->s;

                $last_story_acitivty_model->activity_time_spent = $seconds;
                $last_story_acitivty_model->save(false);
            }

            if (!$model->save()) {
                if (isset($model->errors)) {
                    return [
                        "operation" => "error",
                        "message" => $model->errors
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => "We've faced a problem creating the Request, please contact us for assistance."
                    ];
                }
            }
        }

        $company = ($model && $model->company && $model->company->company_name) ? $model->company->company_name : ' - ';
        $story = ($story  && $story->request && $story->request->request_position_title) ? $story->request->request_position_title : ' - ';
        return [
            "operation" => "success",
            "message" => Yii::$app->user->identity->staff_name . " started " . $story  . ' for ' . $company,
            "last_story_acitivty_model" => $last_story_acitivty_model,
            "newStoryActivity" => $model
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
        $model = Story::findOne($id);

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
