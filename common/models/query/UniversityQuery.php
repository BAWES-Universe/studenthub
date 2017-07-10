<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[University]].
 *
 */
class UniversityQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['{{%university}}.deleted'=>0]);
    }

    /**
     * @return $this
     */
    public function joinCandidate()
    {
        return $this->leftJoin('candidate', 'candidate.university_id = university.university_id');
    }

    public function listWithCandidateCount()
    {
        return $this->select([
                'university.*', 
                'COUNT(candidate.candidate_id) as total_candidates'
            ])
            ->joinCandidate()
            ->groupBy('university.university_id')
            ->orderBy('total_candidates DESC, university_name_en')
            ->asArray();
    }
}
	