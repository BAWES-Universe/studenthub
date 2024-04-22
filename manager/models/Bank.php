<?php

namespace manager\models;


class Bank extends \common\models\Bank
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\manager\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}