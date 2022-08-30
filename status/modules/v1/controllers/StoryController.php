<?php

namespace status\modules\v1\controllers;

use Yii;
use common\models\StoryActivity;
use common\models\Story;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Request;
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
     * Return a List of stories.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $status = Yii::$app->request->get('story_status');
        $position_type = Yii::$app->request->get('position_type');
        $keyword = Yii::$app->request->get("name");
        $staff_id = Yii::$app->request->get("staff_id");
        $company_id = Yii::$app->request->get("company_id");

        $query = Story::find()
            ->joinWith('request');

        if ($status) {
            $status = ($status == '9' ? 0 : $status);
            $query->andWhere(['story_status' => $status]);
        }

        if ($position_type) {
            $query->andWhere(['request.request_position_type' => $position_type]);
        }

        if ($company_id) {
            $query->andWhere(['request.company_id' => $company_id]);
        }

        if ($staff_id) {
            $query->andWhere(['story.staff_id' => $staff_id]);
        }

        if ($keyword) {
            $query->andWhere([
                'OR',
                ['like', 'request.request_position_title', $keyword]
            ]);
        }

        $query->orderBy('story_created_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
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
