<?php

namespace staff\components;

use common\components\evaluation\EvaluationAccess;
use common\models\Candidate;
use common\models\Staff;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Staff identity and candidate visibility for evaluation actions.
 */
trait EvaluationActor
{
    /**
     * @return array<string, mixed>
     * @throws ForbiddenHttpException
     */
    protected function evaluationActor()
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof Staff) {
            throw new ForbiddenHttpException('Staff access is required.');
        }

        return [
            'is_staff' => true,
            'staff_id' => (int) $identity->staff_id,
            'staff_role' => (int) $identity->staff_role,
            'staff_name' => $identity->staff_name,
        ];
    }

    /**
     * Existing candidate access is an authenticated Staff user and a candidate
     * record that has not been deleted. There is no finer per-candidate grant.
     *
     * @param mixed $candidateId
     * @return array<string, mixed>
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    protected function evaluationCandidate($candidateId)
    {
        $this->evaluationActor();
        $candidate = Candidate::find()
            ->select(['candidate_id', 'deleted'])
            ->where(['candidate_id' => (int) $candidateId])
            ->asArray()
            ->one();

        if (!EvaluationAccess::canAccessCandidate(true, $candidate ?: [])) {
            throw new NotFoundHttpException('Candidate not found.');
        }

        return $candidate;
    }
}
