<?php

namespace common\models\query;

use common\models\CandidateSkill;

/**
 * This is the ActiveQuery class for [[CandidateSkill]].
 *
 */
class CandidateSkillQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%candidate_skill}}.deleted' => 0]);
        return parent::count($q);
    }

    /**
     * @inheritdoc
     * @return CandidateSkill[]|array
     */
    public function all($db = null)
    {
        $this->andWhere (['{{%candidate_skill}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateSkill|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%candidate_skill}}.deleted' => 0]);
        return parent::one($db);
    }
}