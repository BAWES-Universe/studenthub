<?php

namespace common\models\query;

use common\models\FulltimerExperience;

/**
 * This is the ActiveQuery class for [[FulltimerExperience]].
 *
 */
class FulltimerExperienceQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return FulltimerExperience[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%fulltimer_experience}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return FulltimerExperience|array|null
     */
    public function one($db = null)
    {
        $this->andWhere (['{{%fulltimer_experience}}.deleted' => 0]);
        return parent::one($db);
    }
}