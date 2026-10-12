<?php

namespace staff\modules\v1\controllers;

use common\components\evaluation\EvaluationWorkflow;
use common\components\evaluation\YiiEvaluationRepository;
use staff\components\EvaluationActor;
use staff\components\EvaluationResponse;
use Yii;
use yii\web\NotFoundHttpException;

class InterviewEvaluationPresetController extends StaffRestController
{
    use EvaluationActor;

    /**
     * @return array<string, mixed>
     */
    public function actionList()
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        return EvaluationResponse::send($this->workflow()->listPresets($actor), $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function actionCreate()
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $result = $this->workflow()->createPreset(
            $actor,
            Yii::$app->request->getBodyParam('name'),
            Yii::$app->request->getBodyParam('schema', [])
        );
        return $this->presetResponse($result, $actor);
    }

    /**
     * @param string $id evaluation uuid
     * @return array<string, mixed>
     * @throws NotFoundHttpException
     */
    public function actionFromEvaluation($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $existing = $this->workflow()->repository()->getEvaluation($id);
        if (!$existing) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $this->evaluationCandidate($existing['candidate_id']);
        $result = $this->workflow()->savePresetFromEvaluation(
            $actor,
            $id,
            Yii::$app->request->getBodyParam('name')
        );
        return $this->presetResponse($result, $actor);
    }

    /**
     * @param string $id
     * @return array<string, mixed>
     */
    public function actionUpdate($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $result = $this->workflow()->updatePreset(
            $actor,
            $id,
            Yii::$app->request->getBodyParam('name'),
            Yii::$app->request->getBodyParam('schema', []),
            Yii::$app->request->getBodyParam('expected_revision')
        );
        return $this->presetResponse($result, $actor);
    }

    /**
     * @param string $id
     * @return array<string, mixed>
     */
    public function actionDuplicate($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $result = $this->workflow()->duplicatePreset($actor, $id);
        return $this->presetResponse($result, $actor);
    }

    /**
     * @param string $id
     * @return array<string, mixed>
     */
    public function actionArchive($id)
    {
        $actor = $this->evaluationActor();
        $this->requireFormStorage();
        $result = $this->workflow()->archivePreset(
            $actor,
            $id,
            Yii::$app->request->getBodyParam('expected_revision')
        );
        return $this->presetResponse($result, $actor);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    private function presetResponse(array $result, array $actor)
    {
        if (!empty($result['ok']) && isset($result['preset'])) {
            $result['preset'] = $this->workflow()->presentPreset($result['preset'], $actor);
            $result['message'] = 'Preset saved.';
        }
        if (!empty($result['conflict']) && isset($result['server']) && is_array($result['server'])) {
            $result['server'] = $this->workflow()->presentPreset($result['server'], $actor);
        }
        return EvaluationResponse::send($result, $actor);
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
