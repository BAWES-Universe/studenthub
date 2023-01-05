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

    /**
     * @param $staff_id
     * @return mixed
     */
    public function filterByStaff($staff_id) {
        return $this->andWhere(['staff_id'=>$staff_id]);
    }

    /**
     * @param $question_uuid
     * @return DailyStandupAnswerQuery
     */
    public function filterByQuestion($question_uuid) {
        return $this->andWhere(['question_uuid'=>$question_uuid]);
    }
 /**
     * @param $question_uuid
     * @return DailyStandupAnswerQuery
     */
    public function filterByDate($date) {
        return $this->andWhere(new Expression("DATE(created_at) = DATE('".$date."')"));
    }
}
