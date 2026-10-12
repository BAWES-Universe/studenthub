<?php

namespace common\components\evaluation;

/**
 * Disposable store for evaluation workflow tests. Not used in production.
 */
class MemoryEvaluationRepository implements EvaluationRepository
{
    /** @var array<string, array<string, mixed>> */
    public $evaluations = [];

    /** @var array<string, array<string, mixed>> */
    public $presets = [];

    /** @var array<int, array<string, mixed>> */
    public $changes = [];

    /** @var array<int, int> */
    public $staffIds = [];

    public $failNextUpdate = false;

    public $throwNextUpdate = false;

    public function getEvaluation($uuid)
    {
        return isset($this->evaluations[$uuid]) ? $this->evaluations[$uuid] : null;
    }

    public function insertEvaluation(array $row)
    {
        $this->evaluations[$row['interview_evaluation_uuid']] = $row;
    }

    public function updateEvaluation(array $row, $expectedRevision)
    {
        if ($this->throwNextUpdate) {
            $this->throwNextUpdate = false;
            throw new \RuntimeException('Save failed.');
        }
        if ($this->failNextUpdate) {
            $this->failNextUpdate = false;
            return false;
        }
        $uuid = $row['interview_evaluation_uuid'];
        if (!isset($this->evaluations[$uuid]) || (int) $this->evaluations[$uuid]['revision'] !== (int) $expectedRevision) {
            return false;
        }
        $this->evaluations[$uuid] = $row;
        return true;
    }

    public function insertChange(array $row)
    {
        $this->changes[] = $row;
    }

    public function changesFor($evaluationUuid)
    {
        $rows = [];
        foreach ($this->changes as $change) {
            if ($change['interview_evaluation_uuid'] === $evaluationUuid) {
                $rows[] = $change;
            }
        }
        return $rows;
    }

    public function getPreset($uuid)
    {
        return isset($this->presets[$uuid]) ? $this->presets[$uuid] : null;
    }

    public function insertPreset(array $row)
    {
        $this->presets[$row['preset_uuid']] = $row;
    }

    public function updatePreset(array $row, $expectedRevision)
    {
        $uuid = $row['preset_uuid'];
        if (!isset($this->presets[$uuid])) {
            return false;
        }
        $current = $this->presets[$uuid];
        if ((int) $current['revision'] !== (int) $expectedRevision || !empty($current['archived_at'])) {
            return false;
        }
        $this->presets[$uuid] = $row;
        return true;
    }

    public function listPresets($includeArchived)
    {
        $rows = [];
        foreach ($this->presets as $preset) {
            if ($includeArchived || empty($preset['archived_at'])) {
                $rows[] = $preset;
            }
        }
        return $rows;
    }

    public function evaluationsForPreset($presetUuid)
    {
        $rows = [];
        foreach ($this->evaluations as $evaluation) {
            if (($evaluation['preset_uuid'] ?? null) === $presetUuid) {
                $rows[] = $evaluation;
            }
        }
        return $rows;
    }

    public function transaction(callable $callback)
    {
        return $callback();
    }

    public function existingStaffIds(array $staffIds)
    {
        $found = [];
        foreach ($staffIds as $staffId) {
            if (in_array((int) $staffId, $this->staffIds, true)) {
                $found[] = (int) $staffId;
            }
        }
        return $found;
    }
}
