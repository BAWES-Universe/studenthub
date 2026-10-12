<?php

namespace common\components\evaluation;

use yii\db\Query;

/**
 * MySQL storage for staff evaluations. Writes columns added by the form migration.
 */
class YiiEvaluationRepository implements EvaluationRepository
{
    public function getEvaluation($uuid)
    {
        $row = (new Query())
            ->from('{{%interview_evaluation}}')
            ->where(['interview_evaluation_uuid' => $uuid])
            ->one();

        return $row ? $this->evaluationFromRow($row) : null;
    }

    public function insertEvaluation(array $row)
    {
        \Yii::$app->db->createCommand()->insert('{{%interview_evaluation}}', $this->evaluationToRow($row))->execute();
    }

    public function updateEvaluation(array $row, $expectedRevision)
    {
        $columns = $this->evaluationToRow($row);
        unset($columns['interview_evaluation_uuid'], $columns['candidate_id'], $columns['staff_id'], $columns['created_by_staff_id'], $columns['created_at'], $columns['request_uuid'], $columns['company_id']);

        $updated = \Yii::$app->db->createCommand()->update('{{%interview_evaluation}}', $columns, [
            'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
            'revision' => (int) $expectedRevision,
        ])->execute();

        return $updated === 1;
    }

    public function insertChange(array $row)
    {
        \Yii::$app->db->createCommand()->insert('{{%interview_evaluation_change}}', [
            'change_uuid' => $row['change_uuid'],
            'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
            'revision' => $row['revision'],
            'action' => $row['action'],
            'staff_id' => $row['staff_id'],
            'snapshot_json' => json_encode($row['snapshot'], JSON_UNESCAPED_UNICODE),
            'created_at' => $row['created_at'],
        ])->execute();
    }

    public function changesFor($evaluationUuid)
    {
        $rows = (new Query())
            ->from('{{%interview_evaluation_change}}')
            ->where(['interview_evaluation_uuid' => $evaluationUuid])
            ->orderBy(['revision' => SORT_ASC])
            ->all();

        $changes = [];
        foreach ($rows as $row) {
            $changes[] = [
                'change_uuid' => $row['change_uuid'],
                'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
                'revision' => (int) $row['revision'],
                'action' => $row['action'],
                'staff_id' => (int) $row['staff_id'],
                'snapshot' => json_decode($row['snapshot_json'], true),
                'created_at' => $row['created_at'],
            ];
        }
        return $changes;
    }

    public function getPreset($uuid)
    {
        $row = (new Query())
            ->from('{{%interview_evaluation_preset}}')
            ->where(['preset_uuid' => $uuid])
            ->one();

        return $row ? $this->presetFromRow($row) : null;
    }

    public function insertPreset(array $row)
    {
        \Yii::$app->db->createCommand()->insert('{{%interview_evaluation_preset}}', $this->presetToRow($row))->execute();
    }

    public function updatePreset(array $row, $expectedRevision)
    {
        $columns = $this->presetToRow($row);
        unset($columns['preset_uuid'], $columns['created_by_staff_id'], $columns['created_at']);
        $updated = \Yii::$app->db->createCommand()->update('{{%interview_evaluation_preset}}', $columns, [
            'and',
            ['preset_uuid' => $row['preset_uuid']],
            ['revision' => (int) $expectedRevision],
            ['archived_at' => null],
        ])->execute();

        return $updated === 1;
    }

    public function listPresets($includeArchived)
    {
        $query = (new Query())
            ->from('{{%interview_evaluation_preset}}')
            ->orderBy(['name' => SORT_ASC]);
        if (!$includeArchived) {
            $query->andWhere(['archived_at' => null]);
        }

        $presets = [];
        foreach ($query->all() as $row) {
            $presets[] = $this->presetFromRow($row);
        }
        return $presets;
    }

    public function evaluationsForPreset($presetUuid)
    {
        $rows = (new Query())
            ->from('{{%interview_evaluation}}')
            ->where(['preset_uuid' => $presetUuid])
            ->all();
        $evaluations = [];
        foreach ($rows as $row) {
            $evaluations[] = $this->evaluationFromRow($row);
        }
        return $evaluations;
    }

