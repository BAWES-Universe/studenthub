<?php

namespace common\models\query;

use common\models\FulltimerSkill;

/**
 * This is the ActiveQuery class for [[FulltimerSkill]].
 *
 */
class FulltimerSkillQuery extends \yii\db\ActiveQuery
{

    /**
     * @inheritdoc
     * @return FulltimerSkill[]|array
     */
    public function all($db = null)
    {
        $this->andWhere (['{{%fulltimer_skill}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return FulltimerSkill|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%fulltimer_skill}}.deleted' => 0]);
        return parent::one($db);
    }
}