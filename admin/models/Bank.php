<?php

namespace admin\models;


class Bank extends \common\models\Bank
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}