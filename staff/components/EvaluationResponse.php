<?php

namespace staff\components;

use staff\models\InterviewEvaluation;
use Yii;

class EvaluationResponse
{
    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public static function send(array $result, array $actor = [])
    {
        if (!empty($result['forbidden'])) {
            Yii::$app->response->statusCode = 403;
            return [
                'operation' => 'error',
                'message' => $result['errors'][0]['message'],
            ];
        }
        if (!empty($result['conflict'])) {
            Yii::$app->response->statusCode = 409;
            return [
                'operation' => 'conflict',
                'message' => $result['errors'][0]['message'],
                'server' => isset($result['server']) ? self::conflictCopy($result['server']) : null,
                'unsaved' => isset($result['unsaved']) ? $result['unsaved'] : null,
            ];
        }
        if (empty($result['ok'])) {
            return [
                'operation' => 'error',
                'message' => isset($result['errors']) ? $result['errors'] : 'The evaluation could not be saved.',
            ];
        }

        $payload = [
            'operation' => 'success',
            'message' => isset($result['message']) ? $result['message'] : 'Saved.',
        ];
        if (isset($result['data'])) {
            $payload['data'] = self::evaluation($result['data']);
        }
        if (isset($result['preset'])) {
            $payload['preset'] = $result['preset'];
        }
        if (isset($result['presets'])) {
            $payload['presets'] = $result['presets'];
        }
        if (isset($result['changes'])) {
            $payload['changes'] = self::changes($result['changes']);
        }
        return $payload;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    /**
     * Preset conflicts keep the preset fields the editor reloads.
     * Evaluation formatting would add answer and legacy fields the preset does not have.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function conflictCopy(array $row)
    {
        if (isset($row['preset_uuid']) && !isset($row['interview_evaluation_uuid'])) {
            return $row;
        }
        return self::evaluation($row);
    }

    public static function evaluation(array $row)
    {
        $row['is_legacy'] = !empty($row['legacy']);
        if (!isset($row['answers']) || $row['answers'] === [] || $row['answers'] === null) {
            $row['answers'] = new \stdClass();
        }
        $row['created_by'] = InterviewEvaluation::staffBrief(isset($row['created_by_staff_id']) ? $row['created_by_staff_id'] : null);
        $row['updated_by'] = InterviewEvaluation::staffBrief(isset($row['updated_by_staff_id']) ? $row['updated_by_staff_id'] : null);
        unset($row['legacy']);
        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     * @return array<int, array<string, mixed>>
     */
    private static function changes(array $changes)
    {
        foreach ($changes as $index => $change) {
            $changes[$index]['staff'] = InterviewEvaluation::staffBrief($change['staff_id']);
        }
        return $changes;
    }
}
