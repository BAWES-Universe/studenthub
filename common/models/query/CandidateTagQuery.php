<?php

namespace common\models\query;

use common\models\CandidateTag;

/**
 * This is the ActiveQuery class for [[CandidateTag]].
 *
 */
class CandidateTagQuery extends \yii\db\ActiveQuery
{

    /**
     * @inheritdoc
     * @return CandidateTag[]|array
     */
    public function all($db = null)
    {
        $this->andWhere (['{{%candidate_tag}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateTag|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%candidate_tag}}.deleted' => 0]);
        return parent::one($db);
    }
}