<?php

namespace common\models\query;

use common\models\Fulltimer;

/**
 * This is the ActiveQuery class for [[FulltimerSkill]].
 *
 */
class FulltimerQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return Fulltimer[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Fulltimer|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param $id
     * @return FulltimerQuery
     */
    public function filterById($id)
    {
        return $this->andWhere(['{{%fulltimer}}.fulltimer_uuid'=>$id]);
    }

    /**
     * @param $candidate_name
     * @return FulltimerQuery
     */
    public function filterName($candidate_name)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_name', $candidate_name]);
    }

    /**
     * @param $candidate_email
     * @return FulltimerQuery
     */
    public function filterEmail($candidate_email)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_email', $candidate_email]);
    }


    public function filterPhone($candidate_phone)
    {
        return $this->andWhere(['like', '{{%fulltimer}}.fulltimer_phone', $candidate_phone]);
    }
}
