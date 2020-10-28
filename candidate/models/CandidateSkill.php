<?php

namespace candidate\models;

class CandidateSkill extends \common\models\CandidateSkill
{

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_skill_created_at']);
        unset($fields['candidate_id']);

        return $fields;
    }
}
