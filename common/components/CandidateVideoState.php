<?php

namespace common\components;

/**
 * Stored-video transitions for the Candidate intro video.
 * Callback writes must also be conditioned on the current job id.
 */
class CandidateVideoState
{
    /**
     * Empty video: nothing is stored and nothing is waiting on conversion.
     * @return array<string, mixed>
     */
    public static function clearedVideoAttributes()
    {
        return [
            'candidate_video' => null,
            'candidate_video_job_id' => null,
            'candidate_video_processed' => 1,
        ];
    }

    /**
     * A direct MP4 is already playable, so it must not keep a previous conversion job.
     * @return array<string, mixed>
     */
    public static function directMp4Attributes()
    {
        return [
            'candidate_video_job_id' => null,
            'candidate_video_processed' => true,
        ];
    }

    /**
     * @param mixed $candidateId
     * @param mixed $jobId
     * @return array<string, mixed>
     */
    public static function currentJobCondition($candidateId, $jobId)
    {
        return [
            'candidate_id' => $candidateId,
            'candidate_video_job_id' => $jobId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function failedJobAttributes()
    {
        return [
            'candidate_video' => null,
            'candidate_video_processed' => 1,
        ];
    }

    /**
     * @param mixed $outputFilePath
     * @return array<string, mixed>|null
     */
    public static function completedJobAttributes($outputFilePath)
    {
        if (!is_string($outputFilePath) || $outputFilePath === '') {
            return null;
        }

        $base = explode('.', basename($outputFilePath))[0];
        if ($base === '') {
            return null;
        }

        return [
            'candidate_video' => $base,
            'candidate_video_processed' => 1,
        ];
    }

    /**
     * Apply a callback only when the job id is still the row's current job.
     * Progress events and stale jobs leave the row unchanged.
     *
     * @param array<string, mixed> $row
     * @param mixed $callbackJobId
     * @param mixed $status
     * @param mixed $outputFilePath
     * @return array<string, mixed>
     */
    public static function applyCallback(array $row, $callbackJobId, $status, $outputFilePath = null)
    {
        if ((string)$callbackJobId === '' || !array_key_exists('candidate_video_job_id', $row)) {
            return $row;
        }

        if ((string)$row['candidate_video_job_id'] !== (string)$callbackJobId) {
            return $row;
        }

        if ($status === 'ERROR') {
            return array_merge($row, self::failedJobAttributes());
        }

        if ($status !== 'COMPLETE') {
            return $row;
        }

        $attributes = self::completedJobAttributes($outputFilePath);
        if ($attributes === null) {
            return $row;
        }

        return array_merge($row, $attributes);
    }
}
