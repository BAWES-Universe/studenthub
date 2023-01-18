<?php

namespace admin\modules\v1\controllers;

use common\models\CandidateEvalDeptQues;
use common\models\CandidateEvaluation;
use Yii;
use yii\db\BaseActiveRecord;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;
use common\models\CandidateEvalQues;

/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateEvaluationController extends Controller
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
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionListQuestion()
    {
        $query = CandidateEvalDeptQues::find();
        $query->groupBy('dept_id');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionViewQuestion($id)
    {
        return CandidateEvalQues::findOne($id);
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionListCandidateReport()
    {
        $query = CandidateEvaluation::find();

        if ($staffID = Yii::$app->request->get('staffID',null))
            $query->andWhere(['staff_id'=>$staffID]);

        if ($candidateID = Yii::$app->request->get('candidateID',null))
            $query->andWhere(['candidate_id'=>$candidateID]);

        if ($departmentID = Yii::$app->request->get('departmentID',null))
            $query->andWhere(['dept_id'=>$departmentID]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return array|string[]
     */
    public function actionCreateQuestion()
    {
        $model = new CandidateEvalQues();
        $model->question = Yii::$app->request->post('question');
        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }
        $last = CandidateEvalQues::find()->orderBy('created_at DESC')->one(); // last record not fetching
        CandidateEvalDeptQues::deleteAll(['ceq_uuid'=>$model->ceq_uuid]);
        $departmentIDs = Yii::$app->request->post('deptIDs');
        foreach ($departmentIDs as $DP_ID) {
            $evelModel = new CandidateEvalDeptQues();
            $evelModel->ceq_uuid = $last->ceq_uuid;
            $evelModel->dept_id = $DP_ID;
            $evelModel->save(false);
        }

        return [
            'operation' => 'success',
            'message' => 'Candidate Evaluation question created success'
        ];
    }

    /**
     * @param $id
     * @return CandidateEvaluation|null
     */
    public function actionViewReport($id)
    {
        return CandidateEvaluation::findOne($id);
    }

    /**
     * @return array|string[]
     */
    public function actionUpdateQuestion($id)
    {
        $model = $this->findModel($id);

        $model->question = Yii::$app->request->post('question');

        CandidateEvalDeptQues::deleteAll(['ceq_uuid'=>$id]);

        $departmentIDs = Yii::$app->request->post('deptIDs');
        foreach ($departmentIDs as $DP_ID) {
            $evelModel = new CandidateEvalDeptQues();
            $evelModel->ceq_uuid = $id;
            $evelModel->dept_id = $DP_ID;
            $evelModel->save(false);
        }

        if (!$model->save()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Candidate Evaluation question updated successfully'
        ];
    }

    /**
     * @param $id
     * @throws NotFoundHttpException
     */
    public function actionAssignQuestion($id)
    {
        $error = false;
        $this->findModel($id); // to check if exist

        CandidateEvalDeptQues::deleteAll(['ceq_uuid'=>$id]);
        $departmentIDs = Yii::$app->request->getBodyParam('deptId');
        foreach ($departmentIDs as $DP_ID) {
            $model = new CandidateEvalDeptQues();
            $model->ceq_uuid = $id;
            $model->dept_id = $DP_ID;
            if (!$model->save(false))
                $error = true;
        }

        if ($error) {
            return [
                'operation' => 'error',
                'message' => 'Error while saving the data'
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Candidate Evaluation question assigned successfully'
        ];
    }

    /**
     * @return ActiveDataProvider
     */
    public function actionListAssignedQuestion()
    {
        $query = CandidateEvalDeptQues::find();

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return array|string[]
     */
    public function actionDelete($id)
    {
        CandidateEvalDeptQues::deleteAll(['ceq_uuid'=>$id]);
        $model = $this->findModel($id);

        if (!$model->delete()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'message' => 'Candidate Evaluation question deleted successfully'
        ];
    }

    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CandidateEvalQues the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CandidateEvalQues::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
