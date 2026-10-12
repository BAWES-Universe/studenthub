<?php

namespace common\tests\unit\components;

use common\components\evaluation\EvaluationAccess;
use common\components\evaluation\EvaluationForm;
use common\components\evaluation\EvaluationWorkflow;
use common\components\evaluation\MemoryEvaluationRepository;
use PHPUnit\Framework\TestCase;
use staff\components\EvaluationResponse;

require_once dirname(__DIR__, 4) . '/common/components/evaluation/EvaluationAccess.php';
require_once dirname(__DIR__, 4) . '/common/components/evaluation/EvaluationForm.php';
require_once dirname(__DIR__, 4) . '/common/components/evaluation/EvaluationRepository.php';
require_once dirname(__DIR__, 4) . '/common/components/evaluation/MemoryEvaluationRepository.php';
require_once dirname(__DIR__, 4) . '/common/components/evaluation/EvaluationWorkflow.php';
require_once dirname(__DIR__, 4) . '/staff/components/EvaluationResponse.php';

class EvaluationFormTest extends TestCase
{
    public function testDraftAllowsMissingRequiredFieldsAndCompleteRejectsThem()
    {
        $workflow = $this->workflow();
        $property = $this->textProperty('prop_strength1', 'Strengths', true);
        $draft = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Initial interview',
            'evaluation_date' => '2026-10-01',
            'schema' => ['properties' => [$property]],
            'answers' => [],
        ]);

        $this->assertTrue($draft['ok']);
        $this->assertSame('draft', $draft['data']['form_status']);
        $this->assertNull($draft['data']['answers']['prop_strength1']);

        $completed = $workflow->saveExisting($this->staff(4), $draft['data']['interview_evaluation_uuid'], [
            'mode' => 'complete',
            'expected_revision' => 1,
            'title' => 'Initial interview',
            'evaluation_date' => '2026-10-01',
            'schema' => ['properties' => [$property]],
            'answers' => [],
        ]);

        $this->assertFalse($completed['ok']);
        $this->assertSame('Strengths is required.', $completed['errors'][0]['message']);
        $this->assertSame('draft', $workflow->saveExisting($this->staff(4), $draft['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Initial interview',
            'schema' => ['properties' => [$property]],
            'answers' => ['prop_strength1' => 'Clear examples'],
        ])['data']['form_status']);
    }

    public function testReorderAndRenameKeepAnswers()
    {
        $workflow = $this->workflow();
        $first = $this->textProperty('prop_strength1', 'Strengths', false);
        $second = $this->textProperty('prop_concern01', 'Concerns', false);
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Interview',
            'schema' => ['properties' => [$first, $second]],
            'answers' => [
                'prop_strength1' => 'Communication',
                'prop_concern01' => 'Notice period',
            ],
        ]);

        $renamed = $second;
        $renamed['name'] = 'Follow-up';
        $updated = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Interview',
            'schema' => ['properties' => [$renamed, $first]],
            'answers' => $saved['data']['answers'],
        ]);

        $this->assertTrue($updated['ok']);
        $this->assertSame('Follow-up', $updated['data']['schema']['properties'][0]['name']);
        $this->assertSame('Communication', $updated['data']['answers']['prop_strength1']);
        $this->assertSame('Notice period', $updated['data']['answers']['prop_concern01']);
    }

    public function testPopulatedTypeChangeRequiresConfirmation()
    {
        $workflow = $this->workflow();
        $text = $this->textProperty('prop_score0001', 'Score', false);
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Interview',
            'schema' => ['properties' => [$text]],
            'answers' => ['prop_score0001' => '4'],
        ]);

        $number = $text;
        $number['type'] = 'number';
        $blocked = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Interview',
            'schema' => ['properties' => [$number]],
            'answers' => ['prop_score0001' => 4],
        ]);
        $this->assertFalse($blocked['ok']);
        $this->assertSame('4', $workflow->repository()->evaluations[$saved['data']['interview_evaluation_uuid']]['answers']['prop_score0001']);

        $cleared = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Interview',
            'schema' => ['properties' => [$number]],
            'answers' => ['prop_score0001' => 4],
            'confirm_clear_properties' => ['prop_score0001'],
        ]);
        $this->assertTrue($cleared['ok']);
        $this->assertSame(4, $cleared['data']['answers']['prop_score0001']);
    }

    public function testRemovingAPopulatedPropertyRequiresConfirmation()
    {
        $workflow = $this->workflow();
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Interview',
            'schema' => ['properties' => [$this->textProperty('prop_note00001', 'Note', false)]],
            'answers' => ['prop_note00001' => 'Keep'],
        ]);

        $blocked = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Interview',
            'schema' => ['properties' => []],
            'answers' => [],
        ]);
        $this->assertFalse($blocked['ok']);
        $this->assertSame(['properties' => []], $blocked['unsaved']['schema']);
        $this->assertSame([], $blocked['unsaved']['answers']);
        $this->assertSame('prop_note00001', $blocked['errors'][0]['property_id']);
        $this->assertSame('Keep', $workflow->repository()->evaluations[$saved['data']['interview_evaluation_uuid']]['answers']['prop_note00001']);
    }

    public function testPresetConflictKeepsPresetFields()
    {
        $method = new \ReflectionMethod(EvaluationResponse::class, 'conflictCopy');
        $method->setAccessible(true);
        $preset = [
            'preset_uuid' => 'eval_preset_1',
            'name' => 'Screening',
            'schema' => ['properties' => []],
            'revision' => 2,
            'can_manage' => true,
        ];
        $copy = $method->invoke(null, $preset);
        $this->assertSame('eval_preset_1', $copy['preset_uuid']);
        $this->assertSame('Screening', $copy['name']);
        $this->assertTrue($copy['can_manage']);
        $this->assertArrayNotHasKey('is_legacy', $copy);
        $this->assertArrayNotHasKey('answers', $copy);
    }

    public function testFormattedNotesAndSelectionsSurviveSave()
    {
        $workflow = $this->workflow();
        $workflow->repository()->staffIds = [8];
        $select = [
            'id' => 'prop_choice001',
            'name' => 'Recommendation',
            'type' => 'select',
            'required' => true,
            'options' => [
                ['id' => 'opt_hire00001', 'label' => 'Hire', 'color' => 'green'],
                ['id' => 'opt_hold00001', 'label' => 'Hold', 'color' => 'yellow'],
            ],
        ];
        $notes = [
            'id' => 'prop_notes0001',
            'name' => 'Notes',
            'type' => 'notes',
            'required' => false,
        ];
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'complete',
            'title' => 'Panel',
            'evaluation_date' => '2026-10-02',
            'schema' => ['properties' => [$select, $notes]],
            'answers' => [
                'prop_choice001' => 'opt_hire00001',
                'prop_notes0001' => '<p>Hello <strong>there</strong></p><ul><li>First</li></ul><script>alert(1)</script>',
            ],
        ]);

        $this->assertTrue($saved['ok'], json_encode($saved));
        $reopened = $workflow->repository()->getEvaluation($saved['data']['interview_evaluation_uuid']);
        $this->assertSame('opt_hire00001', $reopened['answers']['prop_choice001']);
        $this->assertStringContainsString('<strong>there</strong>', $reopened['answers']['prop_notes0001']);
        $this->assertStringContainsString('<li>First</li>', $reopened['answers']['prop_notes0001']);
        $this->assertStringNotContainsString('script', strtolower($reopened['answers']['prop_notes0001']));
        $this->assertSame('completed', $reopened['form_status']);
    }

    public function testUnsafeLinksAreRejected()
    {
        $result = EvaluationForm::prepareSave(['exists' => false], [
            'mode' => 'draft',
            'title' => 'Links',
            'schema' => ['properties' => [[
                'id' => 'prop_link00001',
                'name' => 'Portfolio',
                'type' => 'url',
                'required' => false,
            ]]],
            'answers' => ['prop_link00001' => 'javascript:alert(1)'],
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('http', $result['errors'][0]['message']);
    }

    public function testPresetCopiesStructureOnlyAndLeavesEarlierEvaluationsIntact()
    {
        $workflow = $this->workflow();
        $schema = ['properties' => [
            $this->textProperty('prop_strength1', 'Strengths', true),
            [
                'id' => 'prop_person001',
                'name' => 'Interviewer',
                'type' => 'staff',
                'required' => false,
            ],
        ]];
        $workflow->repository()->staffIds = [8];
        $preset = $workflow->createPreset($this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE), 'Screening', $schema);
        $applied = EvaluationForm::blankEvaluationFromPreset($preset['preset']);
        $this->assertSame([], $applied['answers']);
        $this->assertNull($applied['evaluation_date']);
        $this->assertStringNotContainsString('candidate', json_encode($preset['preset']['schema']));

        $first = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Candidate A',
            'preset_uuid' => $preset['preset']['preset_uuid'],
            'schema' => $applied['schema'],
            'answers' => [
                'prop_strength1' => 'Specific example',
                'prop_person001' => 8,
            ],
        ]);
        $this->assertSame(8, $first['data']['answers']['prop_person001']);

        $fromEvaluation = $workflow->savePresetFromEvaluation(
            $this->staff(9),
            $first['data']['interview_evaluation_uuid'],
            'Shared screening'
        );
        $encoded = json_encode($fromEvaluation['preset']);
        $this->assertStringNotContainsString('Specific example', $encoded);
        $this->assertStringNotContainsString('candidate', $encoded);
        $this->assertStringNotContainsString('"8"', $encoded);

        $secondBlank = EvaluationForm::blankEvaluationFromPreset($fromEvaluation['preset']);
        $second = $workflow->saveNew($this->staff(9), 21, [
            'mode' => 'draft',
            'title' => $secondBlank['title'],
            'preset_uuid' => $fromEvaluation['preset']['preset_uuid'],
            'schema' => $secondBlank['schema'],
            'answers' => $secondBlank['answers'],
        ]);
        $this->assertNull($second['data']['answers']['prop_strength1']);
        $this->assertNull($second['data']['answers']['prop_person001']);
        $this->assertSame(21, $second['data']['candidate_id']);

        $changedSchema = $schema;
        $changedSchema['properties'][0]['name'] = 'Edited on the preset';
        $edited = $workflow->updatePreset(
            $this->staff(9),
            $fromEvaluation['preset']['preset_uuid'],
            'Shared screening',
            $changedSchema,
            $fromEvaluation['preset']['revision']
        );
        $this->assertTrue($edited['ok']);
        $untouched = $workflow->repository()->getEvaluation($first['data']['interview_evaluation_uuid']);
        $this->assertSame('Strengths', $untouched['schema']['properties'][0]['name']);
        $this->assertSame('Specific example', $untouched['answers']['prop_strength1']);

        $archived = $workflow->archivePreset(
            $this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE),
            $preset['preset']['preset_uuid'],
            $preset['preset']['revision']
        );
        $this->assertTrue($archived['ok']);
        $this->assertNotNull($archived['preset']['archived_at']);
        $stillThere = $workflow->repository()->getEvaluation($first['data']['interview_evaluation_uuid']);
        $this->assertSame('Screening', $stillThere['preset_name']);
        $this->assertSame('Specific example', $stillThere['answers']['prop_strength1']);
        $listed = $workflow->listPresets($this->staff(4));
        $listedIds = array_column($listed['presets'], 'preset_uuid');
        $this->assertNotContains($preset['preset']['preset_uuid'], $listedIds);
        $this->assertContains($fromEvaluation['preset']['preset_uuid'], $listedIds);
    }

    public function testPresetManagementIsCreatorOrStaffManager()
    {
        $workflow = $this->workflow();
        $created = $workflow->createPreset($this->staff(4, 6), 'Mine', [
            'properties' => [$this->textProperty('prop_strength1', 'Strengths', false)],
        ]);
        $other = $workflow->updatePreset($this->staff(9, 6), $created['preset']['preset_uuid'], 'Taken', [
            'properties' => [$this->textProperty('prop_strength1', 'Strengths', false)],
        ], $created['preset']['revision']);
        $this->assertTrue($other['forbidden']);

        $manager = $workflow->archivePreset(
            $this->staff(3, EvaluationAccess::STAFF_MANAGER_ROLE),
            $created['preset']['preset_uuid'],
            $created['preset']['revision']
        );
        $this->assertTrue($manager['ok']);
        $this->assertFalse(EvaluationAccess::canManagePreset(9, 6, 4));
        $this->assertTrue(EvaluationAccess::canManagePreset(4, 6, 4));
        $this->assertTrue(EvaluationAccess::canManagePreset(3, EvaluationAccess::STAFF_MANAGER_ROLE, 4));
    }

    public function testAnotherStaffMemberCanEditAndStaleSavesDoNotOverwrite()
    {
        $workflow = $this->workflow();
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'complete',
            'title' => 'Final',
            'evaluation_date' => '2026-10-03',
            'schema' => ['properties' => [$this->textProperty('prop_note00001', 'Note', true)]],
            'answers' => ['prop_note00001' => 'Original'],
        ]);

        $correction = $workflow->saveExisting($this->staff(9), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'correction',
            'expected_revision' => 1,
            'title' => 'Final',
            'evaluation_date' => '2026-10-03',
            'schema' => $saved['data']['schema'],
            'answers' => ['prop_note00001' => 'Corrected'],
        ]);
        $this->assertTrue($correction['ok']);
        $this->assertSame(4, $correction['data']['created_by_staff_id']);
        $this->assertSame(9, $correction['data']['updated_by_staff_id']);
        $this->assertSame('completed', $correction['data']['form_status']);
        $this->assertNotNull($correction['data']['completed_at']);

        $stale = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'correction',
            'expected_revision' => 1,
            'title' => 'Final',
            'evaluation_date' => '2026-10-03',
            'schema' => $saved['data']['schema'],
            'answers' => ['prop_note00001' => 'Stale overwrite'],
        ]);
        $this->assertTrue($stale['conflict']);
        $this->assertSame('Stale overwrite', $stale['unsaved']['answers']['prop_note00001']);
        $this->assertSame('Corrected', $workflow->repository()->getEvaluation($saved['data']['interview_evaluation_uuid'])['answers']['prop_note00001']);

        $history = $workflow->history($this->staff(9), $saved['data']['interview_evaluation_uuid']);
        $this->assertSame('correction', $history['changes'][1]['action']);
        $this->assertSame('Corrected', $history['changes'][1]['snapshot']['answers']['prop_note00001']);
    }

    public function testFailedSaveKeepsStoredRecordAndUnsavedInput()
    {
        $workflow = $this->workflow();
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Draft',
            'schema' => ['properties' => [$this->textProperty('prop_note00001', 'Note', false)]],
            'answers' => ['prop_note00001' => 'Stored'],
        ]);
        $workflow->repository()->throwNextUpdate = true;
        $failed = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Draft',
            'schema' => $saved['data']['schema'],
            'answers' => ['prop_note00001' => 'Not stored yet'],
        ]);

        $this->assertFalse($failed['ok']);
        $this->assertSame('Not stored yet', $failed['unsaved']['answers']['prop_note00001']);
        $this->assertSame('Stored', $workflow->repository()->getEvaluation($saved['data']['interview_evaluation_uuid'])['answers']['prop_note00001']);
    }

    public function testLegacyRowsStayReadableAndAreNotRelabeled()
    {
        $workflow = $this->workflow();
        $uuid = 'interview_evaluation_legacy0001';
        $workflow->repository()->evaluations[$uuid] = [
            'interview_evaluation_uuid' => $uuid,
            'candidate_id' => 20,
            'staff_id' => 4,
            'title' => null,
            'evaluation_date' => null,
            'form_status' => null,
            'preset_uuid' => null,
            'preset_name' => null,
            'schema' => null,
            'answers' => [],
            'revision' => 0,
            'created_by_staff_id' => null,
            'updated_by_staff_id' => null,
            'completed_at' => null,
            'completed_by_staff_id' => null,
            'legacy' => true,
            'created_at' => '2024-07-01 00:00:00',
            'updated_at' => '2024-07-01 00:00:00',
        ];

        $blocked = $workflow->saveExisting($this->staff(4), $uuid, [
            'mode' => 'draft',
            'expected_revision' => 0,
            'title' => 'Invented',
            'schema' => ['properties' => []],
            'answers' => [],
        ]);
        $this->assertFalse($blocked['ok']);
        $this->assertNull($workflow->repository()->getEvaluation($uuid)['form_status']);
        $this->assertNull($workflow->repository()->getEvaluation($uuid)['created_by_staff_id']);
    }

    public function testCandidateAccessAndOtherRoleApis()
    {
        $this->assertTrue(EvaluationAccess::canAccessCandidate(true, [
            'candidate_id' => 20,
            'deleted' => 0,
        ]));
        $this->assertFalse(EvaluationAccess::canAccessCandidate(true, [
            'candidate_id' => 20,
            'deleted' => 1,
        ]));
        $this->assertFalse(EvaluationAccess::canAccessCandidate(false, [
            'candidate_id' => 20,
            'deleted' => 0,
        ]));
        $this->assertFalse(EvaluationAccess::canAccessCandidate(true, null));

        $root = dirname(__DIR__, 4);
        $staffConfig = file_get_contents($root . '/staff/config/main.php');
        $this->assertStringContainsString("'controller' => 'v1/interview-evaluation'", $staffConfig);
        foreach (['admin', 'candidate', 'company', 'manager'] as $module) {
            $config = file_get_contents($root . '/' . $module . '/config/main.php');
            $this->assertStringNotContainsString('interview-evaluation', $config, $module);
        }

        $model = file_get_contents($root . '/common/models/InterviewEvaluation.php');
        $this->assertStringNotContainsString("'schema'", $model);
        $this->assertStringNotContainsString('schema_json', $model);
    }

    public function testNestedNoteMarkupDropsEventHandlersAndKeepsFormatting()
    {
        $saved = EvaluationForm::sanitizeNotes(
            '<div onclick="window.__evalNoteRan=1"><p><strong>Bold</strong></p>'
            . '<div onmouseover="window.__evalNoteRan=1"><ul><li>Kept</li></ul></div></div>'
        );
        $this->assertTrue($saved['ok']);
        $html = strtolower($saved['html']);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<li>kept</li>', $html);
    }

    public function testRequiredNotesAreCheckedAfterSanitizing()
    {
        $notes = [
            'id' => 'prop_notes0001',
            'name' => 'Notes',
            'type' => 'notes',
            'required' => true,
        ];
        $score = [
            'id' => 'prop_score0001',
            'name' => 'Score',
            'type' => 'number',
            'required' => true,
        ];
        $confirmed = [
            'id' => 'prop_check0001',
            'name' => 'Confirmed',
            'type' => 'checkbox',
            'required' => true,
        ];
        foreach (['<p><br></p>', '<p>&nbsp;</p>', '  ', '<div onclick="window.__evalNoteRan=1"><script>alert(1)</script></div>'] as $blank) {
            $result = EvaluationForm::prepareSave(['exists' => false], [
                'mode' => 'complete',
                'title' => 'Panel',
                'evaluation_date' => '2026-10-11',
                'schema' => ['properties' => [$notes, $score, $confirmed]],
                'answers' => [
                    'prop_notes0001' => $blank,
                    'prop_score0001' => 0,
                    'prop_check0001' => false,
                ],
            ]);
            $this->assertFalse($result['ok'], $blank);
            $messages = array_column($result['errors'], 'message');
            $this->assertContains('Notes is required.', $messages);
            $this->assertContains('Confirmed is required.', $messages);
            $this->assertNotContains('Score is required.', $messages);
        }

        $kept = EvaluationForm::prepareSave(['exists' => false], [
            'mode' => 'complete',
            'title' => 'Panel',
            'evaluation_date' => '2026-10-11',
            'schema' => ['properties' => [$notes, $score, $confirmed]],
            'answers' => [
                'prop_notes0001' => '<p onclick="window.__evalNoteRan=1"><strong>Bold</strong></p><ul><li>Kept</li></ul>',
                'prop_score0001' => 0,
                'prop_check0001' => true,
            ],
        ]);
        $this->assertTrue($kept['ok'], json_encode($kept));
        $this->assertSame(0, $kept['state']['answers']['prop_score0001']);
        $this->assertTrue($kept['state']['answers']['prop_check0001']);
        $this->assertStringContainsString('<strong>Bold</strong>', $kept['state']['answers']['prop_notes0001']);
        $this->assertStringNotContainsString('onclick', strtolower($kept['state']['answers']['prop_notes0001']));
    }

    public function testMultiSelectKeepsTheOptionsThatRemain()
    {
        $workflow = $this->workflow();
        $property = [
            'id' => 'prop_langs0001',
            'name' => 'Languages',
            'type' => 'multi_select',
            'required' => false,
            'options' => [
                ['id' => 'opt_arabic001', 'label' => 'Arabic', 'color' => 'green'],
                ['id' => 'opt_englis001', 'label' => 'English', 'color' => 'blue'],
            ],
        ];
        $saved = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Languages',
            'schema' => ['properties' => [$property]],
            'answers' => ['prop_langs0001' => ['opt_arabic001', 'opt_englis001']],
        ]);
        $this->assertTrue($saved['ok']);
        $withoutArabic = $property;
        $withoutArabic['options'] = [$property['options'][1]];
        $blocked = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Languages',
            'schema' => ['properties' => [$withoutArabic]],
            'answers' => ['prop_langs0001' => ['opt_englis001']],
        ]);
        $this->assertFalse($blocked['ok']);

        $clearedFirst = $property;
        $kept = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Languages',
            'schema' => ['properties' => [$clearedFirst]],
            'answers' => ['prop_langs0001' => []],
        ]);
        $this->assertTrue($kept['ok']);
        $removedAfterClear = $workflow->saveExisting($this->staff(4), $saved['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 2,
            'title' => 'Languages',
            'schema' => ['properties' => [$withoutArabic]],
            'answers' => ['prop_langs0001' => []],
        ]);
        $this->assertTrue($removedAfterClear['ok'], json_encode($removedAfterClear));

        $again = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Languages',
            'schema' => ['properties' => [$property]],
            'answers' => ['prop_langs0001' => ['opt_arabic001', 'opt_englis001']],
        ]);
        $confirmed = $workflow->saveExisting($this->staff(4), $again['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Languages',
            'schema' => ['properties' => [$withoutArabic]],
            'answers' => ['prop_langs0001' => ['opt_englis001']],
            'confirm_clear_properties' => ['prop_langs0001'],
        ]);
        $this->assertTrue($confirmed['ok'], json_encode($confirmed));
        $this->assertSame(['opt_englis001'], $confirmed['data']['answers']['prop_langs0001']);
    }

    public function testStalePresetEditsDoNotOverwriteOrRestore()
    {
        $workflow = $this->workflow();
        $created = $workflow->createPreset($this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE), 'Shared', [
            'properties' => [$this->textProperty('prop_strength1', 'Strengths', false)],
        ]);
        $uuid = $created['preset']['preset_uuid'];
        $revision = $created['preset']['revision'];
        $renamed = [
            'properties' => [$this->textProperty('prop_strength1', 'Renamed', false)],
        ];
        $added = [
            'properties' => [
                $this->textProperty('prop_strength1', 'Strengths', false),
                $this->textProperty('prop_concern01', 'Concern', false),
            ],
        ];
        $first = $workflow->updatePreset($this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE), $uuid, 'Shared', $renamed, $revision);
        $this->assertTrue($first['ok']);
        $stale = $workflow->updatePreset($this->staff(3, EvaluationAccess::STAFF_MANAGER_ROLE), $uuid, 'Shared', $added, $revision);
        $this->assertTrue($stale['conflict']);
        $this->assertSame('Renamed', $workflow->repository()->getPreset($uuid)['schema']['properties'][0]['name']);
        $this->assertSame($added, $stale['unsaved']['schema']);

        $archived = $workflow->archivePreset($this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE), $uuid, $first['preset']['revision']);
        $this->assertTrue($archived['ok']);
        $restore = $workflow->updatePreset($this->staff(4, EvaluationAccess::STAFF_MANAGER_ROLE), $uuid, 'Shared', $added, $revision);
        $this->assertTrue($restore['conflict']);
        $this->assertNotNull($workflow->repository()->getPreset($uuid)['archived_at']);
        $this->assertSame('Renamed', $workflow->repository()->getPreset($uuid)['schema']['properties'][0]['name']);
    }

    public function testPhoneRejectsLettersAndStoresTrimmedNumbers()
    {
        $workflow = $this->workflow();
        $phone = [
            'id' => 'prop_phone0001',
            'name' => 'Phone',
            'type' => 'phone',
            'required' => false,
        ];
        $number = [
            'id' => 'prop_score0001',
            'name' => 'Number',
            'type' => 'number',
            'required' => false,
        ];
        $schema = ['properties' => [$phone, $number]];
        $rejected = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Phone check',
            'schema' => $schema,
            'answers' => [
                'prop_phone0001' => 'ddad',
                'prop_score0001' => 8,
            ],
        ]);

        $this->assertFalse($rejected['ok']);
        $this->assertSame('prop_phone0001', $rejected['errors'][0]['property_id']);
        $this->assertSame('Enter a phone number for Phone.', $rejected['errors'][0]['message']);

        $eight = $workflow->saveNew($this->staff(4), 20, [
            'mode' => 'draft',
            'title' => 'Phone check',
            'schema' => $schema,
            'answers' => [
                'prop_phone0001' => '  50000001  ',
                'prop_score0001' => 8,
            ],
        ]);

        $this->assertTrue($eight['ok']);
        $this->assertSame('50000001', $eight['data']['answers']['prop_phone0001']);
        $this->assertSame(8, $eight['data']['answers']['prop_score0001']);

        $international = $workflow->saveExisting($this->staff(4), $eight['data']['interview_evaluation_uuid'], [
            'mode' => 'draft',
            'expected_revision' => 1,
            'title' => 'Phone check',
            'schema' => $schema,
            'answers' => [
                'prop_phone0001' => "  +965 5000 0001\n",
                'prop_score0001' => 8,
            ],
        ]);

        $this->assertTrue($international['ok']);
        $this->assertSame('+965 5000 0001', $international['data']['answers']['prop_phone0001']);
    }

    public function testStaffManagerConstantMatchesTheStaffModel()
    {
        $staff = file_get_contents(dirname(__DIR__, 4) . '/common/models/Staff.php');
        $this->assertStringContainsString('const ROlE_MANAGER = 1;', $staff);
        $this->assertSame(1, EvaluationAccess::STAFF_MANAGER_ROLE);
    }

    private function workflow()
    {
        $sequence = 0;
        return new EvaluationWorkflow(new MemoryEvaluationRepository(), function () {
            return '2026-10-11 12:00:00';
        }, function ($prefix) use (&$sequence) {
            $sequence++;
            return $prefix . str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);
        });
    }

    private function staff($id, $role = 6)
    {
        return [
            'is_staff' => true,
            'staff_id' => $id,
            'staff_role' => $role,
        ];
    }

    private function textProperty($id, $name, $required)
    {
        return [
            'id' => $id,
            'name' => $name,
            'type' => 'text',
            'required' => $required,
        ];
    }
}
