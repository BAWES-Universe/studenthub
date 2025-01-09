<?php

namespace admin\modules\v1\controllers;

use admin\models\Company;
use admin\models\Staff;
use common\models\StaffWorkSession;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class StaffWorkSessionController extends Controller
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
     * Return a List of StaffWorkSession available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $query = StaffWorkSession::find();

        if($staff_id = Yii::$app->request->get('staff_id')) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

//        if($created_at = Yii::$app->request->get('date') && !Yii::$app->request->get('filterBy', null)) {
//            $query->andWhere(new Expression("DATE(created_at) =
//                DATE('".DATE('Y-m-d', strtotime($created_at))."')"));
//        }

        $startDate = Yii::$app->request->get('startDate', null);
        $endDate = Yii::$app->request->get('endDate', null);
        if ($startDate) {
            $query->andWhere(new Expression("DATE(created_at) >= DATE('".
                date('Y-m-d', strtotime ($startDate)) ."')"));
        }
        if ($endDate) {
            $query->andWhere(new Expression("DATE(created_at) <= DATE('".
                date('Y-m-d', strtotime ($endDate)) ."')"));
        }

        $query->filterByGroup();
        $query->filterByOrder();

//        return $query->getSqlQuery();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of StaffWorkSession available.
     * @return ActiveDataProvider
     */
    public function actionListInactive()
    {
        $query = Staff::find();

        if($staff_id = Yii::$app->request->get('staff_id')) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        if($created_at = Yii::$app->request->get('date')) {
            $subQuery = StaffWorkSession::find()->select('staff_id')
            ->andWhere(new Expression("DATE(created_at) = 
                DATE('".DATE('Y-m-d', strtotime($created_at))."')"));
            $query->andWhere(['not in', 'staff_id', $subQuery]);
            $query->andWhere(['staff.deleted' => '0']);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return StaffWorkSession
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }


    /**
     * Return a List of Company Accounts available.
     * @return ActiveDataProvider
     */
    public function actionDownloadListExcel()
    {
        $query = StaffWorkSession::find();

        if($staff_id = Yii::$app->request->get('staff_id')) {
            $query->andWhere(['staff_id' => $staff_id]);
        }

        $startDate = Yii::$app->request->get('startDate', null);
        $endDate = Yii::$app->request->get('endDate', null);
        if ($startDate) {
            $query->andWhere(new Expression("DATE(created_at) >= DATE('".
                date('Y-m-d', strtotime ($startDate)) ."')"));
        }
        if ($endDate) {
            $query->andWhere(new Expression("DATE(created_at) <= DATE('".
                date('Y-m-d', strtotime ($endDate)) ."')"));
        }

        $query->filterByGroup();
        $query->filterByOrder();
        header('Access-Control-Allow-Origin: *');

        \moonland\phpexcel\Excel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                'staff_id',
                [
                    'attribute'=>'Name',
                    'label'=>'staff name',
                    'value'=>function($model) {
                        return $model->staff->staff_name;
                    }
                ],[
                    'attribute'=>'Email',
                    'label'=>'Staff Email',
                    'value'=>function($model) {
                        return $model->staff->staff_email;
                    }
                ],
                [
                    'attribute'=>'total_minutes',
                    'label'=>'Total Min.',
                    'value'=>function($model) {
                        return (!$model->total_minutes) ? ($model->staff->hours_per_day*60) : $model->total_minutes;
                    }
                ],
                [
                    'attribute'=>'total_minutes',
                    'label'=>'Total Hours.',
                    'value'=>function($model) {
                        return (!round($model->total_minutes/60, 3)) ? $model->staff->hours_per_day : round($model->total_minutes/60, 3);
                    }
                ],
                [
                    'attribute'=>'created_at',
                    'label'=>'From',
                    'value'=>function($model) {
                        return $model->created_at? date('Y-m-d',strtotime($model->created_at)): null;
                    }
                ],
                [
                    'attribute'=>'updated_at',
                    'label'=>'To',
                    'value'=>function($model) {
                        return $model->updated_at? date('Y-m-d',strtotime($model->updated_at)): null;
                    }
                ],
            ]
        ]);
    }
    /**
     * Finds the DailyStandupQuestion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return StaffWorkSession the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = StaffWorkSession::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
