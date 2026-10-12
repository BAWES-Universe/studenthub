<?php

namespace common\components\evaluation;

/**
 * Staff evaluation access.
 *
 * STAFF_MANAGER_ROLE matches common\models\Staff::ROlE_MANAGER.
 * It is a Staff role, not an Admin-app identity.
 */
class EvaluationAccess
{
    public const STAFF_MANAGER_ROLE = 1;

    /**
     * @param array<string, mixed>|null $candidate candidate_id and deleted
     */
    public static function canAccessCandidate($isStaff, $candidate)
    {
        if ($isStaff !== true || !is_array($candidate)) {
            return false;
        }
        if (!isset($candidate['candidate_id'], $candidate['deleted'])) {
            return false;
        }
        if ((int) $candidate['candidate_id'] <= 0) {
            return false;
        }
        return (int) $candidate['deleted'] === 0;
    }

    public static function isStaffManager($staffRole)
    {
        return (int) $staffRole === self::STAFF_MANAGER_ROLE;
    }

    /**
     * Preset edits and archival are limited to the creator or a Staff manager.
     */
    public static function canManagePreset($actorStaffId, $actorRole, $creatorStaffId)
    {
        $actorStaffId = (int) $actorStaffId;
        $creatorStaffId = (int) $creatorStaffId;
        if ($actorStaffId > 0 && $actorStaffId === $creatorStaffId) {
            return true;
        }
        return self::isStaffManager($actorRole);
    }

    /**
     * @param array<string, mixed> $actor
     */
    public static function isStaffActor(array $actor)
    {
        return !empty($actor['is_staff']) && (int) ($actor['staff_id'] ?? 0) > 0;
    }
}
