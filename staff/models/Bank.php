<?php

namespace staff\models;


class Bank extends \common\models\Bank
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}