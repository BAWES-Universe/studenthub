<?php

namespace candidate\modules\v1\controllers;

use Yii;
use candidate\models\CandidateExperience;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CandidateExperienceController extends Controller
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
        $query = CandidateExperience::find()
            ->andWhere(['candidate_id' => Yii::$app->user->getId()]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }


    /**
     * @return array|string[]
     */
    public function actionSave() {

        $candidateExperiences = Yii::$app->request->getBodyParam("candidateExperiences", []);

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($candidateExperiences as $candidateExperience) {

            $model = empty($candidateExperience['candidate_experience_id']) ? new CandidateExperience():
                $this->findModel($candidateExperience['candidate_experience_id']);

            $model->candidate_id = Yii::$app->user->getId();
            $model->experience = isset($candidateExperience['experience'])?
                $candidateExperience['experience']: null;
            $model->employer = isset($candidateExperience['employer']) ?
                $candidateExperience['employer']: null;
            $model->start_year = isset($candidateExperience['start_year'])?
                $candidateExperience['start_year']: null;
            $model->end_year = isset($candidateExperience['end_year'])?
                $candidateExperience['end_year']: null;

            if (!$model->save()) {

                $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => $model->getErrors()
                ];
            }
        }

        $transaction->commit();

        $candidateExperiences = Yii::$app->user->identity->getCandidateExperiences()
            ->all();

        return [
            'operation' => 'success',
            "candidateExperiences" => $candidateExperiences
        ];
    }

    /**
     * @return array|string[]
     */
    public function actionCreate() {

        $model = new CandidateExperience();

        $model->candidate_id = Yii::$app->user->getId();
        $model->experience = Yii::$app->request->getBodyParam("experience");
        $model->employer = Yii::$app->request->getBodyParam("employer");
        $model->start_year = Yii::$app->request->getBodyParam("start_year");
        $model->end_year = Yii::$app->request->getBodyParam("end_year");

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
        $model->experience = Yii::$app->request->getBodyParam("experience");
        $model->employer = Yii::$app->request->getBodyParam("employer");
        $model->start_year = Yii::$app->request->getBodyParam("start_year");
        $model->end_year = Yii::$app->request->getBodyParam("end_year");

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
     * @return CandidateExperience
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = CandidateExperience::find()
            ->andWhere ([
                "candidate_id" => Yii::$app->user->getId(),
                'candidate_experience_id' => $id
            ])
            ->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}