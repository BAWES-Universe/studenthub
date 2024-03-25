<?php

namespace common\models\query;

use yii\db\Expression;

class CandidateStatsQuery extends \yii\db\ActiveQuery
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
     * @param $startDate
     * @param $endDate
     * @return $this
     *
    public function filterByDateRange($startDate, $endDate)
    {
        if ($startDate) {
            $this->andWhere(new Expression("DATE(salary_date) >= DATE('" . $startDate . "')"));
        }

        if ($endDate) {
            $this->andWhere(new Expression("DATE(salary_date) <= DATE('" . $endDate . "')"));
        }

        return $this;
    }*/
}