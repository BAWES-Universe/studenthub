<?php

namespace candidate\modules\v1\controllers;

use common\models\JobInterest;
use Yii;
use candidate\models\Job;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class JobController extends Controller
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
            'options',
            'log'
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
     * Return a List of Invitation
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $applied = Yii::$app->request->get("applied");

        $query = Job::find()
            ->joinWith(['jobSkills', "area", "area.country"])
            ->andWhere(['status' => Job::STATUS_ACTIVE])
            ->orderBy('job.created_at DESC');

        $subQuery = JobInterest::find()
            ->select('job_uuid')
            ->andWhere(['candidate_id' => Yii::$app->user->getId()]);

        if ($applied) {
            $query->andWhere(['IN', 'job.job_uuid', $subQuery]);
        } else {
            $candidate = Yii::$app->user->identity;

            $filter = 'available_from IS NULL OR DATE(available_from) >= DATE(NOW()) AND '.
                'available_to IS NULL OR DATE(available_to) <= DATE(NOW()) AND '.
                'min_age IS NULL OR min_age >= '. $candidate->getAge() . ' AND '.
                'max_age IS NULL OR max_age <= '. $candidate->getAge() . ' AND '.
                'gender IS NULL or gender =' . $candidate->candidate_gender;

            $query->andWhere(['NOT IN', 'job.job_uuid', $subQuery])
                ->andWhere( new Expression($filter));
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load Invitationn details
     * @param $id
     * @return Invitationn
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionApply($id) {
        $job = $this->findModel($id);

        //check if already applied

        $interest = JobInterest::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId(), 'job_uuid' => $id])
            ->one();

        if ($interest) {
            return [
                "operation" => 'error',
                "message" => 'Already applied!'
            ];
        }

        $seen_at = Yii::$app->request->getBodyParam("seen_at");

        $model = new JobInterest();
        $model->candidate_id = Yii::$app->user->getId();
        $model->job_uuid = $id;
        $model->notes =  Yii::$app->request->getBodyParam("notes");
        $model->seen_at = $seen_at?
            date("Y-m-d H:i:s", strtotime($seen_at)) : null;

        if (!$model->save()) {
            return [
                "operation" => 'error',
                "message" => $model->errors
            ];
        }

        return [
            "operation" => 'success',
            "message" => "Applied!"
        ];
    }

    /**
     * Finds the Job model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Job the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Job::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}