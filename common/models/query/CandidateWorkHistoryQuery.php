<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[CandidateWorkHistory]].
 *
 * @see CandidateWorkHistory
 */
class CandidateWorkHistoryQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return CandidateWorkHistory[]|array
     */
    public function all($db = null)
    {
        //$this->andWhere(['{{%candidate_work_history}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateWorkHistory|array|null
     */
    public function one($db = null)
    {
        //$this->andWhere(['{{%candidate_work_history}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * compare candidate id
     * @param $candidate_id
     * @return $this
     */
    public function filterCandidate($candidate_id) {
        return $this->andWhere(['candidate_id'=>$candidate_id]);
    }

    /**
     * compare date
     * @param $date
     * @return $this
     */
    public function filterDate($date) {
        return $this->andWhere(['start_date'=>$date]);
    }

    /**
     * filter by null end date
     * @return $this
     */
    public function emptyEndDate() {
        return $this->andWhere('end_date is null');
    }
}
