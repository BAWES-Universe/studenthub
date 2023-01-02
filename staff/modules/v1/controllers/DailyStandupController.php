<?php

namespace staff\modules\v1\controllers;

use staff\models\Staff;
use Yii;
use common\models\DailyStandupAnswer;
use common\models\DailyStandupQuestion;
use common\models\StaffLeave;
use common\models\StaffWorkSession;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\rest\Controller;


class DailyStandupController extends Controller
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
     * Return a unanswered question
     */
    public function actionQuestion()
    {
        $subQuery = Yii::$app->user->identity
            ->getDailyStandupAnswers()
            ->select('question_uuid')
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."')"));

        return DailyStandupQuestion::find()
             ->andWhere(['NOT IN', 'question_uuid', $subQuery])
            ->one();
    }

    public function actionAnswer($question_uuid)
    {
        $question = $this->_findModel($question_uuid);
        $model = new DailyStandupAnswer();
        $model->staff_id = Yii::$app->user->getId();
        $model->question_uuid = $question_uuid;
        $model->question = $question->question;
        $model->answer = Yii::$app->request->getBodyParam('answer');

        if(!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => "Answer saved!"
        ];
    }

    /**
     * request for leave
     * @return array|string[]
     */
    public function actionLeaveRequest()
    {
        $model = new StaffLeave();
        $model->staff_id = Yii::$app->user->getId();
        $model->from_date = Yii::$app->request->getBodyParam('from_date');
        $model->to_date = Yii::$app->request->getBodyParam('to_date');
        $model->note = Yii::$app->request->getBodyParam('note');

        if(!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => "Request saved!"
        ];
    }

    /**
     * list staff on leave
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionAbsences()
    {
        $subQuery = StaffLeave::find()
            ->select('staff_id')
            ->andWhere(['staff_id' => Yii::$app->user->getId()])
            ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"));

        return Staff::find()
            ->andWhere(['NOT IN', 'staff_id', $subQuery])
            ->all();
    }

    /**
     * return current session
     * @return array|\yii\db\ActiveRecord|null
     */
    public function actionSession()
    {
         $session = StaffWorkSession::find()
            ->andWhere([
                'staff_id' => Yii::$app->user->getId()
            ])
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."') 
                AND total_minutes IS NULL"))
            ->one();

         $leave = StaffLeave::find()
             ->andWhere(['staff_id' => Yii::$app->user->getId()])
             ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"))
             ->one();

         return [
             'session' => $session,
             'leave' => $leave
         ];
    }

    /**
     * Start session
     * @return array|string[]
     */
    public function actionStartSession()
    {
        $model = new StaffWorkSession();
        $model->staff_id = Yii::$app->user->getId();

        if(!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }
        $model->refresh();

        if ($model) {
            return [
                'operation' => 'success',
                'message' => "Session started!",
                "time" => date('Y-m-d H:i:s'),
                "model" => StaffWorkSession::findOne(['work_session_uuid' => $model->work_session_uuid])
            ];
        }

        return [
            'operation' => 'error',
            'message' => "Error while fetching the details",
        ];
    }

    /**
     * End session
     * @return array|string[]
     */
    public function actionEndSession()
    {
        /*$minutes = StaffWorkSession::find()
            ->andWhere(['work_session_uuid' => $id])
            ->select(new Expression("TIMESTAMPDIFF(created_at, NOW())"))
            ->scalar();*/

        $model = StaffWorkSession::find()
            ->andWhere([
                'staff_id' => Yii::$app->user->getId()
            ])
            ->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."') 
                AND total_minutes IS NULL"))
            ->one();

        if(!$model) {
            return [
                'operation' => 'error',
                'message' => "Please start session"
            ];
        }

        //$model->total_minutes = (int) ((strtotime($model->created_at) - time())/ 60);

        $time = Yii::$app->db->createCommand("SELECT current_timestamp()")->queryScalar();

        $model->total_minutes = (int) ((strtotime($time) - strtotime($model->created_at))/ 60);

        if(!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => "Session ended!",
            "model" => $model
        ];
    }

    /**
     * @param $id
     * @return DailyStandupQuestion
     * @throws \yii\web\NotFoundHttpException
     */
    private function _findModel($id)
    {
        $model = DailyStandupQuestion::findOne($id);

        if (!$model)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $model;
    }
}
