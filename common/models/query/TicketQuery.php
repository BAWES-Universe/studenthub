<?php

namespace common\models\query;

use yii\db\Expression;

class TicketQuery extends \yii\db\ActiveQuery
{
    /**
     * @param $start_date
     * @param $end_date
     * @return TicketQuery
     */
    public function filterByDateRange($start_date, $end_date) {

        if(empty($start_date) || empty($end_date)) {
            return $this;
        }

        return $this->andWhere (new Expression("  
            DATE(ticket.created_at) BETWEEN DATE('".date('Y-m-d', strtotime($start_date))."') 
            AND DATE('".date('Y-m-d', strtotime($end_date))."')
        "));
    }
}