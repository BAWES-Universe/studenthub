<?php

namespace common\models\query;

use yii\db\Expression;

class DailyStandupAnswerQuery extends \yii\db\ActiveQuery
{
    /**
     * filter today's answers
     * @param $db
     * @return DailyStandupAnswerQuery
     */
    public function filterToday($db = null)
    {
        return $this->andWhere(new Expression("DATE(created_at) = DATE('".date('Y-m-d')."')"));
    }
}