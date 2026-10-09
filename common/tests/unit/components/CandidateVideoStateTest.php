<?php

namespace common\tests\unit\components;

use common\components\CandidateVideoState;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/common/components/CandidateVideoState.php';

class CandidateVideoStateTest extends TestCase
{
    public function testRemovalThenMp4ReplacementClearsTheStuckJob()
    {
        $pending = [
            'candidate_id' => 17,
            'candidate_name' => 'Noura',
            'candidate_video' => 'stuck-output_1',
            'candidate_video_job_id' => 'stuck-job',
            'candidate_video_processed' => 0,
        ];

        $removed = array_merge($pending, CandidateVideoState::clearedVideoAttributes());

        $this->assertNull($removed['candidate_video']);
        $this->assertNull($removed['candidate_video_job_id']);
        $this->assertSame(1, $removed['candidate_video_processed']);
        $this->assertSame('Noura', $removed['candidate_name']);

        $replaced = array_merge($removed, CandidateVideoState::directMp4Attributes(), [
            'candidate_video' => 'ready_1',
        ]);

        $this->assertSame('ready_1', $replaced['candidate_video']);
        $this->assertNull($replaced['candidate_video_job_id']);
        $this->assertTrue($replaced['candidate_video_processed']);
        $this->assertSame('Noura', $replaced['candidate_name']);
    }

    public function testCurrentJobCompletionAndErrorStillApply()
    {
        $pending = $this->pendingRow();

        $completed = CandidateVideoState::applyCallback(
            $pending,
            'job-current',
            'COMPLETE',
            's3://studenthub/candidate-video/converted_1.mp4'
        );

        $this->assertSame('converted_1', $completed['candidate_video']);
        $this->assertSame(1, $completed['candidate_video_processed']);
        $this->assertSame('job-current', $completed['candidate_video_job_id']);
        $this->assertSame('Noura', $completed['candidate_name']);

        $failed = CandidateVideoState::applyCallback($pending, 'job-current', 'ERROR');

        $this->assertNull($failed['candidate_video']);
        $this->assertSame(1, $failed['candidate_video_processed']);
        $this->assertSame('job-current', $failed['candidate_video_job_id']);
        $this->assertSame('Noura', $failed['candidate_name']);
    }

    public function testProgressAndStaleCallbacksLeaveTheReplacementUntouched()
    {
        $pending = $this->pendingRow();
        $progress = CandidateVideoState::applyCallback(
            $pending,
            'job-current',
            'PROGRESSING',
            's3://studenthub/candidate-video/partial_1.mp4'
        );
        $this->assertSame($pending, $progress);

        $statusUpdate = CandidateVideoState::applyCallback(
            $pending,
            'job-current',
            'STATUS_UPDATE',
            's3://studenthub/candidate-video/partial_1.mp4'
        );
        $this->assertSame($pending, $statusUpdate);

        $incomplete = CandidateVideoState::applyCallback($pending, 'job-current', 'COMPLETE', '');
        $this->assertSame($pending, $incomplete);

        $replacement = [
            'candidate_id' => 17,
            'candidate_name' => 'Noura',
            'candidate_video' => 'replacement_1',
            'candidate_video_job_id' => null,
            'candidate_video_processed' => 1,
        ];

        $staleComplete = CandidateVideoState::applyCallback(
            $replacement,
            'job-current',
            'COMPLETE',
            's3://studenthub/candidate-video/stuck-output_1.mp4'
        );
        $staleError = CandidateVideoState::applyCallback($replacement, 'job-current', 'ERROR');

        $this->assertSame($replacement, $staleComplete);
        $this->assertSame($replacement, $staleError);
        $this->assertSame(
            ['candidate_id' => 17, 'candidate_video_job_id' => 'job-current'],
            CandidateVideoState::currentJobCondition(17, 'job-current')
        );
    }

    public function testAccountControllerAndMp4SaveUseTheseRules()
    {
        $controller = file_get_contents(dirname(__DIR__, 4) . '/candidate/modules/v1/controllers/AccountController.php');
        $model = file_get_contents(dirname(__DIR__, 4) . '/common/models/Candidate.php');

        $this->assertStringContainsString('CandidateVideoState::clearedVideoAttributes()', $controller);
        $this->assertStringContainsString('if (!$model->deleteVideo())', $controller);
        $this->assertStringContainsString('CandidateVideoState::currentJobCondition($model->candidate_id, $jobId)', $controller);
        $this->assertStringContainsString('CandidateVideoState::failedJobAttributes()', $controller);
        $this->assertStringContainsString('CandidateVideoState::completedJobAttributes(', $controller);
        $this->assertStringContainsString('CandidateVideoState::directMp4Attributes()', $model);
    }

    /**
     * @return array<string, mixed>
     */
    private function pendingRow()
    {
        return [
            'candidate_id' => 17,
            'candidate_name' => 'Noura',
            'candidate_video' => 'stuck-output_1',
            'candidate_video_job_id' => 'job-current',
            'candidate_video_processed' => 0,
        ];
    }
}
