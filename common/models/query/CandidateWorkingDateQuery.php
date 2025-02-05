<?php

namespace common\models\query;

use yii\db\Expression;

class CandidateWorkingDateQuery extends \yii\db\ActiveQuery
{
    /**
     * @param $start_date
     * @param $end_date
     * @return CandidateWorkingDateQuery
     */
    public function filterByDateRange($start_date, $end_date) {

        if ($start_date) {
            $this->andWhere(new Expression('DATE(candidate_working_date.date) >= DATE("'. $start_date .'")'));
        }

        if ($end_date) {
            $this->andWhere(new Expression('DATE(candidate_working_date.date) <= DATE("'. $end_date .'")'));
        }

        return $this;
        /*
        if(empty($start_date) || empty($end_date)) {
            return $this;
        }

        return $this->andWhere (new Expression("  
            DATE(candidate_working_date.date) BETWEEN DATE('".date('Y-m-d', strtotime($start_date))."') 
            AND DATE('".date('Y-m-d', strtotime($end_date))."')
        "));*/
    }
}