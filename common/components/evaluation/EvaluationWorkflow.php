<?php

namespace common\components\evaluation;

/**
 * Saves evaluations and shared presets.
 * An evaluation keeps its own schema snapshot, so later preset edits do not change it.
 */
class EvaluationWorkflow
{
    /** @var EvaluationRepository */
    private $repository;

    public function repository()
    {
        return $this->repository;
    }

    /** @var callable */
    private $clock;

    /** @var callable */
    private $ids;

    public function __construct(EvaluationRepository $repository, $clock = null, $ids = null)
    {
        $this->repository = $repository;
        $this->clock = $clock ?: function () {
            return date('Y-m-d H:i:s');
        };
        $this->ids = $ids ?: function ($prefix) {
            return $prefix . bin2hex(random_bytes(16));
        };
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function saveNew(array $actor, $candidateId, array $input)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }

        $preset = $this->presetForNewEvaluation(isset($input['preset_uuid']) ? $input['preset_uuid'] : null);
        if ($preset !== null && empty($preset['ok'])) {
            return $preset;
        }

        $prepared = EvaluationForm::prepareSave(
            ['exists' => false],
            $input,
            $this->knownStaffIds($input)
        );
        if (empty($prepared['ok'])) {
            return $this->withUnsaved($prepared, $input);
        }

        $now = call_user_func($this->clock);
        $state = $prepared['state'];
        $row = [
            'interview_evaluation_uuid' => call_user_func($this->ids, 'interview_evaluation_'),
            'candidate_id' => (int) $candidateId,
            'staff_id' => (int) $actor['staff_id'],
            'request_uuid' => null,
            'company_id' => null,
            'title' => $state['title'],
            'evaluation_date' => $state['evaluation_date'],
            'form_status' => $state['form_status'],
            'preset_uuid' => $preset ? $preset['preset']['preset_uuid'] : null,
            'preset_name' => $preset ? $preset['preset']['name'] : null,
            'schema' => $state['schema'],
            'answers' => $state['answers'],
            'revision' => $state['revision'],
            'created_by_staff_id' => (int) $actor['staff_id'],
            'updated_by_staff_id' => (int) $actor['staff_id'],
            'completed_at' => $state['form_status'] === 'completed' ? $now : null,
            'completed_by_staff_id' => $state['form_status'] === 'completed' ? (int) $actor['staff_id'] : null,
            'created_at' => $now,
            'updated_at' => $now,
            'legacy' => false,
        ];

        $change = $this->changeRow($row, $state['action'], $actor, $now);
        $this->repository->transaction(function () use ($row, $change) {
            $this->repository->insertEvaluation($row);
            $this->repository->insertChange($change);
        });

