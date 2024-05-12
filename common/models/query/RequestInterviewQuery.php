<?php

namespace common\models\query;

use yii\db\ActiveQuery;

class RequestInterviewQuery extends ActiveQuery
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
     */
    public function filterDateRange($startDate = null, $endDate = null)
    {
        if ($startDate) {
            $startDate = date('Y-m-d', strtotime($startDate));
            $this->andWhere("DATE(interview_at) >= DATE('$startDate')");
        }

        if ($endDate) {
            $endDate = date('Y-m-d', strtotime($endDate));
            $this->andWhere("DATE(interview_at) <= DATE('$endDate')");
        }

        return $this;
    }
}