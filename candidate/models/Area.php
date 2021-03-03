<?php

namespace candidate\models;


class Area extends \common\models\Area
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\candidate\models\Country")
    {
        return parent::getCountry($modelClass);
    }
}