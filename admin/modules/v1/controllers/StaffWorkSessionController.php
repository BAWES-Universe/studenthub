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

        $query->orderBy('created_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load StaffWorkSession details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
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
