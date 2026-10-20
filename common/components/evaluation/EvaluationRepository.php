<?php

namespace common\components\evaluation;

interface EvaluationRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function getEvaluation($uuid);

    /**
     * @param array<string, mixed> $row
     */
    public function insertEvaluation(array $row);

    /**
     * @param array<string, mixed> $row
     */
    public function updateEvaluation(array $row, $expectedRevision);

    /**
     * @param array<string, mixed> $row
     */
    public function insertChange(array $row);

    /**
     * @return array<int, array<string, mixed>>
     */
    public function changesFor($evaluationUuid);

    /**
     * @return array<string, mixed>|null
     */
    public function getPreset($uuid);

    /**
     * @param array<string, mixed> $row
     */
    public function insertPreset(array $row);

    /**
     * @param array<string, mixed> $row
     */
    public function updatePreset(array $row, $expectedRevision);

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPresets($includeArchived);

    /**
     * @return array<int, array<string, mixed>>
     */
    public function evaluationsForPreset($presetUuid);

    /**
     * @param array<int, int> $staffIds
     * @return array<int, int>
     */
    public function existingStaffIds(array $staffIds);

    /**
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback);
}
