<?php

namespace admin\modules\v1\controllers;

use common\models\DailyStandupAnswer;
use common\models\StaffLeave;
use common\models\StaffWorkSession;
use Yii;
use common\models\DailyStandupQuestion;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class DailyStandupQuestionController extends Controller
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
     * Return a List of DailyStandupQuestion available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = DailyStandupQuestion::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of DailyStandupAnswers available.
     * @return ActiveDataProvider
     */
    public function actionListAnswers()
    {
        $staff_id = Yii::$app->request->get('staff_id');
        $created_at = Yii::$app->request->get('created_at');

        $query = DailyStandupAnswer::find();

        if($staff_id) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        if($created_at) {
            $query->andWhere(new Expression("DATE(created_at) = 
                DATE('".DATE('Y-m-d', strtotime($created_at))."')"));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListWorkSession()
    {
        $groupBy = Yii::$app->request->get('groupBy');
        $staff_id = Yii::$app->request->get('staff_id');
        $created_at = Yii::$app->request->get('created_at');

        $query = StaffWorkSession::find();

        if($staff_id) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        if ($groupBy) {
            if ($groupBy == 'staff') {
                $query->addGroupBy('staff_id');
            }
            if ($groupBy == 'date') {
                $query->addGroupBy('created_at');
            }
        }

        if($created_at) {
            $query->andWhere(new Expression("DATE(created_at) = 
                DATE('".DATE('Y-m-d', strtotime($created_at))."')"));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }


    /**
     * list staff on leave
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionAbsences()
    {
        return StaffLeave::find()
           // ->andWhere(['staff_id' => $id])
            ->andWhere(new Expression("DATE(from_date) <= DATE('".date('Y-m-d')."') AND 
                DATE(to_date) >= DATE('".date('Y-m-d')."')"))
            ->all();
    }

    /**
     * load DailyStandupQuestion details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Create a bank account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new DailyStandupQuestion
        $model = new DailyStandupQuestion();

        $model->question = Yii::$app->request->getBodyParam("question");

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
                    "message" => "We've faced a problem adding question, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Daily Standup Question Added: '.$model->question.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Daily Standup Question Added successfully"
        ];
    }

    /**
     * Update question
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->question = Yii::$app->request->getBodyParam("question");

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
                    "message" => "We've faced a problem updating the question, please contact us for assistance."
                ];
            }
        }

        Yii::info('[Daily Standup Question Updated: '.$model->question.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);


        return [
            "operation" => "success",
            "message" => "Daily standup question updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Delete bank
        $model->delete();

        Yii::info('[Daily Standup Question Deleted: '.$model->question.'] By '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Daily standup question deleted successfully"
        ];
    }

    /**
     * Finds the DailyStandupQuestion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DailyStandupQuestion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DailyStandupQuestion::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
