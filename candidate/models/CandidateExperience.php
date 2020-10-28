<?php

namespace candidate\models;

class CandidateExperience extends \common\models\CandidateExperience
{
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_experience_created_at']);
        unset($fields['candidate_id']);

        return $fields;
    }
}
