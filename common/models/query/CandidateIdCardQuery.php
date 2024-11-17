<?php
namespace common\models\query;

use common\models\CandidateIdCard;

/**
 * This is the ActiveQuery class for [[CandidateIdCard]].
 *
 */
class CandidateIdCardQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return CandidateIdCard[]|array
     */
    public function all($db = null)
    {
        $this->andWhere (['{{%candidate_id_card}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateIdCard|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%candidate_id_card}}.deleted' => 0]);
        return parent::one($db);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idExpired()
    {
        return $this->andWhere('DATE(expiry_date) < DATE(NOW())');
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%candidate_id_card}}.deleted' => 0]);
        return parent::count($q);
    }
}