<?php

namespace staff\modules\v1\controllers;

use common\models\InterviewEvaluationNote;
use common\models\InterviewEvaluationNoteVersion;
use Yii;
use common\models\Note;
use staff\components\EvaluationActor;
use staff\models\Fulltimer;
use staff\models\InterviewEvaluation;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class InterviewEvaluationController extends Controller
{
    use EvaluationActor;

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
     * Return a List of Fulltimer Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $this->evaluationActor();
        $candidate_id = Yii::$app->request->get('candidate_id', null);
        $filtersByCandidate = $candidate_id !== null && $candidate_id !== '';
        if ($filtersByCandidate) {
            $this->evaluationCandidate($candidate_id);
        }

        $query = InterviewEvaluation::find()
            ->innerJoin(
                '{{%candidate}}',
                '{{%candidate}}.candidate_id = {{%interview_evaluation}}.candidate_id'
            )
            ->andWhere(['{{%candidate}}.deleted' => 0])
            ->orderBy(['{{%interview_evaluation}}.created_at' => SORT_DESC]);

        if ($filtersByCandidate) {
            $query->andWhere(['{{%interview_evaluation}}.candidate_id' => (int) $candidate_id]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load fulltimer details
     * @param $id
     * @return Fulltimer
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->evaluationCandidate($model->candidate_id);
        return $model;
    }

    /**
     * return interview note versions
     * @param $id
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionVersions($id)
    {
        $model = $this->findModel($id);
        $this->evaluationCandidate($model->candidate_id);

        $query = $model->getInterviewEvaluationNoteVersions()
            ->orderBy("created_at DESC");

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionAddNewVersion($id) {
        $interview = $this->findModel($id);
        $this->evaluationCandidate($interview->candidate_id);
        if ($blocked = $this->rejectStructuredWrite($interview)) {
            return $blocked;
        }

        $notes = Yii::$app->request->getBodyParam("interviewEvaluationNotes");

        if(!$notes || sizeof($notes) == 0) {
            return [
                "operation" => "error",
                "message" => "Notes required!"
            ];
        }

        $latestVersion = $interview->getLatestInterviewEvaluationNoteVersions()
            ->one();

        $model = new InterviewEvaluationNoteVersion();
        $model->interview_evaluation_uuid = $interview->interview_evaluation_uuid;
        $model->version = $latestVersion? $latestVersion->version + 1: 1;
        $model->staff_id = Yii::$app->user->getId();

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        //add version notes

        foreach ($notes as $note) {

            if(empty($note['note'])) {
                continue;
            }

            $interviewEvaluationNote = new InterviewEvaluationNote();
            $interviewEvaluationNote->ienv_uuid = $model->ienv_uuid;
            $interviewEvaluationNote->note = $note['note'];

            if(!$interviewEvaluationNote->save()) {
                return [
                    "operation" => "error",
                    "message" => $interviewEvaluationNote->errors
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Interview evaluation version created successfully",
            "data" => $model
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAddNote($id) {
        $interview = $this->findModel($id);
        $this->evaluationCandidate($interview->candidate_id);
        if ($blocked = $this->rejectStructuredWrite($interview)) {
            return $blocked;
        }

        $model = new Note();
        $model->interview_evaluation_uuid = $interview->interview_evaluation_uuid;
        $model->request_uuid = $interview->request_uuid;
        $model->company_id = $interview->company_id;
        $model->note_text = Yii::$app->request->getBodyParam('note_text');
        $model->note_type = "Interview Evaluation";

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Note added",
            "note" => Note::findOne($model->note_uuid)
        ];
    }

    /**
     * @return array|string[]
     */
    public function actionCreate()
    {
        $model = new InterviewEvaluation();

        $model->request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->company_id = $model->request? $model->request->company_id: null;//Yii::$app->request->getBodyParam("company_id");
        $model->staff_id = Yii::$app->user->getId();
        if ($model->candidate_id) {
            $this->evaluationCandidate($model->candidate_id);
        }

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors,
                    "code" => 1
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem adding, please contact us for assistance.",
                    "code" => 2
                ];
            }
        }

        $interviewEvaluationNotes = Yii::$app->request->getBodyParam("interviewEvaluationNotes", []);

        //add new version

        $interviewEvaluationNoteVersion = new InterviewEvaluationNoteVersion;
        $interviewEvaluationNoteVersion->interview_evaluation_uuid = $model->interview_evaluation_uuid;
        $interviewEvaluationNoteVersion->staff_id = Yii::$app->user->getId();

        if(!$interviewEvaluationNoteVersion->save()) {
            return [
                "operation" => "error",
                "code" => 3,
                "message" => $interviewEvaluationNoteVersion->errors
            ];
        }

        //add notes

        foreach ($interviewEvaluationNotes as $interviewEvaluationNote) {

            if(empty($interviewEvaluationNote['note'])) {
                continue;
            }

            $noteModal = new InterviewEvaluationNote();
            $noteModal->ienv_uuid = $interviewEvaluationNoteVersion->ienv_uuid;
            $noteModal->note = $interviewEvaluationNote['note'];
            if(!$noteModal->save()) {
                return [
                    "operation" => "error",
                    "message" => $noteModal->errors,
                    "code" => 4
                ];
            }
        }

        /*$notes = Yii::$app->request->getBodyParam("notes", []);

        foreach ($notes as $note) {
            $noteModal = new Note();
            $noteModal->interview_evaluation_uuid = $model->interview_evaluation_uuid;
            $noteModal->request_uuid = $model->request_uuid;
            $noteModal->company_id = $model->company_id;
            $noteModal->note_text = $note['note_text'];
            $noteModal->note_type = "Interview Evaluation";
            if(!$noteModal->save()) {
                return [
                    "operation" => "error",
                    "message" => $noteModal->errors,
                    "code" => 3
                ];
            }
        }*/

        return [
            "operation" => "success",
            "message" => "Interview evaluation added created successfully",
            "data" => $model
        ];
    }

    /**
     * @param $id
     * @return void
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->evaluationCandidate($model->candidate_id);
        if ($blocked = $this->rejectStructuredWrite($model)) {
            return $blocked;
        }

        //$model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->request_uuid = Yii::$app->request->getBodyParam("request_uuid");
        $model->company_id = $model->request? $model->request->company_id: null;// Yii::$app->request->getBodyParam("company_id");
        $model->staff_id = Yii::$app->user->getId();

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating, please contact us for assistance."
                ];
            }
        }

        $interviewEvaluationNotes = Yii::$app->request->getBodyParam("interviewEvaluationNotes", []);

        //add new version

        $latestVersion = $model->getLatestInterviewEvaluationNoteVersions()
            ->one();

        $interviewEvaluationNoteVersion = new InterviewEvaluationNoteVersion;
        $interviewEvaluationNoteVersion->version = $latestVersion? $latestVersion->version + 1: 1;
        $interviewEvaluationNoteVersion->interview_evaluation_uuid = $model->interview_evaluation_uuid;
        $interviewEvaluationNoteVersion->staff_id = Yii::$app->user->getId();

        if(!$interviewEvaluationNoteVersion->save()) {
            return [
                "operation" => "error",
                "code" => 3,
                "message" => $interviewEvaluationNoteVersion->errors
            ];
        }

        //add notes

        foreach ($interviewEvaluationNotes as $interviewEvaluationNote) {

            if(empty($interviewEvaluationNote['note'])) {
                continue;
            }

            $noteModal = new InterviewEvaluationNote();
            $noteModal->ienv_uuid = $interviewEvaluationNoteVersion->ienv_uuid;
            $noteModal->note = $interviewEvaluationNote['note'];
            if(!$noteModal->save()) {
                return [
                    "operation" => "error",
                    "message" => $noteModal->errors,
                    "code" => 4
                ];
            }
        }

        return [
            "operation" => "success",
            "message" => "Interview evaluation added successfully",
            "data" => $model
        ];
    }

    /**
     * @param $id
     * @return string[]
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->evaluationCandidate($model->candidate_id);
        if ($blocked = $this->rejectStructuredWrite($model)) {
            return $blocked;
        }

        $model->delete();

        return [
            "operation" => "success",
            "message" => "Interview evaluation deleted successfully"
        ];
    }

    /**
     * @param $id
     * @return InterviewEvaluation|null
     * @throws NotFoundHttpException
     */
    /**
     * Structured rows keep their schema, answers, revision, and history.
     * Legacy note writes stay available when no schema has been saved.
     *
     * @return array|null
     */
    protected function rejectStructuredWrite(InterviewEvaluation $model)
    {
        if (!$model->hasAttribute('schema_json') || $model->schema_json === null || $model->schema_json === '') {
            return null;
        }

        Yii::$app->response->statusCode = 422;
        return [
            'operation' => 'error',
            'message' => 'This evaluation uses the structured form. Save it from the evaluation editor so revision and history stay intact.',
        ];
    }

    protected function findModel($id)
    {
        if (($model = InterviewEvaluation::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

