<?php

namespace staff\modules\v1\controllers;

use staff\models\Staff;
use Yii;
use common\models\DailyStandupAnswer;
use common\models\DailyStandupQuestion;
use common\models\StaffLeave;
use common\models\StaffWorkSession;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\rest\Controller;


class StaffLeaveController extends Controller
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
     * request for leave
     * @return array|string[]
     */
    public function actionCreate()
    {
        $model = new StaffLeave();
        $model->staff_id = Yii::$app->user->getId();
        $model->from_date = Yii::$app->request->getBodyParam('from_date');
        $model->to_date = Yii::$app->request->getBodyParam('to_date');
        $model->note = Yii::$app->request->getBodyParam('note');
        $model->category = Yii::$app->request->getBodyParam('type');
        $model->file = Yii::$app->request->getBodyParam("file");
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
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = StaffLeave::find();
        $query->andWhere(['staff_id'=>Yii::$app->user->getId()]);
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionDelete($id)
    {
        if (StaffLeave::deleteAll(['staff_leave_uuid'=>$id])) {
            return [
                'operation' => 'success',
                'message' => "Request deleted successfully!"
            ];
        }

        return [
            'operation' => 'error',
            'message' => 'error while deleting request'
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