    public function transaction(callable $callback)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $result = $callback();
            $transaction->commit();
            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function existingStaffIds(array $staffIds)
    {
        if ($staffIds === []) {
            return [];
        }
        $found = (new Query())
            ->select('staff_id')
            ->from('{{%staff}}')
            ->where(['staff_id' => $staffIds, 'deleted' => 0])
            ->column();

        return array_map('intval', $found);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function evaluationFromRow(array $row)
    {
        $schema = $this->decode($row['schema_json'] ?? null);
        return [
            'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
            'candidate_id' => (int) $row['candidate_id'],
            'staff_id' => $row['staff_id'] !== null ? (int) $row['staff_id'] : null,
            'request_uuid' => $row['request_uuid'],
            'company_id' => $row['company_id'] !== null ? (int) $row['company_id'] : null,
            'title' => $row['title'] ?? null,
            'evaluation_date' => $row['evaluation_date'] ?? null,
            'form_status' => $row['form_status'] ?? null,
            'preset_uuid' => $row['preset_uuid'] ?? null,
            'preset_name' => $row['preset_name'] ?? null,
            'schema' => $schema,
            'answers' => $this->decode($row['answers_json'] ?? null) ?: [],
            'revision' => (int) ($row['revision'] ?? 0),
            'created_by_staff_id' => isset($row['created_by_staff_id']) && $row['created_by_staff_id'] !== null ? (int) $row['created_by_staff_id'] : null,
            'updated_by_staff_id' => isset($row['updated_by_staff_id']) && $row['updated_by_staff_id'] !== null ? (int) $row['updated_by_staff_id'] : null,
            'completed_at' => $row['completed_at'] ?? null,
            'completed_by_staff_id' => isset($row['completed_by_staff_id']) && $row['completed_by_staff_id'] !== null ? (int) $row['completed_by_staff_id'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'legacy' => $schema === null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function evaluationToRow(array $row)
    {
        return [
            'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
            'request_uuid' => $row['request_uuid'],
            'company_id' => $row['company_id'],
            'staff_id' => $row['staff_id'],
            'candidate_id' => $row['candidate_id'],
            'title' => $row['title'],
            'evaluation_date' => $row['evaluation_date'],
            'form_status' => $row['form_status'],
            'preset_uuid' => $row['preset_uuid'],
            'preset_name' => $row['preset_name'],
            'schema_json' => json_encode($row['schema'], JSON_UNESCAPED_UNICODE),
            'answers_json' => json_encode($row['answers'], JSON_UNESCAPED_UNICODE),
            'revision' => $row['revision'],
            'created_by_staff_id' => $row['created_by_staff_id'],
            'updated_by_staff_id' => $row['updated_by_staff_id'],
            'completed_at' => $row['completed_at'],
            'completed_by_staff_id' => $row['completed_by_staff_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presetFromRow(array $row)
    {
        return [
            'preset_uuid' => $row['preset_uuid'],
            'name' => $row['name'],
            'schema' => $this->decode($row['schema_json']),
            'created_by_staff_id' => (int) $row['created_by_staff_id'],
            'updated_by_staff_id' => $row['updated_by_staff_id'] !== null ? (int) $row['updated_by_staff_id'] : null,
            'archived_at' => $row['archived_at'],
            'revision' => (int) $row['revision'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function presetToRow(array $row)
    {
        return [
            'preset_uuid' => $row['preset_uuid'],
            'name' => $row['name'],
            'schema_json' => json_encode($row['schema'], JSON_UNESCAPED_UNICODE),
            'created_by_staff_id' => $row['created_by_staff_id'],
            'updated_by_staff_id' => $row['updated_by_staff_id'],
            'archived_at' => $row['archived_at'],
            'revision' => $row['revision'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function decode($json)
    {
        if ($json === null || $json === '') {
            return null;
        }
        return json_decode($json, true);
    }
}
