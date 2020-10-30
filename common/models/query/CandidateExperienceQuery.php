<?php

namespace common\models\query;

use common\models\CandidateExperience;

/**
 * This is the ActiveQuery class for [[CandidateExperience]].
 *
 */
class CandidateExperienceQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return CandidateExperience[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%candidate_experience}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateExperience|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%candidate_experience}}.deleted' => 0]);
        return parent::one($db);
    }
}