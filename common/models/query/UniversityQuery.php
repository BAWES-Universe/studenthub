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
        $this->andWhere(['{{%university}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%university}}.deleted' => 0]);
        return parent::one($db);
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

    /**
    * @param $name
    * @return CountryQuery
    */
    public function filterName($name)
    {
        return $this->andWhere(
            ['or',
                ['like', 'university_name_en', $name],
                ['like', 'university_name_ar', $name]
            ]
        );
    }
}
