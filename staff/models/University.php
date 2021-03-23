<?php

namespace staff\models;


class University extends \common\models\University
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidates ($modelClass);
    }
}