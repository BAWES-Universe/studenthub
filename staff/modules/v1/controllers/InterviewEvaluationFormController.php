<?php

namespace staff\modules\v1\controllers;

use common\components\evaluation\EvaluationWorkflow;
use common\components\evaluation\YiiEvaluationRepository;
use staff\components\EvaluationActor;
use staff\components\EvaluationResponse;
use Yii;
use yii\web\NotFoundHttpException;

class InterviewEvaluationFormController extends StaffRestController
{
    use EvaluationActor;

    /**
     * @return array<string, mixed>
     */
    public function actionSaveForm()
    {
        $actor = $this->evaluationActor();
        $candidateId = Yii::$app->request->getBodyParam('candidate_id');
        $this->evaluationCandidate($candidateId);
        $this->requireFormStorage();

        $result = $this->workflow()->saveNew($actor, $candidateId, Yii::$app->request->getBodyParams());
        if (!empty($result['ok'])) {
            Yii::info(
                '[Evaluation ' . $result['data']['form_status'] . ' ' . $result['data']['interview_evaluation_uuid'] . '] by ' . $actor['staff_name'] . ' for candidate ' . (int) $candidateId,
                __METHOD__
            );
        }
        return EvaluationResponse::send($result, $actor);
    }

    /**
     * @param string $id
     * @return array<string, mixed>
     * @throws NotFoundHttpException
     */
    public function actionUpdateForm($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $existing = $this->workflow()->repository()->getEvaluation($id);
        if (!$existing) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $this->evaluationCandidate($existing['candidate_id']);

        $result = $this->workflow()->saveExisting($actor, $id, Yii::$app->request->getBodyParams());
        if (!empty($result['ok'])) {
            Yii::info(
                '[Evaluation ' . $result['data']['form_status'] . ' revision ' . $result['data']['revision'] . ' ' . $id . '] by ' . $actor['staff_name'] . ' for candidate ' . $existing['candidate_id'],
                __METHOD__
            );
        }
        return EvaluationResponse::send($result, $actor);
    }

    /**
     * @param string $id
     * @return array<string, mixed>
     * @throws NotFoundHttpException
     */
    public function actionHistory($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $existing = $this->workflow()->repository()->getEvaluation($id);
        if (!$existing) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $this->evaluationCandidate($existing['candidate_id']);
        return EvaluationResponse::send($this->workflow()->history($actor, $id), $actor);
    }

    protected function workflow()
    {
        return new EvaluationWorkflow(new YiiEvaluationRepository());
    }

    protected function requireFormStorage()
    {
        if (Yii::$app->db->getTableSchema('interview_evaluation_preset', true) === null) {
            throw new \yii\web\HttpException(503, 'Evaluation forms are not available until the database migration has run.');
        }
    }
}