        return $this->saved($row, $state['success_message']);
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function saveExisting(array $actor, $uuid, array $input)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }

        $current = $this->repository->getEvaluation($uuid);
        if (!$current) {
            return $this->missing('Evaluation not found.');
        }

        $prepared = EvaluationForm::prepareSave(
            $this->currentState($current),
            $input,
            $this->knownStaffIds($input)
        );
        if (empty($prepared['ok'])) {
            $prepared = $this->withUnsaved($prepared, $input);
            if (!empty($prepared['conflict'])) {
                $prepared['server'] = $current;
            }
            return $prepared;
        }

        $now = call_user_func($this->clock);
        $state = $prepared['state'];
        $row = $current;
        $row['title'] = $state['title'];
        $row['evaluation_date'] = $state['evaluation_date'];
        $row['form_status'] = $state['form_status'];
        $row['schema'] = $state['schema'];
        $row['answers'] = $state['answers'];
        $row['revision'] = $state['revision'];
        $row['updated_by_staff_id'] = (int) $actor['staff_id'];
        $row['updated_at'] = $now;
        if ($state['form_status'] === 'completed' && empty($current['completed_at'])) {
            $row['completed_at'] = $now;
            $row['completed_by_staff_id'] = (int) $actor['staff_id'];
        }

        $change = $this->changeRow($row, $state['action'], $actor, $now);
        try {
            $written = $this->repository->transaction(function () use ($row, $current, $change) {
                $saved = $this->repository->updateEvaluation($row, (int) $current['revision']);
                if ($saved) {
                    $this->repository->insertChange($change);
                }
                return $saved;
            });
        } catch (\Throwable $exception) {
            return $this->withUnsaved([
                'ok' => false,
                'conflict' => false,
                'errors' => [[
                    'property_id' => null,
                    'message' => 'The evaluation could not be saved. Your entries are still on this page.',
                ]],
            ], $input);
        }

        if (!$written) {
            $server = $this->repository->getEvaluation($uuid);
            return $this->withUnsaved([
                'ok' => false,
                'conflict' => true,
                'server' => $server,
                'errors' => [[
                    'property_id' => null,
                    'message' => 'Someone else saved this evaluation after you opened it. Your unsaved entries are still on this page.',
                ]],
            ], $input);
        }

        return $this->saved($row, $state['success_message']);
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<int, array<string, mixed>>|array<string, mixed>
     */
    public function history(array $actor, $uuid)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }
        if (!$this->repository->getEvaluation($uuid)) {
            return $this->missing('Evaluation not found.');
        }
        return [
            'ok' => true,
            'changes' => $this->repository->changesFor($uuid),
        ];
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function createPreset(array $actor, $name, array $schema)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }
        $normalized = EvaluationForm::presetSchema($schema);
        if (empty($normalized['ok'])) {
            return $normalized;
        }
        $label = $this->presetName($name);
        if ($label === null) {
            return $this->missing('Enter a preset name.');
        }
        $now = call_user_func($this->clock);
        $row = [
            'preset_uuid' => call_user_func($this->ids, 'eval_preset_'),
            'name' => $label,
            'schema' => $normalized['schema'],
            'created_by_staff_id' => (int) $actor['staff_id'],
            'updated_by_staff_id' => (int) $actor['staff_id'],
            'archived_at' => null,
            'revision' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->repository->insertPreset($row);
        return ['ok' => true, 'preset' => $row];
    }

    /**
     * Copies structure from an evaluation. Answers are not read into the preset.
     *
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function savePresetFromEvaluation(array $actor, $evaluationUuid, $name)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }
        $evaluation = $this->repository->getEvaluation($evaluationUuid);
        if (!$evaluation) {
            return $this->missing('Evaluation not found.');
        }
        if (!empty($evaluation['legacy']) || empty($evaluation['schema'])) {
            return $this->missing('The earlier notes format cannot be saved as a preset.');
        }
        return $this->createPreset($actor, $name, $evaluation['schema']);
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function updatePreset(array $actor, $presetUuid, $name, array $schema, $expectedRevision)
    {
        $preset = $this->repository->getPreset($presetUuid);
        if (!$preset) {
            return $this->missing('Preset not found.');
        }
        if (!$this->canManage($actor, $preset)) {
            return $this->forbidden('Only the preset creator or a staff manager can change this preset.');
        }
        $normalized = EvaluationForm::presetSchema($schema);
        if (empty($normalized['ok'])) {
            return $normalized;
        }
        $label = $this->presetName($name);
        if ($label === null) {
            return $this->missing('Enter a preset name.');
        }
        if (!empty($preset['archived_at']) || (int) $preset['revision'] !== (int) $expectedRevision) {
            return $this->presetConflict($preset, $label, $normalized['schema']);
        }
        $next = $preset;
        $next['name'] = $label;
        $next['schema'] = $normalized['schema'];
        $next['updated_by_staff_id'] = (int) $actor['staff_id'];
        $next['revision'] = ((int) $expectedRevision) + 1;
        $next['updated_at'] = call_user_func($this->clock);
        if (!$this->repository->updatePreset($next, $expectedRevision)) {
            $fresh = $this->repository->getPreset($presetUuid);
            return $this->presetConflict($fresh ?: $preset, $label, $normalized['schema']);
        }
        return ['ok' => true, 'preset' => $next];
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function duplicatePreset(array $actor, $presetUuid)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }
        $preset = $this->repository->getPreset($presetUuid);
        if (!$preset) {
            return $this->missing('Preset not found.');
        }
        $label = $preset['name'] . ' copy';
        if (strlen($label) > 200) {
            $label = substr($preset['name'], 0, 190) . ' copy';
        }
        return $this->createPreset($actor, $label, $preset['schema']);
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function archivePreset(array $actor, $presetUuid, $expectedRevision)
    {
        $preset = $this->repository->getPreset($presetUuid);
        if (!$preset) {
            return $this->missing('Preset not found.');
        }
        if (!$this->canManage($actor, $preset)) {
            return $this->forbidden('Only the preset creator or a staff manager can change this preset.');
        }
        if (!empty($preset['archived_at']) || (int) $preset['revision'] !== (int) $expectedRevision) {
            return $this->presetConflict($preset, $preset['name'], $preset['schema']);
        }
        $next = $preset;
        $next['archived_at'] = call_user_func($this->clock);
        $next['updated_by_staff_id'] = (int) $actor['staff_id'];
        $next['updated_at'] = $next['archived_at'];
        $next['revision'] = ((int) $expectedRevision) + 1;
        if (!$this->repository->updatePreset($next, $expectedRevision)) {
            $fresh = $this->repository->getPreset($presetUuid);
            return $this->presetConflict($fresh ?: $preset, $preset['name'], $preset['schema']);
        }
        return ['ok' => true, 'preset' => $next];
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function listPresets(array $actor)
    {
        if (!EvaluationAccess::isStaffActor($actor)) {
            return $this->forbidden('Staff access is required.');
        }
        $rows = [];
        foreach ($this->repository->listPresets(false) as $preset) {
            $rows[] = $this->presentPreset($preset, $actor);
        }
        return ['ok' => true, 'presets' => $rows];
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $preset
     */
    public function presentPreset(array $preset, array $actor)
    {
        $preset['can_manage'] = EvaluationAccess::canManagePreset(
            $actor['staff_id'] ?? 0,
            $actor['staff_role'] ?? 0,
            $preset['created_by_staff_id']
        );
        return $preset;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, int>
     */
    private function knownStaffIds(array $input)
    {
        $schema = isset($input['schema']) && is_array($input['schema']) ? $input['schema'] : ['properties' => []];
        $answers = isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [];
        $requested = EvaluationForm::collectStaffIds($schema, $answers);
        if ($requested === []) {
            return [];
        }
        return $this->repository->existingStaffIds($requested);
    }

    /**
     * @param mixed $presetUuid
     * @return array<string, mixed>|null
     */
    private function presetForNewEvaluation($presetUuid)
    {
        if ($presetUuid === null || $presetUuid === '') {
            return null;
        }
        $preset = $this->repository->getPreset($presetUuid);
        if (!$preset || !empty($preset['archived_at'])) {
            return $this->missing('That preset is no longer available for new evaluations.');
        }
        return ['ok' => true, 'preset' => $preset];
    }

    /**
     * @param array<string, mixed> $current
     * @return array<string, mixed>
     */
    private function currentState(array $current)
    {
        return [
            'exists' => true,
            'legacy' => !empty($current['legacy']) || empty($current['schema']),
            'revision' => (int) $current['revision'],
            'form_status' => $current['form_status'],
            'schema' => isset($current['schema']) ? $current['schema'] : ['properties' => []],
            'answers' => isset($current['answers']) ? $current['answers'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    private function changeRow(array $row, $action, array $actor, $now)
    {
        return [
            'change_uuid' => call_user_func($this->ids, 'eval_change_'),
            'interview_evaluation_uuid' => $row['interview_evaluation_uuid'],
            'revision' => $row['revision'],
            'action' => $action,
            'staff_id' => (int) $actor['staff_id'],
            'snapshot' => [
                'title' => $row['title'],
                'evaluation_date' => $row['evaluation_date'],
                'form_status' => $row['form_status'],
                'schema' => $row['schema'],
                'answers' => $row['answers'],
                'preset_uuid' => $row['preset_uuid'],
                'preset_name' => $row['preset_name'],
            ],
            'created_at' => $now,
        ];
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $preset
     */
    private function canManage(array $actor, array $preset)
    {
        return EvaluationAccess::isStaffActor($actor) && EvaluationAccess::canManagePreset(
            $actor['staff_id'],
            isset($actor['staff_role']) ? $actor['staff_role'] : 0,
            $preset['created_by_staff_id']
        );
    }

    /**
     * @param array<string, mixed> $server
     * @param mixed $name
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function presetConflict(array $server, $name, array $schema)
    {
        return [
            'ok' => false,
            'conflict' => true,
            'errors' => [[
                'property_id' => null,
                'message' => 'Someone else saved this preset after you opened it. Your unsaved edits are still here.',
            ]],
            'server' => $server,
            'unsaved' => [
                'name' => $name,
                'schema' => $schema,
            ],
        ];
    }

    private function presetName($name)
    {
        $label = trim((string) $name);
        if ($label === '' || strlen($label) > 200) {
            return null;
        }
        return $label;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function saved(array $row, $message)
    {
        return [
            'ok' => true,
            'operation' => 'success',
            'message' => $message,
            'data' => $row,
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function withUnsaved(array $result, array $input)
    {
        $result['unsaved'] = $input;
        return $result;
    }

    private function forbidden($message)
    {
        return [
            'ok' => false,
            'forbidden' => true,
            'errors' => [[
                'property_id' => null,
                'message' => $message,
            ]],
        ];
    }

    private function missing($message)
    {
        return [
            'ok' => false,
            'conflict' => false,
            'errors' => [[
                'property_id' => null,
                'message' => $message,
            ]],
        ];
    }
}
