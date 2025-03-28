<?php

namespace candidate\modules\v1\controllers;

use common\models\Degree;
use common\models\DegreeGroup;
use common\models\Major;
use Yii;
use common\models\CandidateEducation;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CandidateEducationController extends Controller
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
     * @return ActiveDataProvider
     */
    public function actionList() {
        $query = CandidateEducation::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListMajor() {

        $page = Yii::$app->request->get("page");
        $q = Yii::$app->request->get("q");
        $limit = Yii::$app->request->get("limit");

        $query = Major::find();

        if ($q) {
            $query->andWhere([
                "OR",
                ["like", "major_name_en", $q],
                ["like", "major_name_ar", $q],
            ]);
        }

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $limit > 0 ? $limit: 20,
            ],
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListDegreeGroup() {
        $query = DegreeGroup::find();
        $page = Yii::$app->request->get("page");
        $limit = Yii::$app->request->get("limit");

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $limit > 0 ? $limit: 20,
            ],
        ]);
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListDegree() {
        $page = Yii::$app->request->get("page");
        $q = Yii::$app->request->get("q");
        $limit = Yii::$app->request->get("limit");
        
        $query = Degree::find();
        
        if ($q) {
            $query->andWhere([
                "OR",
                ["like", "degree_name_en", $q],
                ["like", "degree_name_ar", $q],
            ]);
        }

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $limit > 0 ? $limit: 20,
            ],
        ]);
    }

    /**
     * @return array|string[]
     */
    public function actionSave() {

        $candidateEducations = Yii::$app->request->getBodyParam("candidateEducations", []);

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($candidateEducations as $candidateEducation) {

            $model = empty($candidateEducation['education_uuid']) ? new CandidateEducation():
                $this->findModel($candidateEducation['education_uuid']);

            $model->candidate_id = Yii::$app->user->getId();
            $model->university_id = $candidateEducation['university_id'];
            $model->degree_uuid = !empty($candidateEducation['degree_uuid'])? $candidateEducation['degree_uuid']: null;
            $model->major_uuid = !empty($candidateEducation['major_uuid'])? $candidateEducation['major_uuid']: null;
            $model->graduation_year = !empty($candidateEducation['graduation_year'])? $candidateEducation['graduation_year']: null;
            $model->is_currently_studying = (int)$candidateEducation['is_currently_studying'];

            if (isset($candidateEducation['graduation_year'])) {
                if (
                    $candidateEducation['graduation_year'] &&
                    (int)$candidateEducation['graduation_year'] > (int)date('Y')
                ) {
                    $model->is_currently_studying = 1;
                }

                if (
                    $candidateEducation['graduation_year'] &&
                    (int)$candidateEducation['graduation_year'] < (int)date('Y')
                ) {
                    $model->is_currently_studying = 0;
                }
            }

            if (!$model->save()) {

                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => $model->getErrors()
                ];
            }
        }

        $transaction->commit();

        $candidateEducations = Yii::$app->user->identity->getCandidateEducations()
            ->with(['major', 'degree', 'university'])
            ->asArray()
            ->all();

        return [
            'operation' => 'success',
            "candidateEducations" => $candidateEducations
        ];
    }

    /**
     * @return array|string[]
     */
    public function actionCreate() {

        $model = new CandidateEducation();

        $model->candidate_id = Yii::$app->user->getId();
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->degree_uuid = Yii::$app->request->getBodyParam("degree_uuid");
        $model->major_uuid = Yii::$app->request->getBodyParam("major_uuid");
        $model->graduation_year = Yii::$app->request->getBodyParam("graduation_year");
        $model->is_currently_studying = (int) Yii::$app->request->getBodyParam("is_currently_studying");

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id) {

        $model = $this->findModel($id);

        //$model->candidate_id = Yii::$app->user->getId();
        $model->university_id = Yii::$app->request->getBodyParam("university_id");
        $model->degree_uuid = Yii::$app->request->getBodyParam("degree_uuid");
        $model->major_uuid = Yii::$app->request->getBodyParam("major_uuid");
        $model->graduation_year = Yii::$app->request->getBodyParam("graduation_year");
        $model->is_currently_studying = (int)Yii::$app->request->getBodyParam("is_currently_studying");

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id) {

        $model = $this->findModel($id);

        if (!$model->delete()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
        ];
    }

    /**
     * @param $id
     * @return void
     */
    public function actionView($id) {
        return $this->findModel($id);
    }

    /**
     * @param $id
     * @return CandidateEducation
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = CandidateEducation::find()
            ->andWhere ([
                "candidate_id" => Yii::$app->user->getId(),
                'education_uuid' => $id
            ])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}