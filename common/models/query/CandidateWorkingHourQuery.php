<?php

namespace common\models\query;

use yii\db\Expression;

class CandidateWorkingHourQuery extends \yii\db\ActiveQuery
{
    /**
     * @param $start_date
     * @param $end_date
     * @return CandidateWorkingDateQuery
     */
    public function filterByDateRange($start_date, $end_date) {

        if ($start_date) {
            $this->andWhere(new Expression('DATE(candidate_working_hour.date) >= DATE("'. $start_date .'")'));
        }

        if ($end_date) {
            $this->andWhere(new Expression('DATE(candidate_working_hour.date) <= DATE("'. $end_date .'")'));
        }

        return $this;

        /*if(empty($start_date) || empty($end_date)) {
            return $this;
        }

        return $this->andWhere (new Expression("  
            DATE(candidate_working_hour.date) BETWEEN DATE('".date('Y-m-d', strtotime($start_date))."') 
            AND DATE('".date('Y-m-d', strtotime($end_date))."')
        "));*/
    }

    public function filterFrom($date) {
        $startDate = date('Y-m-d', strtotime($date));
        $this->andWhere("DATE(candidate_working_hour.date) >= '$startDate'");
    }

    public function filterTo($date) {
        $endDate = date('Y-m-d', strtotime($date));
        $this->andWhere("DATE(candidate_working_hour.date) <= '$endDate'");
    }
}