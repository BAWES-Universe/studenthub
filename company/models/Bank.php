<?php

namespace company\models;


class Bank extends \common\models\Bank
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}